<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * All Generated Bills (CakePHP SocietyBillsController::society_generated_bills() / delete_all_bills_payments()).
 * Reads vw_member_transaction_data, the same DB view Cake reads. Needs a scratch copy of the CakePHP database with
 * society/member/bill data (database name must contain "scratch"); society 104723 / financial_year_id 21 has bills
 * there. Test rows use a far-future date (2099) and a dedicated bill_no/voucher_no/receipt_id range so the delete-all
 * cascade never touches real data. Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/AllGeneratedBillsTest.php
 */
class AllGeneratedBillsTest extends TestCase
{
    private const SOCIETY = 104723;
    private const MEMBER = 27834715;
    private const FY = 21;
    private const TEST_DATE = '2099-04-01';
    private const TEST_NO = 99999001;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $this->cleanup();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->cleanup();
        }

        parent::tearDown();
    }

    private function cleanup(): void
    {
        DB::table('member_bill_summaries')->where('society_id', self::SOCIETY)->where('bill_no', self::TEST_NO)->delete();
        DB::table('member_payments')->where('society_id', self::SOCIETY)->where('receipt_id', self::TEST_NO)->delete();
        DB::table('journal_vouchers')->where('society_id', self::SOCIETY)->where('voucher_no', self::TEST_NO)->delete();
        DB::table('member_bill_generates')->where('society_id', self::SOCIETY)->where('bill_number', self::TEST_NO)->delete();
    }

    private function asSociety()
    {
        return $this->actingAs(User::findOrFail(self::SOCIETY))->withSession(['fy.year_id' => self::FY]);
    }

    private function seedTestBill(): void
    {
        $template = DB::table('member_bill_summaries')->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)->first();
        $row = (array) $template;
        unset($row['id']);
        $row['bill_no'] = self::TEST_NO;
        $row['member_id'] = self::MEMBER;
        $row['bill_generated_date'] = self::TEST_DATE;
        $row['bill_end_date'] = self::TEST_DATE;
        $row['financial_year_id'] = self::FY;
        $row['society_id'] = self::SOCIETY;
        $row['monthly_amount'] = 1234.56;
        DB::table('member_bill_summaries')->insert($row);
    }

    public function test_get_request_shows_no_bills_until_a_range_is_submitted(): void
    {
        $this->seedTestBill();

        $response = $this->asSociety()->get(route('society.allGeneratedBills'));

        $response->assertOk();
        $response->assertDontSee((string) self::TEST_NO);
        $response->assertSee('No data available in table');
    }

    public function test_post_with_date_range_returns_matching_bill_with_all_columns(): void
    {
        $this->seedTestBill();

        $response = $this->asSociety()->post(route('society.allGeneratedBills'), [
            'from_date' => '2099-01-01',
            'to_date' => '2099-12-31',
        ]);

        $response->assertOk();
        $response->assertSee((string) self::TEST_NO);
        $response->assertSee('1234.56');
        $response->assertSee('April');
    }

    public function test_post_with_date_range_excludes_bills_outside_it(): void
    {
        $this->seedTestBill();

        $response = $this->asSociety()->post(route('society.allGeneratedBills'), [
            'from_date' => '2098-01-01',
            'to_date' => '2098-12-31',
        ]);

        $response->assertOk();
        $response->assertDontSee((string) self::TEST_NO);
    }

    public function test_delete_all_cascades_across_tables_and_is_scoped_by_date(): void
    {
        $this->seedTestBill();

        DB::table('member_payments')->insert([
            'society_id' => self::SOCIETY, 'receipt_id' => self::TEST_NO, 'member_id' => self::MEMBER,
            'member_transfer' => 0, 'bill_type' => 'reg', 'payment_date' => self::TEST_DATE,
            'financial_year_id' => self::FY, 'amount_paid' => 100,
        ]);
        DB::table('journal_vouchers')->insert([
            'society_id' => self::SOCIETY, 'jv_amount_debited' => 100, 'jv_amount_credited' => 100,
            'voucher_date' => self::TEST_DATE, 'voucher_no' => self::TEST_NO, 'financial_year_id' => self::FY,
        ]);
        DB::table('member_bill_generates')->insert([
            'month' => '4', 'member_id' => self::MEMBER, 'society_id' => self::SOCIETY, 'ledger_head_id' => 1,
            'amount' => 100, 'bill_number' => self::TEST_NO, 'bill_generated_date' => self::TEST_DATE,
            'cdate' => now(), 'financial_year_id' => self::FY,
        ]);
        // deleteSocietyMemberIdentifications() wipes every identification row for
        // society+CURRENT SESSION FY unconditionally (no date-range or bill_no scoping - see
        // the controller's own comment), so this has to be seeded at the real current FY (21)
        // to be in scope; tracked by its own id rather than a marker field.
        $identificationId = DB::table('member_identifications')->insertGetId([
            'society_id' => self::SOCIETY, 'financial_year_id' => self::FY, 'member_transfer' => 0,
        ]);

        try {
            // A bill outside the delete range must survive.
            $survivorId = DB::table('member_bill_summaries')->where('society_id', self::SOCIETY)->where('financial_year_id', self::FY)
                ->where('bill_no', '!=', self::TEST_NO)->value('id');
            $this->assertNotNull($survivorId, 'expected at least one real bill in the fixture to compare against');

            $response = $this->asSociety()->post(route('society.deleteAllBillsAndPayments'), [
                'from_date' => '2099-01-01',
                'to_date' => '2099-12-31',
            ]);

            $response->assertRedirect(route('society.allGeneratedBills'));
            $this->assertStringContainsString('Deleted', session('success') ?? '');

            $this->assertSame(0, DB::table('member_bill_summaries')->where('society_id', self::SOCIETY)->where('bill_no', self::TEST_NO)->count());
            $this->assertSame(0, DB::table('member_payments')->where('society_id', self::SOCIETY)->where('receipt_id', self::TEST_NO)->count());
            $this->assertSame(0, DB::table('journal_vouchers')->where('society_id', self::SOCIETY)->where('voucher_no', self::TEST_NO)->count());
            $this->assertSame(0, DB::table('member_bill_generates')->where('society_id', self::SOCIETY)->where('bill_number', self::TEST_NO)->count());
            $this->assertSame(0, DB::table('member_identifications')->where('id', $identificationId)->count());

            // Untouched: the real bill outside the 2099 range.
            $this->assertSame(1, DB::table('member_bill_summaries')->where('id', $survivorId)->count());
        } finally {
            DB::table('member_identifications')->where('id', $identificationId)->delete();
        }
    }

    public function test_delete_all_requires_a_date_range(): void
    {
        $response = $this->asSociety()->withSession(['date.from_date' => null, 'date.to_date' => null])
            ->post(route('society.deleteAllBillsAndPayments'), []);

        $response->assertRedirect(route('society.allGeneratedBills'));
        $this->assertStringContainsString('Select a From and To date', session('error') ?? '');
    }
}
