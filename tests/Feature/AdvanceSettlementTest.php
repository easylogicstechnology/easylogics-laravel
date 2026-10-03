<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BillSettlementService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Overpayment / advance carry-forward (CakePHP updateMemberBillSummaryById(): after
 * addPaymentInUpdation() returns, the leftover unconsumed payment pool is subtracted from
 * balance_amount, letting it go negative - that negative value is what the next bill's
 * setupBill()-equivalent ($prevBalance < 0 check) reads as an advance). Ported to
 * BillSettlementService::allocatePayments().
 *
 * Needs a scratch copy of the CakePHP database with a real bill chain (database name must
 * contain "scratch"); society 104723 / member 27834715 / financial_year_id 21 has a 10-bill
 * chain, member_transfer 0, with no existing payments, in dnc_scratch. Snapshots and restores
 * member_bill_summaries and member_payments for this member. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/AdvanceSettlementTest.php
 */
class AdvanceSettlementTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;

    private array $billSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $existingPayments = DB::table('member_payments')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->count();
        if ($existingPayments > 0) {
            $this->markTestSkipped('Fixture member already has payments - expected a clean slate for this test.');
        }

        $bills = DB::table('member_bill_summaries')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
            ->where('member_transfer', 0)->orderBy('bill_generated_date')->orderBy('id')->get();
        if ($bills->count() < 2) {
            $this->markTestSkipped('Fixture member does not have the expected multi-bill chain in this database.');
        }
        $this->billSnapshot = $bills->map(fn ($b) => (array) $b)->all();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch') && !empty($this->billSnapshot)) {
            foreach ($this->billSnapshot as $row) {
                $id = $row['id'];
                unset($row['id']);
                DB::table('member_bill_summaries')->where('id', $id)->update($row);
            }
            DB::table('member_payments')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->delete();
        }

        parent::tearDown();
    }

    public function test_overpayment_becomes_a_negative_balance_amount_that_flows_into_the_next_bills_arrears(): void
    {
        $bill1 = $this->billSnapshot[0];
        $bill2 = $this->billSnapshot[1];
        $monthlyPrincipal = (float) $bill1['monthly_principal_amount'];
        $this->assertGreaterThan(0, $monthlyPrincipal, 'fixture bill 1 must have a positive monthly charge for this test to mean anything');

        $overpayBy = 600.0;
        $paymentAmount = $monthlyPrincipal + $overpayBy;

        DB::table('member_payments')->insert([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999002, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => $bill1['bill_generated_date'],
            'financial_year_id' => self::FY, 'amount_paid' => $paymentAmount,
        ]);

        $ok = app(BillSettlementService::class)->recalculateMemberBills(self::MEMBER, self::SOCIETY, 'reg', self::FY, 0);
        $this->assertTrue($ok);

        $afterBill1 = DB::table('member_bill_summaries')->where('id', $bill1['id'])->first();
        $afterBill2 = DB::table('member_bill_summaries')->where('id', $bill2['id'])->first();

        // Bill 1: fully paid off (component balances floored at 0), but the running
        // balance_amount reflects the full overpayment as a negative (advance).
        $this->assertEqualsWithDelta(0.0, (float) $afterBill1->principal_balance, 0.01);
        $this->assertEqualsWithDelta(-$overpayBy, (float) $afterBill1->balance_amount, 0.01);

        // Bill 2: op_principal_arrears must pick up that negative balance (the advance),
        // not the (non-negative) principal_balance component - matches setupBill()'s
        // ($prevBalance < 0) ? $prevBalance : $prevPrincipalBal.
        $this->assertEqualsWithDelta(-$overpayBy, (float) $afterBill2->op_principal_arrears, 0.01);

        // The advance also reduces bill 2's own amount_payable below its own monthly charge.
        $bill2MonthlyBillAmount = (float) $afterBill2->monthly_bill_amount;
        $this->assertEqualsWithDelta($bill2MonthlyBillAmount - $overpayBy, (float) $afterBill2->amount_payable, 0.01);
    }

    public function test_a_payment_that_exactly_covers_dues_leaves_zero_balance_not_negative(): void
    {
        $bill1 = $this->billSnapshot[0];
        $monthlyPrincipal = (float) $bill1['monthly_principal_amount'];

        DB::table('member_payments')->insert([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999003, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => $bill1['bill_generated_date'],
            'financial_year_id' => self::FY, 'amount_paid' => $monthlyPrincipal,
        ]);

        $ok = app(BillSettlementService::class)->recalculateMemberBills(self::MEMBER, self::SOCIETY, 'reg', self::FY, 0);
        $this->assertTrue($ok);

        $afterBill1 = DB::table('member_bill_summaries')->where('id', $bill1['id'])->first();
        $this->assertEqualsWithDelta(0.0, (float) $afterBill1->principal_balance, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $afterBill1->balance_amount, 0.01);
    }
}
