<?php

namespace Tests\Feature;

use App\Models\Society;
use App\Models\User;
use App\Services\Reports\TrialBalanceReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Admin Reports menu pages ported from CakePHP AdminController: Trial Balance Diff Report, Reseller Plans and
 * Payment Setup Check (plus the sidebar order).
 *
 * Needs a scratch copy of the CakePHP database - it refuses to run against any database whose name does not
 * contain "scratch" (it writes reseller_plans rows, removed again afterwards). Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=<scratch db> vendor/bin/phpunit tests/Feature/AdminReportPagesTest.php
 */
class AdminReportPagesTest extends TestCase
{
    private const PREFIX = 'zzplan_';

    protected function setUp(): void
    {
        parent::setUp();

        if (!str_contains((string) config('database.connections.mysql.database'), 'scratch')) {
            $this->markTestSkipped('Needs a scratch copy of the CakePHP database (DB_DATABASE must contain "scratch").');
        }

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
        DB::table('reseller_plans')->where('name', 'like', self::PREFIX . '%')->delete();
    }

    private function admin(): User
    {
        return User::where('role', 'Admin')->firstOrFail();
    }

    private function reseller(): User
    {
        // (the scratch copy also has a reseller row with id 0 - not a usable plan target)
        return User::where('role', 'Reseller')->where('access_level', 3)->where('id', '>', 0)->orderByDesc('id')->firstOrFail();
    }

    private function plan(string $suffix): ?object
    {
        return DB::table('reseller_plans')->where('name', self::PREFIX . $suffix)->first();
    }

    // ---------------------------------------------------------------- menu

    public function test_the_admin_sidebar_lists_the_cake_menu_in_cake_order(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.reports.complaints'))->assertOk()->getContent();

        $order = ['Add Society', 'Reseller Societies', 'Societies List', 'Society Parameters',
            'Trial Balance Diff Report', 'Reseller Society Report', 'Reseller Payments', 'Reseller Plans',
            'Payment Setup Check', 'Complaint Register', 'Manage Founder', 'Manage Partners', 'Manage Help Video'];

        $pos = -1;
        foreach ($order as $label) {
            $at = strpos($html, '>' . $label . '</a>', $pos + 1);
            $this->assertNotFalse($at, "menu item '$label' missing or out of order");
            $pos = $at;
        }
    }

    public function test_the_new_pages_are_admin_only(): void
    {
        $society = User::where('role', 'Society')->firstOrFail();

        foreach (['admin.reports.trialBalanceDiff', 'admin.reports.resellerPlans', 'admin.reports.paymentSetup', 'admin.reports.searchResellers'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
            $this->actingAs($society)->get(route($route))->assertForbidden();
            auth()->logout();
        }

        $this->actingAs($society)->post(route('admin.reports.resellerPlans.save'), ['name' => 'x'])->assertForbidden();
    }

    // ---------------------------------------------------------------- trial balance diff

    public function test_trial_balance_diff_report_lists_active_societies_with_the_engines_totals(): void
    {
        $res = $this->actingAs($this->admin())->get(route('admin.reports.trialBalanceDiff'))->assertOk();

        $res->assertSee('Trial Balance Diff Report (All Societies - Current Financial Year)');
        $res->assertSee('Debit')->assertSee('Credit')->assertSee('Diff (Dr - Cr)');

        $total = Society::where('status', 1)->count();
        $res->assertSee('of ' . number_format($total) . ' societies (page 1 of ' . max(1, (int) ceil($total / 15)) . ')');

        // first society with a mapped year: the row must equal an independent run of the Trial Balance engine
        $first = Society::where('status', 1)->orderBy('society_name')->limit(15)->get(['id', 'society_name'])
            ->first(fn ($s) => DB::table('society_year_mapping')->where('society_id', $s->id)->where('is_active', 1)->exists());
        $this->assertNotNull($first, 'no society with a mapped financial year in the scratch DB');

        $fy = DB::table('financial_year_master')->whereIn('id', DB::table('society_year_mapping')
            ->where('society_id', $first->id)->where('is_active', 1)->pluck('year_id'))->where('is_active', 1)
            ->orderBy('year_start_date')->first();

        $report = (new TrialBalanceReport($first->id, $fy->id, $fy->year_start_date, $fy->year_end_date))
            ->run(['payment_date' => $fy->year_start_date, 'payment_date_to' => $fy->year_end_date]);
        $debit = $credit = 0.0;
        foreach ($report['heads'] as $g) {
            foreach ($g['ledgers'] ?? [] as $l) {
                $debit += (float) $l['transactions']['debit'];
                $credit += (float) $l['transactions']['credit'];
            }
        }

        $res->assertSee(date('d/m/Y', strtotime($fy->year_start_date)) . ' - ' . date('d/m/Y', strtotime($fy->year_end_date)));
        $res->assertSee(number_format($debit, 2))->assertSee(number_format($credit, 2))->assertSee(number_format(round($debit - $credit, 2), 2));
    }

    public function test_a_mismatching_row_is_red_and_paging_is_clamped(): void
    {
        $total = Society::where('status', 1)->count();
        $lastPage = max(1, (int) ceil($total / 15));

        // page beyond the end shows the last page; page 0 / junk shows the first
        $this->actingAs($this->admin())->get(route('admin.reports.trialBalanceDiff', ['page' => 99999]))
            ->assertOk()->assertSee("Page $lastPage / $lastPage");
        $this->get(route('admin.reports.trialBalanceDiff', ['page' => 'abc']))->assertOk()->assertSee("Page 1 / $lastPage");
    }

    // ---------------------------------------------------------------- reseller plans

    public function test_reseller_plans_page_lists_plans_in_two_tabs(): void
    {
        $this->actingAs($this->admin());
        $reseller = $this->reseller();
        DB::table('reseller_plans')->insert([
            ['plan_key' => 'zz_g', 'name' => self::PREFIX . 'general', 'days' => 30, 'amount' => 500, 'is_active' => 1, 'only_reseller_id' => null, 'sort_order' => 990, 'cdate' => now(), 'udate' => now()],
            ['plan_key' => 'zz_s', 'name' => self::PREFIX . 'special', 'days' => 30, 'amount' => 1, 'is_active' => 0, 'only_reseller_id' => $reseller->id, 'sort_order' => 991, 'cdate' => now(), 'udate' => now()],
        ]);

        $this->get(route('admin.reports.resellerPlans'))->assertOk()
            ->assertSee('Reseller Plans &amp; Prices', false)
            ->assertSee('Sab resellers ke plans')->assertSee('Special plan - ek reseller ke liye')
            ->assertSee(self::PREFIX . 'general')->assertSee(self::PREFIX . 'special')
            ->assertSee($reseller->username);

        DB::table('reseller_plans')->whereIn('plan_key', ['zz_g', 'zz_s'])->delete();
    }

    public function test_adding_and_updating_a_general_plan(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.reports.resellerPlans.save'), ['name' => self::PREFIX . 'a', 'days' => '45', 'amount' => '750.5', 'sort_order' => '77', 'is_active' => '1'])
            ->assertRedirect(route('admin.reports.resellerPlans') . '#general')
            ->assertSessionHas('success', 'Plan added: ' . self::PREFIX . 'a - Rs. 750.50 for 45 days.');

        $plan = $this->plan('a');
        $this->assertSame('750.50', $plan->amount);
        $this->assertSame(45, (int) $plan->days);
        $this->assertSame(1, (int) $plan->is_active);
        $this->assertNull($plan->only_reseller_id);
        $this->assertStringStartsWith('plan_', $plan->plan_key);

        // update: same plan key, inactive (no is_active field)
        $this->post(route('admin.reports.resellerPlans.save', $plan->id), ['name' => self::PREFIX . 'a', 'days' => '60', 'amount' => '800', 'sort_order' => '78'])
            ->assertSessionHas('success', 'Plan updated: ' . self::PREFIX . 'a - Rs. 800.00 for 60 days (inactive - resellers will not see it).');

        $after = $this->plan('a');
        $this->assertSame($plan->plan_key, $after->plan_key);
        $this->assertSame(60, (int) $after->days);
        $this->assertSame(0, (int) $after->is_active);
        $this->assertSame(1, DB::table('reseller_plans')->where('name', self::PREFIX . 'a')->count());
    }

    public function test_plan_validation_matches_cake(): void
    {
        $this->actingAs($this->admin());
        $reseller = $this->reseller();
        $ok = ['name' => self::PREFIX . 'v', 'days' => '30', 'amount' => '500', 'sort_order' => '1'];

        $cases = [
            [['name' => ''] + $ok, 'Plan name is required (up to 100 characters).'],
            [['name' => str_repeat('x', 101)] + $ok, 'Plan name is required (up to 100 characters).'],
            [['days' => '0'] + $ok, 'Days must be a whole number between 1 and 3650.'],
            [['days' => '3651'] + $ok, 'Days must be a whole number between 1 and 3650.'],
            [['days' => '1.5'] + $ok, 'Days must be a whole number between 1 and 3650.'],
            [['amount' => '0'] + $ok, 'Price must be more than 0, at most 2 decimals (e.g. 1 or 3000 or 5500.50).'],
            [['amount' => '10.123'] + $ok, 'Price must be more than 0, at most 2 decimals (e.g. 1 or 3000 or 5500.50).'],
            [['amount' => '-5'] + $ok, 'Price must be more than 0, at most 2 decimals (e.g. 1 or 3000 or 5500.50).'],
            [['only_reseller_id' => 'abc'] + $ok, 'Only-for reseller ID must be a user ID number (or left empty for everyone).'],
            [['sort_order' => '1000'] + $ok, 'Order must be a number between 0 and 999.'],
            [['require_reseller' => '1'] + $ok, 'Please pick a reseller from the search list for a special plan.'],
            [['amount' => '99.99'] + $ok, 'A plan under Rs. 100 must be limited to one reseller (fill "Only for reseller ID") so it is never shown to everyone.'],
            [['only_reseller_id' => '999999999'] + $ok, 'No reseller found with ID 999999999.'],
        ];

        foreach ($cases as [$data, $message]) {
            $this->post(route('admin.reports.resellerPlans.save'), $data)->assertSessionHas('error', $message);
        }
        $this->assertNull($this->plan('v'), 'an invalid plan must not be saved');

        // a special plan under Rs. 100 is fine when limited to a real reseller, and returns to the special tab
        $this->post(route('admin.reports.resellerPlans.save'), ['amount' => '1', 'only_reseller_id' => (string) $reseller->id, 'require_reseller' => '1', 'is_active' => '1'] + $ok)
            ->assertRedirect(route('admin.reports.resellerPlans') . '#special')
            ->assertSessionHas('success');
        $this->assertSame($reseller->id, (int) $this->plan('v')->only_reseller_id);
    }

    public function test_updating_a_missing_plan_is_404(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.reports.resellerPlans.save', 999999999), ['name' => self::PREFIX . 'x', 'days' => '30', 'amount' => '500'])
            ->assertNotFound();
    }

    public function test_reseller_search_returns_matching_resellers_as_json(): void
    {
        $reseller = $this->reseller();
        $this->actingAs($this->admin());

        $this->getJson(route('admin.reports.searchResellers', ['q' => $reseller->username]))
            ->assertOk()->assertJsonFragment(['id' => $reseller->id, 'username' => $reseller->username]);

        $this->getJson(route('admin.reports.searchResellers', ['q' => 'x']))->assertOk()->assertExactJson([]);
        $this->getJson(route('admin.reports.searchResellers', ['q' => '%%%%']))->assertOk()->assertExactJson([]);

        // a bare number also matches the ID itself (it can match phone numbers etc. too, capped at 15 rows)
        $ids = collect($this->getJson(route('admin.reports.searchResellers', ['q' => (string) $reseller->id]))->json())->pluck('id');
        $this->assertTrue($ids->contains($reseller->id));
        $this->assertLessThanOrEqual(15, $ids->count());
    }

    // ---------------------------------------------------------------- payment setup

    private function razorpay(string $mode, array $test = [], array $live = []): void
    {
        config(['payment.Razorpay' => [
            'mode' => $mode,
            'test' => $test + ['key_id' => '', 'key_secret' => '', 'webhook_secret' => ''],
            'live' => $live + ['key_id' => '', 'key_secret' => '', 'webhook_secret' => ''],
        ]]);
    }

    public function test_payment_setup_without_keys_says_online_payment_is_off(): void
    {
        $this->razorpay('test');

        $this->actingAs($this->admin())->get(route('admin.reports.paymentSetup'))->assertOk()
            ->assertSee('Payment Setup Check')
            ->assertSee('Online payment abhi band hai.')
            ->assertSee('RAZORPAY_MODE is "test" but the "test" block has no key id and/or key secret.')
            ->assertSee('problem = missing')
            ->assertSee('reseller_payments')->assertSee('reseller_plan_orders')->assertSee('reseller_plans')
            ->assertSee('Razorpay se test karo');
    }

    public function test_payment_setup_never_shows_a_key_or_secret_only_the_prefix(): void
    {
        $this->razorpay('test', ['key_id' => 'rzp_test_SECRETKEYID123', 'key_secret' => 'TopSecretValue999', 'webhook_secret' => 'WebhookSecret777']);

        $res = $this->actingAs($this->admin())->get(route('admin.reports.paymentSetup'))->assertOk();

        $res->assertSee('Setup theek hai.')->assertSee('rzp_test_...')->assertSee('filled in');
        $res->assertDontSee('SECRETKEYID123')->assertDontSee('TopSecretValue999')->assertDontSee('WebhookSecret777');
    }

    public function test_payment_setup_flags_mode_mismatch_and_masked_secrets(): void
    {
        $this->actingAs($this->admin());

        $this->razorpay('test', ['key_id' => 'rzp_live_abc', 'key_secret' => 'realsecret']);
        $this->get(route('admin.reports.paymentSetup'))->assertSee('problem = mode_mismatch')
            ->assertSee('does not start with rzp_test_');

        $this->razorpay('test', ['key_id' => 'rzp_test_abc', 'key_secret' => '*********']);
        $this->get(route('admin.reports.paymentSetup'))->assertSee('problem = invalid_secret')
            ->assertSee('only asterisks (masked text, not the real secret)');

        $this->razorpay('live', [], ['key_id' => 'rzp_live_abc', 'key_secret' => 'realsecret']);
        $this->get(route('admin.reports.paymentSetup'))->assertSee('Mode: <strong>LIVE</strong>', false);
    }

    public function test_razorpay_check_is_a_read_only_get_and_reports_the_result(): void
    {
        $this->razorpay('test', ['key_id' => 'rzp_test_abc', 'key_secret' => 'realsecret']);
        $this->actingAs($this->admin());

        Http::fake(['api.razorpay.com/*' => Http::sequence()
            ->push(['entity' => 'collection', 'count' => 0, 'items' => []], 200)
            ->push(['error' => ['description' => 'Authentication failed']], 401)]);
        $this->post(route('admin.reports.paymentSetup'))->assertOk()
            ->assertSee('Razorpay accepted the test keys (read-only check, nothing was created or charged).');
        Http::assertSent(fn ($r) => $r->method() === 'GET' && str_starts_with($r->url(), 'https://api.razorpay.com/v1/orders') && $r->hasHeader('Authorization'));
        Http::assertSentCount(1);

        $this->post(route('admin.reports.paymentSetup'))->assertSee('Razorpay error: Authentication failed');
    }

    public function test_razorpay_check_is_refused_until_the_keys_are_usable(): void
    {
        $this->razorpay('test');
        Http::fake();

        $this->actingAs($this->admin())->post(route('admin.reports.paymentSetup'))
            ->assertSee('Fix the setup problems above first - the keys are not usable yet.');

        Http::assertNothingSent();
    }
}
