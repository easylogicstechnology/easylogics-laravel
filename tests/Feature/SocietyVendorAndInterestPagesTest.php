<?php

namespace Tests\Feature;

use App\Models\SocietyLedgerHead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Society menu pages ported from CakePHP: Month-wise Interest Rates, Vendor Detail and Vendor Billing
 * (SocietysController::vendor_details / vendor_billings, SocietysAjaxController vendor actions,
 * MonthInterestRatesController).
 *
 * Needs a scratch copy of the CakePHP database WITH society data (it writes vendor / month-rate rows and removes
 * them again) - it refuses to run against any database whose name does not contain "scratch". Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=dnc_scratch vendor/bin/phpunit tests/Feature/SocietyVendorAndInterestPagesTest.php
 */
class SocietyVendorAndInterestPagesTest extends TestCase
{
    private const TAG = 'zzv_';

    private User $society;
    private User $otherSociety;

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

        $ids = DB::table('society_ledger_heads')->where('status', 1)->select('society_id', DB::raw('COUNT(*) c'))
            ->groupBy('society_id')->having('c', '>=', 20)->orderByDesc('c')->limit(2)->pluck('society_id');
        if ($ids->count() < 2) {
            $this->markTestSkipped('Needs two societies with ledger heads in the scratch database.');
        }
        $this->society = User::where('id', $ids[0])->where('role', 'Society')->firstOrFail();
        $this->otherSociety = User::where('id', $ids[1])->where('role', 'Society')->firstOrFail();

        $this->cleanUp();
    }

    protected function tearDown(): void
    {
        if (str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->cleanUp();
        }

        parent::tearDown();
    }

    private function cleanUp(): void
    {
        $billIds = DB::table('vendor_bills')->where('title', 'like', self::TAG . '%')->pluck('id');
        DB::table('vendor_bill_details')->whereIn('vendor_bill_id', $billIds)->delete();
        DB::table('vendor_bills')->whereIn('id', $billIds)->delete();
        DB::table('vendor_details')->where('comments', 'like', self::TAG . '%')->delete();
        DB::table('vendor_facilities')->where('title', 'like', self::TAG . '%')->delete();
        DB::table('society_ledger_heads')->where('title', 'like', self::TAG . '%')->delete();
        DB::table('society_month_interest_rates')->where('rate_year', '>=', 2097)->delete();
    }

    /** ledger heads of a society that are not a vendor yet, and are not income heads */
    private function freeHeads(User $society, int $n = 3): array
    {
        $taken = DB::table('vendor_details')->where('society_id', $society->id)->pluck('ledger_head_id')->all();

        return SocietyLedgerHead::where('society_id', $society->id)->where('status', 1)->where('account_category_id', '!=', 3)
            ->whereNotIn('id', $taken ?: [0])->orderBy('id')->limit($n)->pluck('id')->all();
    }

    private function vendor(int $headId, array $extra = []): void
    {
        $this->postJson(route('society.vendor.saveDetail'), ['VendorDetail' => ['ledger_head_id' => $headId, 'comments' => self::TAG . 'c'] + $extra])
            ->assertOk()->assertJson(['error' => 0]);
    }

    // ---------------------------------------------------------------- menu

    public function test_the_society_menu_has_the_new_items_in_cake_order(): void
    {
        $html = $this->actingAs($this->society)->get(route('society.vendorDetails'))->assertOk()->getContent();

        $order = ['Society Identity', 'Society Parameters', 'Month-wise Interest Rates', 'Tariff Defination', 'Society Head Sub Group',
            'Society Ledger Heads', 'Vendor Detail', 'Vendor Billing', 'Society Tariffs', 'Society Tariff Order', 'Society Payments'];
        $pos = -1;
        foreach ($order as $label) {
            $at = strpos($html, '>' . $label . '</a>', $pos + 1);
            $this->assertNotFalse($at, "menu item '$label' missing or out of order");
            $pos = $at;
        }
    }

    public function test_the_pages_are_society_only(): void
    {
        $admin = User::where('role', 'Admin')->firstOrFail();

        foreach (['society.vendorDetails', 'society.vendorBillings', 'society.monthInterestRates'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
            $this->actingAs($admin)->get(route($route))->assertForbidden();
            auth()->logout();
        }
        $this->actingAs($admin)->postJson(route('society.vendor.saveDetail'), [])->assertForbidden();
    }

    // ---------------------------------------------------------------- month-wise interest rates

    public function test_month_rates_page_and_range_save(): void
    {
        $sid = $this->society->id;
        $this->actingAs($this->society);

        $this->get(route('society.monthInterestRates'))->assertOk()->assertSee('Month-wise Interest Rates')
            ->assertSee('No month-wise rates yet');

        // Nov 2097 - Feb 2098 = 4 months
        $this->post(route('society.monthInterestRates'), ['from_month' => 11, 'from_year' => 2097, 'to_month' => 2, 'to_year' => 2098, 'interest_rate' => '21'])
            ->assertRedirect(route('society.monthInterestRates'))->assertSessionHas('success', '4 month(s) saved at 21.00%.');

        $rows = DB::table('society_month_interest_rates')->where('society_id', $sid)->where('rate_year', '>=', 2097)->orderBy('rate_year')->orderBy('rate_month')->get();
        $this->assertSame(['2097-11', '2097-12', '2098-1', '2098-2'], $rows->map(fn ($r) => $r->rate_year . '-' . $r->rate_month)->all());
        $this->assertTrue($rows->every(fn ($r) => (float) $r->interest_rate === 21.0 && (int) $r->is_active === 1));

        // saving one month again updates it (no duplicate row)
        $this->post(route('society.monthInterestRates'), ['from_month' => 12, 'from_year' => 2097, 'interest_rate' => '18.5'])
            ->assertSessionHas('success', '1 month(s) saved at 18.50%.');
        $this->assertSame(4, DB::table('society_month_interest_rates')->where('society_id', $sid)->where('rate_year', '>=', 2097)->count());
        $this->assertSame('18.50', DB::table('society_month_interest_rates')->where('society_id', $sid)->where('rate_year', 2097)->where('rate_month', 12)->value('interest_rate'));

        $this->get(route('society.monthInterestRates'))->assertSee('December')->assertSee('18.50')->assertDontSee('No month-wise rates yet');
    }

    public function test_month_rate_validation_matches_cake(): void
    {
        $this->actingAs($this->society);
        $ok = ['from_month' => 1, 'from_year' => 2097, 'interest_rate' => '10'];

        $cases = [
            [['interest_rate' => ''] + $ok, 'Interest rate must be a number from 0 to 100.'],
            [['interest_rate' => 'abc'] + $ok, 'Interest rate must be a number from 0 to 100.'],
            [['interest_rate' => '100.5'] + $ok, 'Interest rate must be a number from 0 to 100.'],
            [['interest_rate' => '-1'] + $ok, 'Interest rate must be a number from 0 to 100.'],
            [['from_year' => 1999] + $ok, 'Choose a valid month and year.'],
            [['from_month' => 13] + $ok, 'Choose a valid month and year.'],
            [['to_year' => 2101, 'to_month' => 1] + $ok, 'Choose a valid month and year.'],
            [['to_year' => 2096, 'to_month' => 12] + $ok, '"To" must not be before "From", and a range can cover at most 60 months.'],
            [['to_year' => 2102, 'to_month' => 1] + $ok, 'Choose a valid month and year.'],
            [['from_year' => 2095, 'to_year' => 2100, 'to_month' => 12] + $ok, '"To" must not be before "From", and a range can cover at most 60 months.'],
        ];
        foreach ($cases as [$data, $message]) {
            $this->post(route('society.monthInterestRates'), $data)->assertSessionHas('error', $message);
        }
        $this->assertSame(0, DB::table('society_month_interest_rates')->where('rate_year', '>=', 2097)->count());

        // exactly 60 months (Jan 2097 - Dec 2101 is out of range; use Jan 2095..Dec 2099 -> but years >= 2097 cleaned) - 2 years is enough
        $this->post(route('society.monthInterestRates'), ['from_month' => 1, 'from_year' => 2097, 'to_month' => 12, 'to_year' => 2098, 'interest_rate' => '0'])
            ->assertSessionHas('success', '24 month(s) saved at 0.00%.');
    }

    public function test_a_month_rate_can_only_be_removed_by_its_own_society(): void
    {
        $this->actingAs($this->society)->post(route('society.monthInterestRates'), ['from_month' => 5, 'from_year' => 2097, 'interest_rate' => '12']);
        $id = DB::table('society_month_interest_rates')->where('society_id', $this->society->id)->where('rate_year', 2097)->value('id');

        auth()->logout();
        $this->actingAs($this->otherSociety)->post(route('society.monthInterestRates'), ['delete_id' => $id])
            ->assertSessionHas('error', 'The rate could not be removed.');
        $this->assertTrue(DB::table('society_month_interest_rates')->where('id', $id)->exists());

        auth()->logout();
        $this->actingAs($this->society)->post(route('society.monthInterestRates'), ['delete_id' => $id])
            ->assertSessionHas('success', 'The rate was removed. That month now uses the Interest Rate of Society Parameters.');
        $this->assertFalse(DB::table('society_month_interest_rates')->where('id', $id)->exists());
    }

    // ---------------------------------------------------------------- vendor detail

    public function test_vendor_detail_form_offers_only_heads_that_are_not_a_vendor_yet(): void
    {
        [$a, $b] = $this->freeHeads($this->society, 2);
        $this->actingAs($this->society);
        $this->vendor($a);

        $new = $this->post(route('society.vendor.detailForm'), ['ledgerHeadId' => 0])->assertOk();
        $new->assertSee('Select Ledger Head')->assertSee('value="' . $b . '"', false)->assertDontSee('<option value="' . $a . '">', false);

        $edit = $this->post(route('society.vendor.detailForm'), ['ledgerHeadId' => $a])->assertOk();
        $edit->assertSee('Vendor / Ledger Head')->assertSee(SocietyLedgerHead::find($a)->title)->assertSee('name="VendorDetail[ledger_head_id]" value="' . $a . '"', false);
    }

    public function test_saving_a_vendor_detail_creates_then_updates_one_row(): void
    {
        [$head] = $this->freeHeads($this->society, 1);
        $this->actingAs($this->society);

        $this->postJson(route('society.vendor.saveDetail'), ['VendorDetail' => [
            'ledger_head_id' => $head, 'contact_person_name' => 'Ravi', 'phone_number' => '9999', 'pan_no' => 'ABCDE1234F', 'gst_no' => '27ABCDE1234F1Z5',
            'company_email' => 'r@x.com', 'comments' => self::TAG . 'one', 'amc_start_date' => '', 'facility_id' => '', 'cr_dr' => 'Dr', 'rating' => 'Good',
        ]])->assertOk()->assertJson(['error' => 0, 'error_message' => 'Vendor detail saved successfully.', 'ledger_head_id' => $head]);

        $row = DB::table('vendor_details')->where('society_id', $this->society->id)->where('ledger_head_id', $head)->first();
        $this->assertSame('Ravi', $row->contact_person_name);
        $this->assertSame('Dr', $row->cr_dr);
        $this->assertNull($row->amc_start_date);
        $this->assertNull($row->facility_id);
        $this->assertSame(1, (int) $row->status);

        // second save: same row updated, cr_dr falls back to Cr for anything but 'Dr'
        $this->postJson(route('society.vendor.saveDetail'), ['VendorDetail' => ['ledger_head_id' => $head, 'contact_person_name' => 'Ravi K', 'comments' => self::TAG . 'two', 'cr_dr' => 'x']])->assertJson(['error' => 0]);
        $this->assertSame(1, DB::table('vendor_details')->where('society_id', $this->society->id)->where('ledger_head_id', $head)->count());
        $after = DB::table('vendor_details')->where('id', $row->id)->first();
        $this->assertSame('Ravi K', $after->contact_person_name);
        $this->assertSame('Cr', $after->cr_dr);
    }

    public function test_a_vendor_cannot_be_saved_on_another_societys_ledger_head(): void
    {
        [$foreign] = $this->freeHeads($this->otherSociety, 1);

        $this->actingAs($this->society)->postJson(route('society.vendor.saveDetail'), ['VendorDetail' => ['ledger_head_id' => $foreign]])
            ->assertOk()->assertJson(['error' => 1, 'error_message' => 'Invalid ledger head selected.']);
        $this->postJson(route('society.vendor.saveDetail'), [])->assertJson(['error' => 1, 'error_message' => 'Invalid ledger head selected.']);
        $this->assertFalse(DB::table('vendor_details')->where('ledger_head_id', $foreign)->where('society_id', $this->society->id)->exists());
    }

    public function test_facility_inline_add_is_find_or_create(): void
    {
        $this->actingAs($this->society);

        $this->postJson(route('society.vendor.saveFacility'), ['title' => ' '])->assertJson(['error' => 1, 'error_message' => 'Facility name is required.']);

        $first = $this->postJson(route('society.vendor.saveFacility'), ['title' => self::TAG . 'Lift AMC'])->assertJson(['error' => 0, 'title' => self::TAG . 'Lift AMC'])->json('id');
        $again = $this->postJson(route('society.vendor.saveFacility'), ['title' => self::TAG . 'Lift AMC'])->json('id');
        $this->assertSame($first, $again);
        $this->assertSame(1, DB::table('vendor_facilities')->where('society_id', $this->society->id)->where('title', self::TAG . 'Lift AMC')->count());
    }

    public function test_vendor_list_page_shows_vendors_of_this_society_only(): void
    {
        [$mine] = $this->freeHeads($this->society, 1);
        [$theirs] = $this->freeHeads($this->otherSociety, 1);
        $this->actingAs($this->society);
        $this->vendor($mine, ['contact_person_name' => 'MineContact']);
        auth()->logout();
        $this->actingAs($this->otherSociety);
        $this->vendor($theirs, ['contact_person_name' => 'TheirContact']);

        auth()->logout();
        $this->actingAs($this->society)->get(route('society.vendorDetails'))->assertOk()
            ->assertSee('MineContact')->assertDontSee('TheirContact')->assertSee('openVendorDetailModal(' . $mine . ')', false);

        // the vendor <option>s for billing: active vendors only, sorted, plus "+ Add New Vendor"
        $this->post(route('society.vendor.vendorList'))->assertOk()
            ->assertSee(SocietyLedgerHead::find($mine)->title)->assertSee('+ Add New Vendor');
    }

    // ---------------------------------------------------------------- vendor billing

    private function billPayload(int $vendorHead, array $lines, array $header = []): array
    {
        return [
            'VendorBill' => $header + ['vendor_ledger_head_id' => $vendorHead, 'bill_type' => 'Purchase', 'bill_no' => 'B-1', 'bill_date' => '2026-04-10',
                'due_date' => '', 'title' => self::TAG . 'bill', 'tds_percent' => '10', 'deduct_amount' => '0'],
            'VendorBillDetail' => $lines,
        ];
    }

    public function test_saving_a_bill_recomputes_all_totals_on_the_server(): void
    {
        [$vendor, $particular1, $particular2] = $this->freeHeads($this->society, 3);
        $this->actingAs($this->society);
        $this->vendor($vendor);

        $res = $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, [
            ['ledger_head_id' => $particular1, 'amount' => '1000', 'sgst_rate' => '9', 'cgst_rate' => '9', 'igst_rate' => '', 'hsn_sac' => '9954'],
            ['ledger_head_id' => $particular2, 'amount' => '500', 'sgst_rate' => '', 'cgst_rate' => '', 'igst_rate' => '18'],
            ['ledger_head_id' => '', 'amount' => '999'],            // no particular -> ignored
            ['ledger_head_id' => $particular1, 'amount' => '0'],    // zero amount -> ignored
        ], ['deduct_amount' => '0.4']))->assertOk()->assertJson(['error' => 0, 'error_message' => 'Vendor bill saved successfully.', 'total_bill_amount' => 1593]);

        $bill = DB::table('vendor_bills')->where('id', $res->json('id'))->first();
        $this->assertSame($this->society->id, (int) $bill->society_id);
        // 1500 + 90 + 90 + 90 = 1770 ; TDS 10% = 177 ; 1770 - 177 - 0.4 = 1592.6 -> 1593, round off +0.4
        $this->assertEquals([1500.0, 90.0, 90.0, 90.0, 177.0, 0.4, 1593.0, 0.4], [
            (float) $bill->total_amount, (float) $bill->total_sgst_amount, (float) $bill->total_cgst_amount, (float) $bill->total_igst_amount,
            (float) $bill->tds_amount, (float) $bill->deduct_amount, (float) $bill->total_bill_amount, (float) $bill->round_off_amount,
        ]);
        $this->assertSame('Purchase', $bill->bill_type);
        $this->assertNull($bill->due_date);
        $this->assertSame(1, (int) $bill->status);

        $lines = DB::table('vendor_bill_details')->where('vendor_bill_id', $bill->id)->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertEquals([90.0, 90.0, 0.0], [(float) $lines[0]->sgst_amount, (float) $lines[0]->cgst_amount, (float) $lines[0]->igst_amount]);
        $this->assertSame('9954', $lines[0]->hsn_sac);
        $this->assertEquals(90.0, (float) $lines[1]->igst_amount);
    }

    public function test_editing_a_bill_replaces_its_lines_and_never_duplicates_it(): void
    {
        [$vendor, $p1, $p2] = $this->freeHeads($this->society, 3);
        $this->actingAs($this->society);
        $this->vendor($vendor);

        $id = $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, [
            ['ledger_head_id' => $p1, 'amount' => '100'], ['ledger_head_id' => $p2, 'amount' => '200'],
        ], ['tds_percent' => '0']))->json('id');
        $this->assertCount(2, DB::table('vendor_bill_details')->where('vendor_bill_id', $id)->get());

        $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, [['ledger_head_id' => $p2, 'amount' => '250.50']], ['id' => $id, 'tds_percent' => '0']))
            ->assertJson(['error' => 0, 'id' => $id, 'total_bill_amount' => 251]);

        $this->assertSame(1, DB::table('vendor_bills')->where('title', self::TAG . 'bill')->count());
        $lines = DB::table('vendor_bill_details')->where('vendor_bill_id', $id)->get();
        $this->assertCount(1, $lines);
        $this->assertEquals(250.5, (float) $lines[0]->amount);
        $this->assertEquals(0.5, (float) DB::table('vendor_bills')->where('id', $id)->value('round_off_amount'));
    }

    public function test_bill_validation_and_ownership(): void
    {
        [$vendor, $p1] = $this->freeHeads($this->society, 2);
        [$foreignHead] = $this->freeHeads($this->otherSociety, 1);
        $this->actingAs($this->society);
        $line = [['ledger_head_id' => $p1, 'amount' => '10']];

        $this->postJson(route('society.vendor.saveBill'), $this->billPayload(0, $line))->assertJson(['error' => 1, 'error_message' => 'Please select a valid vendor.']);
        $this->postJson(route('society.vendor.saveBill'), $this->billPayload($foreignHead, $line))->assertJson(['error' => 1, 'error_message' => 'Please select a valid vendor.']);
        $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, [['ledger_head_id' => '', 'amount' => '5']]))->assertJson(['error' => 1, 'error_message' => 'Please add at least one billing line.']);
        $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, $line, ['id' => 999999999]))->assertJson(['error' => 1, 'error_message' => 'Invalid vendor bill.']);
        $this->assertSame(0, DB::table('vendor_bills')->where('title', self::TAG . 'bill')->count());

        // another society cannot edit / open / print this society's bill
        $id = $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, $line))->json('id');
        auth()->logout();
        $this->actingAs($this->otherSociety);
        $this->postJson(route('society.vendor.saveBill'), $this->billPayload($foreignHead, $line, ['id' => $id]))->assertJson(['error' => 1]);
        $this->post(route('society.vendor.billingForm'), ['vendorBillId' => $id])->assertOk()->assertDontSee('value="' . $id . '"', false);
        $this->get(route('society.vendor.printBill', $id))->assertNotFound();
    }

    public function test_billing_form_and_print_show_the_saved_bill(): void
    {
        [$vendor, $p1] = $this->freeHeads($this->society, 2);
        $this->actingAs($this->society);
        $this->vendor($vendor);
        $id = $this->postJson(route('society.vendor.saveBill'), $this->billPayload($vendor, [
            ['ledger_head_id' => $p1, 'amount' => '1000', 'sgst_rate' => '9', 'cgst_rate' => '9', 'hsn_sac' => '9954'],
        ], ['bill_no' => 'INV-77', 'bill_date' => '2026-05-03', 'tds_percent' => '2']))->json('id');

        $form = $this->post(route('society.vendor.billingForm'), ['vendorBillId' => $id])->assertOk();
        $form->assertSee('name="VendorBill[id]" value="' . $id . '"', false)->assertSee('INV-77')->assertSee('2026-05-03')
            ->assertSee('value="9954"', false)->assertSee('value="90.00"', false)->assertSee(SocietyLedgerHead::find($vendor)->title);

        $blank = $this->post(route('society.vendor.billingForm'), ['vendorBillId' => 0])->assertOk();
        $blank->assertSee('+ Add New Vendor')->assertSee('vendor-billing-line', false);

        $this->get(route('society.vendor.printBill', $id))->assertOk()
            ->assertSee('Vendor Bill #INV-77')->assertSee('03/05/2026')->assertSee(SocietyLedgerHead::find($p1)->title)
            ->assertSee('1,180.00')->assertSee('TDS (2.00%)')->assertSee('23.60');

        $this->get(route('society.vendorBillings'))->assertOk()->assertSee('INV-77')->assertSee('openVendorBillingModal(' . $id . ')', false);
    }

    public function test_particular_head_options_exclude_income_heads(): void
    {
        $income = SocietyLedgerHead::where('society_id', $this->society->id)->where('status', 1)->where('account_category_id', 3)->first();
        $expense = SocietyLedgerHead::where('society_id', $this->society->id)->where('status', 1)->where('account_category_id', '!=', 3)->first();
        $this->actingAs($this->society);

        $html = $this->post(route('society.vendor.particularHeads'))->assertOk()->getContent();
        $this->assertStringContainsString('value="' . $expense->id . '"', $html);
        $this->assertStringNotContainsString('value="' . $income->id . '"', $html);
        $this->assertStringEndsWith('<option value="new">+ Add New Ledger Head</option>', $html);
    }

    public function test_quick_add_ledger_head_and_its_cascading_dropdowns(): void
    {
        $sub = DB::table('society_head_sub_categories')->whereIn('society_id', [0, $this->society->id])->where('status', 1)
            ->whereNotNull('account_category_id')->where('account_category_id', '>', 0)->first();
        $this->actingAs($this->society);

        $this->post(route('society.vendor.subCategories'))->assertOk()->assertSee('value="' . $sub->id . '"', false);

        $cat = $this->post(route('society.vendor.accountCategories'), ['headSubCategoryId' => $sub->id])->assertOk()->getContent();
        $this->assertStringContainsString('value="' . $sub->account_category_id . '"', $cat);
        $head = $this->post(route('society.vendor.accountHeads'), ['headSubCategoryId' => $sub->id])->assertOk()->getContent();
        $this->assertStringContainsString('value="' . $sub->account_head_id . '"', $head);
        $this->post(route('society.vendor.accountCategories'), ['headSubCategoryId' => 0])->assertSee('Account category not available');

        $this->postJson(route('society.vendor.saveLedgerHeadInline'), ['SocietyLedgerHeads' => ['title' => '', 'society_head_sub_category_id' => $sub->id]])
            ->assertJson(['error' => 1, 'error_message' => 'Title and Subgroup are required.']);
        $this->postJson(route('society.vendor.saveLedgerHeadInline'), ['SocietyLedgerHeads' => ['title' => self::TAG . 'New Head']])
            ->assertJson(['error' => 1, 'error_message' => 'Title and Subgroup are required.']);

        $res = $this->postJson(route('society.vendor.saveLedgerHeadInline'), ['SocietyLedgerHeads' => [
            'title' => self::TAG . 'New Head', 'short_code' => 'NH', 'opening_amount' => '',
            'society_head_sub_category_id' => $sub->id, 'account_category_id' => $sub->account_category_id, 'account_head_id' => $sub->account_head_id,
        ]])->assertJson(['error' => 0, 'error_message' => 'Ledger head created successfully.', 'title' => self::TAG . 'New Head']);

        $head = DB::table('society_ledger_heads')->where('id', $res->json('id'))->first();
        $this->assertSame($this->society->id, (int) $head->society_id);
        $this->assertSame(1, (int) $head->status);
        $this->assertEquals(0, (float) $head->opening_amount);
        $this->assertSame((int) $sub->id, (int) $head->society_head_sub_category_id);
    }
}
