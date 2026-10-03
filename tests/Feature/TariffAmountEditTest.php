<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Editing a tariff line's Amount on the bill modal (CakePHP's Particulars/Amount inputs -
 * the only hand-editable cells there; Bill Amount, arrears and Amount Payable are readonly
 * and always derived). Ported from SocietyBillsController::updateMemberTarrifDetails(), wired
 * into both SocietyModuleController::updateMemberBillSummaryById() and
 * updateCurrentMemberBillSummaryById().
 *
 * Needs a scratch copy of the CakePHP database with a real bill chain (database name must
 * contain "scratch"); society 104723 / member 27834715 / financial_year_id 21, bill id 448700
 * (bill_no 10630, month 6, generated 2025-06-01) has no member_bill_generates rows yet in
 * dnc_scratch, so this also exercises the "create" path. Snapshots and restores
 * member_bill_summaries and member_bill_generates for this member. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/TariffAmountEditTest.php
 */
class TariffAmountEditTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;
    private const BILL_ID = 448700;
    private const LEDGER_HEAD_ID = 40342;

    private array $billSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $bill = DB::table('member_bill_summaries')->where('id', self::BILL_ID)->where('member_id', self::MEMBER)->first();
        if (!$bill) {
            $this->markTestSkipped('Fixture bill not found in this database.');
        }
        $this->billSnapshot = (array) $bill;

        $existingGenerate = DB::table('member_bill_generates')->where('member_id', self::MEMBER)
            ->where('society_id', self::SOCIETY)->count();
        if ($existingGenerate > 0) {
            $this->markTestSkipped('Fixture member already has member_bill_generates rows - expected a clean slate for this test.');
        }
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch') && !empty($this->billSnapshot)) {
            $row = $this->billSnapshot;
            $id = $row['id'];
            unset($row['id']);
            DB::table('member_bill_summaries')->where('id', $id)->update($row);
            DB::table('member_bill_generates')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->delete();
        }

        parent::tearDown();
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    public function test_editing_a_tariff_amount_creates_the_bill_generate_row_and_updates_monthly_amount(): void
    {
        $response = $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::BILL_ID,
            'member_id' => self::MEMBER,
            'bill_no' => $this->billSnapshot['bill_no'],
            'month' => $this->billSnapshot['month'],
            'bill_generated_date' => $this->billSnapshot['bill_generated_date'],
            'tariff' => [self::LEDGER_HEAD_ID => 750],
        ]);

        $response->assertOk();
        $response->assertJsonPath('error', 0);

        $generated = DB::table('member_bill_generates')->where('member_id', self::MEMBER)
            ->where('ledger_head_id', self::LEDGER_HEAD_ID)->first();
        $this->assertNotNull($generated, 'expected a new member_bill_generates row for the edited tariff line');
        $this->assertEqualsWithDelta(750.0, (float) $generated->amount, 0.01);

        $updatedBill = DB::table('member_bill_summaries')->where('id', self::BILL_ID)->first();
        $this->assertEqualsWithDelta(750.0, (float) $updatedBill->monthly_amount, 0.01);
        // monthly_principal_amount = monthly_amount - discount, recomputed by the
        // recalculation this endpoint also triggers.
        $this->assertEqualsWithDelta(750.0, (float) $updatedBill->monthly_principal_amount, 0.01);
    }

    public function test_editing_the_same_tariff_line_twice_updates_the_existing_row_not_a_duplicate(): void
    {
        $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::BILL_ID, 'member_id' => self::MEMBER, 'bill_no' => $this->billSnapshot['bill_no'],
            'month' => $this->billSnapshot['month'], 'bill_generated_date' => $this->billSnapshot['bill_generated_date'],
            'tariff' => [self::LEDGER_HEAD_ID => 600],
        ]);
        $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::BILL_ID, 'member_id' => self::MEMBER, 'bill_no' => $this->billSnapshot['bill_no'],
            'month' => $this->billSnapshot['month'], 'bill_generated_date' => $this->billSnapshot['bill_generated_date'],
            'tariff' => [self::LEDGER_HEAD_ID => 900],
        ]);

        $rows = DB::table('member_bill_generates')->where('member_id', self::MEMBER)
            ->where('ledger_head_id', self::LEDGER_HEAD_ID)->get();
        $this->assertCount(1, $rows, 'editing the same line twice must update, not duplicate, the row');
        $this->assertEqualsWithDelta(900.0, (float) $rows->first()->amount, 0.01);
    }

    public function test_the_readonly_summary_fields_are_not_accepted_as_direct_input(): void
    {
        // amount_payable / principal_balance / balance_amount are never in the accepted
        // input list for this endpoint - posting them must have no effect; only the fields
        // this endpoint actually reads (discount/adjustments/tariff) can change the bill.
        $before = DB::table('member_bill_summaries')->where('id', self::BILL_ID)->first();

        $this->asSociety()->postJson(route('society.updateMemberBillSummaryById'), [
            'id' => self::BILL_ID, 'member_id' => self::MEMBER,
            'amount_payable' => 999999, 'principal_balance' => 999999, 'balance_amount' => 999999,
        ]);

        $after = DB::table('member_bill_summaries')->where('id', self::BILL_ID)->first();
        $this->assertEqualsWithDelta((float) $before->amount_payable, (float) $after->amount_payable, 0.01);
    }
}
