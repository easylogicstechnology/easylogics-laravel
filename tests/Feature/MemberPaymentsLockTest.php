<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Member Payments lock when Society Parameters > Current Bill Update = Yes, ported from
 * CakePHP SocietysMembersController::_memberPaymentsLocked() /
 * _rejectLockedPaymentChange() (guards add_member_payment() and delete_member_payments()
 * unconditionally - Cake blocks NEW payments too, not just edit/delete of existing ones,
 * because payment save/delete both trigger the full-chain recalculation that Current Bill
 * Update = Yes exists to prevent). Bulk paste actions are deliberately NOT locked, matching
 * Cake (neither of its bulk-paste actions has this guard either).
 *
 * Needs a scratch copy of the CakePHP database (database name must contain "scratch");
 * society 104723 has no existing payments in dnc_scratch. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/MemberPaymentsLockTest.php
 */
class MemberPaymentsLockTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;

    private ?int $paramSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        // The society fixture used here has 980 members; every society page renders the
        // global member-receipt modal, which builds a per-member x per-bank dropdown
        // (980 x 186 banks here) - unrelated to what this test class covers, but it can
        // exceed the default CLI memory_limit before the assertions below ever run.
        ini_set('memory_limit', '768M');

        $this->paramSnapshot = DB::table('society_parameters')->where('society_id', self::SOCIETY)->value('current_bill_update_enabled');
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            DB::table('society_parameters')->where('society_id', self::SOCIETY)
                ->update(['current_bill_update_enabled' => $this->paramSnapshot]);
            DB::table('member_payments')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->delete();
        }

        parent::tearDown();
    }

    private function setParam($value): void
    {
        DB::table('society_parameters')->where('society_id', self::SOCIETY)->update(['current_bill_update_enabled' => $value]);
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    public function test_locked_list_shows_banner_and_no_edit_delete_links(): void
    {
        $this->setParam(1);
        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999005, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => '2026-04-05',
            'financial_year_id' => self::FY, 'amount_paid' => 500, 'payment_mode' => 1,
        ]);

        $response = $this->asSociety()->get(route('society.memberPayments'));

        $response->assertOk();
        $response->assertSee('locked', false);
        $response->assertDontSee(route('society.addMemberPayment', $paymentId), false);
    }

    public function test_unlocked_list_shows_edit_delete_links_and_no_banner(): void
    {
        $this->setParam(null);
        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999006, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => '2026-04-05',
            'financial_year_id' => self::FY, 'amount_paid' => 500, 'payment_mode' => 1,
        ]);

        $response = $this->asSociety()->get(route('society.memberPayments'));

        $response->assertOk();
        $response->assertDontSee('Payments are');
        $response->assertSee(route('society.addMemberPayment', $paymentId), false);
    }

    public function test_locked_blocks_viewing_and_submitting_the_add_payment_form(): void
    {
        $this->setParam(1);

        $getResponse = $this->asSociety()->get(route('society.addMemberPayment'));
        $getResponse->assertRedirect(route('society.memberPayments'));

        $postResponse = $this->asSociety()->post(route('society.addMemberPayment'), [
            'member_id' => self::MEMBER, 'payment_date' => '2026-04-05', 'amount_paid' => 500,
            'payment_mode' => 1, 'bill_type' => 'reg',
        ]);
        $postResponse->assertRedirect(route('society.memberPayments'));
        $this->assertStringContainsString('locked', session('error') ?? '');

        $this->assertSame(0, DB::table('member_payments')->where('member_id', self::MEMBER)->where('society_id', self::SOCIETY)->count());
    }

    public function test_locked_blocks_editing_and_deleting_an_existing_payment(): void
    {
        $paymentId = DB::table('member_payments')->insertGetId([
            'society_id' => self::SOCIETY, 'receipt_id' => 999999007, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => '2026-04-05',
            'financial_year_id' => self::FY, 'amount_paid' => 500, 'payment_mode' => 1,
        ]);

        $this->setParam(1);

        $editResponse = $this->asSociety()->post(route('society.addMemberPayment', $paymentId), [
            'amount_paid' => 999,
        ]);
        $editResponse->assertRedirect(route('society.memberPayments'));
        $this->assertEqualsWithDelta(500.0, (float) DB::table('member_payments')->where('id', $paymentId)->value('amount_paid'), 0.01);

        $deleteResponse = $this->asSociety()->delete(route('society.deleteMemberPayment', $paymentId));
        $deleteResponse->assertRedirect(route('society.memberPayments'));
        $this->assertSame(1, DB::table('member_payments')->where('id', $paymentId)->count());
    }

    public function test_not_locked_when_parameter_is_no_or_not_set(): void
    {
        foreach ([null, 0] as $value) {
            $this->setParam($value);
            $response = $this->asSociety()->get(route('society.addMemberPayment'));
            $response->assertOk();
        }
    }
}
