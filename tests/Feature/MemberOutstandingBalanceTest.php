<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Make Payment's Outstanding panel (Total Outstanding = principal + interest + tax): the balance endpoint is the port
 * of Cake's SocietysAjaxController::getSocietyMembersOpBalance(). Needs a scratch copy of the CakePHP database
 * (database name must contain "scratch"); member 98670 belongs to society 10405 there.
 */
class MemberOutstandingBalanceTest extends TestCase
{
    private const SOCIETY = 10405;

    private const MEMBER = 98670;

    private const FY = 21;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        DB::table('member_bill_summaries')->where('flat_no', 'OUTSTAND-TEST')->delete();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            DB::table('member_bill_summaries')->where('flat_no', 'OUTSTAND-TEST')->delete();
        }

        parent::tearDown();
    }

    private function summary(int $billNo, array $balances, int $societyId = self::SOCIETY): void
    {
        DB::table('member_bill_summaries')->insert([
            'society_id' => $societyId, 'member_id' => self::MEMBER, 'financial_year_id' => self::FY, 'bill_no' => $billNo,
            'month' => '4', 'flat_no' => 'OUTSTAND-TEST',
        ] + $balances);
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    public function test_it_returns_the_latest_bill_summary_balances_with_two_decimals(): void
    {
        $this->summary(1, ['principal_balance' => 100, 'interest_balance' => 10, 'tax_balance' => 1, 'balance_amount' => 111]);
        $this->summary(2, ['principal_balance' => 24790, 'interest_balance' => 2381, 'tax_balance' => 0, 'balance_amount' => 27171]);

        $this->asSociety()->getJson(route('society.getMemberOpBalance', self::MEMBER))
            ->assertOk()
            ->assertJsonPath('MemberBillSummary.principal_balance', '24790.00')
            ->assertJsonPath('MemberBillSummary.interest_balance', '2381.00')
            ->assertJsonPath('MemberBillSummary.tax_balance', '0.00')
            ->assertJsonPath('MemberBillSummary.balance_amount', '27171.00');
    }

    public function test_it_returns_an_empty_list_without_a_bill_summary_or_for_another_societys_summary(): void
    {
        $this->asSociety()->getJson(route('society.getMemberOpBalance', self::MEMBER))->assertOk()->assertExactJson([]);

        $this->summary(1, ['principal_balance' => 500, 'interest_balance' => 5, 'tax_balance' => 0, 'balance_amount' => 505], 999999);
        $this->asSociety()->getJson(route('society.getMemberOpBalance', self::MEMBER))->assertOk()->assertExactJson([]);
    }

    public function test_the_make_payment_page_shows_total_principle_interest_tax_in_that_order(): void
    {
        $html = (string) $this->asSociety()->get(route('society.addMemberPayment'))->assertOk()->getContent();

        $order = [];
        foreach (['Total Outstanding', 'Principle', 'Interest', 'Tax'] as $label) {
            $order[$label] = strpos($html, '<label for="' . ['Total Outstanding' => 'member_outstanding_payment', 'Principle' => 'member_principle', 'Interest' => 'member_interest', 'Tax' => 'member_tax'][$label] . '">');
            $this->assertNotFalse($order[$label], "$label field is on the page");
        }
        $this->assertSame(array_values($order), collect($order)->sort()->values()->all(), 'fields appear in the order Total, Principle, Interest, Tax');
        $html && $this->assertStringContainsString('principal + interest + tax', $html);
    }

    public function test_the_balance_endpoint_is_only_for_a_society_login(): void
    {
        $this->getJson(route('society.getMemberOpBalance', self::MEMBER))->assertUnauthorized();
        $this->actingAs(User::where('role', 'Member')->firstOrFail())->getJson(route('society.getMemberOpBalance', self::MEMBER))->assertForbidden();
    }
}
