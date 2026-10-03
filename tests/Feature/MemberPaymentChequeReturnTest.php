<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Member Payment form: the cheque/bank fields (Cheque Date, Bank Slip No, Member Bank
 * Details) and the Cheque Return flow, ported from CakePHP's
 * SocietysMembersController::add_member_payment() / memberChecqueReturn().
 *
 * Needs a scratch copy of the CakePHP database (database name must contain "scratch");
 * society 104723 / member 27834715 has no existing payments in dnc_scratch, used as a clean
 * slate. Cleans up everything it creates. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/MemberPaymentChequeReturnTest.php
 */
class MemberPaymentChequeReturnTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;

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
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $ids = DB::table('member_payments')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('cheque_return_details')->whereIn('payment_id', $ids)->delete();
                DB::table('member_bill_settlements')->whereIn('payment_id', $ids)->delete();
                DB::table('member_payments')->whereIn('id', $ids)->delete();
            }
        }

        parent::tearDown();
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    public function test_saving_a_cheque_payment_persists_bank_details_and_cheque_date(): void
    {
        $bank = DB::table('banks')->where('status', 1)->first();
        $this->assertNotNull($bank, 'expected at least one active bank in the fixture database');

        $response = $this->asSociety()->post(route('society.addMemberPayment'), [
            'member_id' => self::MEMBER,
            'payment_date' => '2026-04-05',
            'amount_paid' => 500,
            'payment_mode' => 3,
            'cheque_reference_number' => 'CHQ001',
            'entry_date' => '2026-04-04',
            'credited_date' => '2026-04-06',
            'bank_slip_no' => 'SLIP-42',
            'member_bank_id' => $bank->id,
            'member_bank_ifsc' => 'HDFC0001234',
            'member_bank_branch' => 'Test Branch',
            'bill_type' => 'reg',
        ]);

        $response->assertRedirect(route('society.memberPayments'));

        $saved = DB::table('member_payments')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->first();
        $this->assertNotNull($saved);
        $this->assertSame('SLIP-42', $saved->bank_slip_no);
        $this->assertSame((string) $bank->id, (string) $saved->member_bank_id);
        $this->assertSame('HDFC0001234', $saved->member_bank_ifsc);
        $this->assertSame('Test Branch', $saved->member_bank_branch);
        $this->assertStringStartsWith('2026-04-04', (string) $saved->entry_date);
    }

    public function test_marking_a_cheque_returned_upserts_the_record_and_excludes_it_from_settlement(): void
    {
        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999004, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => '2026-04-05',
            'financial_year_id' => self::FY, 'amount_paid' => 500, 'payment_mode' => 3,
            'cheque_reference_number' => 'CHQ002',
        ]);

        $response = $this->asSociety()->post(route('society.addMemberPayment', $paymentId), [
            'cheque_return_date' => '2026-04-10',
            'cheque_return_reason' => 'Insufficient funds',
        ]);

        $response->assertRedirect(route('society.memberPayments'));
        $this->assertStringContainsString('reverted', session('success') ?? '');

        $returnRow = DB::table('cheque_return_details')->where('payment_id', $paymentId)->first();
        $this->assertNotNull($returnRow);
        $this->assertSame('Insufficient funds', $returnRow->cheque_return_reason);
        $this->assertSame((string) self::MEMBER, (string) $returnRow->member_id);

        // Resubmitting a different reason must UPDATE the same row, not insert a second one.
        $this->asSociety()->post(route('society.addMemberPayment', $paymentId), [
            'cheque_return_date' => '2026-04-11',
            'cheque_return_reason' => 'Signature mismatch',
        ]);
        $this->assertSame(1, DB::table('cheque_return_details')->where('payment_id', $paymentId)->count());
        $this->assertSame('Signature mismatch', DB::table('cheque_return_details')->where('payment_id', $paymentId)->value('cheque_return_reason'));

        // The original payment record itself is left untouched (per the ported comment).
        $unchangedPayment = DB::table('member_payments')->where('id', $paymentId)->first();
        $this->assertEqualsWithDelta(500.0, (float) $unchangedPayment->amount_paid, 0.01);
    }
}
