<?php

namespace Tests\Feature;

use App\Models\ResellerSubUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * What a reseller's team login (Cake: role SubReseller) may do INSIDE a society it opened - the port of CakePHP's
 * _subResellerCan() / _subResellerHasPermission() checks and of the society menu / top bar filtering.
 *
 * Same scratch-database requirements as ResellerSubUserTest (the database name must contain "scratch";
 * reseller 104350 owns societies 10405, 171, 103850, 24, 103955). Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=<scratch db> vendor/bin/phpunit tests/Feature/SubResellerSocietyPermissionTest.php
 */
class SubResellerSocietyPermissionTest extends TestCase
{
    private const RESELLER = 104350;

    private const SOCIETY = 10405;

    private const PREFIX = '98998';

    private const DENIED = 'You do not have permission to do that.';

    private int $counter = 0;

    /**
     * Every server-side check CakePHP has that exists in Laravel: [route, method, route params, module, action, json].
     * action "add|edit" = any one of them; "add" / "edit" on the *Ledger/Payment/Contra/MemberPayment rows is what Cake
     * picks from whether an id was passed.
     */
    private const GUARDED = [
        'society parameters (save)' => ['society.parameters', 'post', [], 'sm_societyparameters', 'edit', false],
        'ledger head add' => ['society.addLedgerHead', 'get', [], 'sm_societyledgerheads', 'add', false],
        'ledger head edit' => ['society.addLedgerHead', 'get', ['id' => 1], 'sm_societyledgerheads', 'edit', false],
        'ledger head delete' => ['society.deleteLedgerHead', 'delete', ['id' => 999999], 'sm_societyledgerheads', 'delete', false],
        'society payment add' => ['society.addPayment', 'get', [], 'sm_societypayments', 'add', false],
        'society payment edit' => ['society.addPayment', 'get', ['id' => 1], 'sm_societypayments', 'edit', false],
        'society payment delete' => ['society.deletePayment', 'delete', ['id' => 999999], 'sm_societypayments', 'delete', false],
        'society payment inline edit' => ['society.updatePaymentField', 'post', [], 'sm_societypayments', 'edit', true],
        'cash contra add' => ['society.addCashContra', 'get', [], 'sm_societycashcontra', 'add', false],
        'cash contra edit' => ['society.addCashContra', 'get', ['id' => 1], 'sm_societycashcontra', 'edit', false],
        'cash contra delete' => ['society.deleteCashContra', 'delete', ['id' => 999999], 'sm_societycashcontra', 'delete', false],
        'payment entry' => ['society.savePaymentEntry', 'post', [], 'tb_paymententry', 'add', true],
        'generate bill' => ['society.generateBill', 'post', [], 'tb_generatebill', 'generate', true],
        'member tariff (save)' => ['society.memberTariff', 'post', [], 'mm_membertariff', 'add|edit', false],
        'member payment add' => ['society.addMemberPayment', 'get', [], 'mm_memberpayments', 'add', false],
        'member payment edit' => ['society.addMemberPayment', 'get', ['id' => 1], 'mm_memberpayments', 'edit', false],
        'member payment delete' => ['society.deleteMemberPayment', 'delete', ['id' => 999999], 'mm_memberpayments', 'delete', false],
        'import member payments' => ['society.importMemberPayments', 'get', [], 'mm_importmemberpayments', 'add', false],
        'import society payments' => ['society.importSocietyPayments', 'get', [], 'mm_importsocietypayments', 'add', false],
        'member receipt bulk paste' => ['society.saveMemberReceiptBulkPaste', 'post', [], 'tb_bulkpaste', 'add', true],
    ];

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
        $ids = DB::table('users')->where('username', 'like', self::PREFIX . '%')->pluck('id');
        DB::table('reseller_sub_users')->whereIn('user_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();
    }

    /**
     * Creates a team login holding exactly $flags (plus software view, needed to open a society at all) and the one
     * society, signs it in and opens the society - the state CakePHP calls "sub-user inside a society".
     */
    private function teamLoginInSociety(array $flags): User
    {
        $mobile = self::PREFIX . sprintf('%05d', ++$this->counter);
        $perms = array_fill_keys(array_merge(['software_view'], $flags), '1');

        $this->actingAs(User::findOrFail(self::RESELLER))->post(route('reseller.users.store'), [
            'full_name' => 'Team ' . $mobile, 'mobile' => $mobile, 'password' => 'secret1', 'password_confirmation' => 'secret1', 'Permission' => $perms,
        ]);
        $user = User::where('username', $mobile)->firstOrFail();
        $sub = ResellerSubUser::where('user_id', $user->id)->firstOrFail();
        $this->post(route('reseller.users.savePermissions'), ['user_id' => $user->id, 'Permission' => $perms, 'society_ids' => [self::SOCIETY]]);

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->post('/login', ['username' => $mobile, 'password' => 'secret1'])->assertRedirect(route('reseller.dashboard'));
        $this->post(route('reseller.societies.switch', self::SOCIETY))->assertRedirect(route('society.dashboard'));
        $this->assertAuthenticatedAs(User::find(self::SOCIETY));
        $this->assertSame($sub->id, session('sub_reseller')['id']);

        return $user;
    }

    private function hit(array $route)
    {
        [$name, $method, $params, , , ] = $route;
        $url = route($name, $params);
        session()->forget(['error', 'success', 'info', '_flash']); // a flash left by the previous refusal would be shown on this page

        return $method === 'get' ? $this->get($url) : $this->{$method}($url, []);
    }

    private function assertDenied($response, bool $json, string $what): void
    {
        if ($json) {
            $response->assertOk()->assertJson(['success' => false, 'error' => self::DENIED, 'message' => self::DENIED]);

            return;
        }
        $response->assertRedirect(route('society.dashboard'));
        $this->assertSame(self::DENIED, session('error'), "$what should be refused");
    }

    private function assertNotDenied($response, string $what): void
    {
        $this->assertNotSame(self::DENIED, session('error'), "$what should not be refused");
        $this->assertStringNotContainsString(self::DENIED, (string) $response->getContent(), "$what should not be refused");
    }

    public function test_every_guarded_action_is_refused_without_its_grant_and_allowed_with_exactly_that_grant(): void
    {
        $this->teamLoginInSociety([]); // nothing but software view

        foreach (self::GUARDED as $what => $route) {
            $this->assertDenied($this->hit($route), $route[5], $what);
        }

        // ...and each one opens with only its own grant (another module's grant is no help)
        foreach (self::GUARDED as $what => $route) {
            [, , , $module, $action] = $route;
            $this->teamLoginInSociety([$module . '_' . explode('|', $action)[0]]);
            $this->assertNotDenied($this->hit($route), $what);

            $this->teamLoginInSociety([$module . '_view']); // viewing is not enough to change anything
            $this->assertDenied($this->hit($route), $route[5], "$what with view only");
        }
    }

    public function test_member_tariff_save_needs_add_or_edit_and_its_page_is_open_to_view(): void
    {
        $this->teamLoginInSociety([]);
        $this->assertDenied($this->post(route('society.memberTariff'), []), false, 'save without a grant');
        session()->forget(['error', '_flash']);
        $this->assertNotDenied($this->get(route('society.memberTariff')), 'opening the page');

        $this->teamLoginInSociety(['mm_membertariff_edit']);
        session()->forget(['error', '_flash']);
        $this->assertNotDenied($this->post(route('society.memberTariff'), []), 'save with edit only');

        $this->teamLoginInSociety(['mm_membertariff_delete', 'mm_membertariff_view']);
        $this->assertDenied($this->post(route('society.memberTariff'), []), false, 'save with delete/view only');
    }

    public function test_society_parameters_page_is_open_but_saving_needs_edit(): void
    {
        $this->teamLoginInSociety([]);
        session()->forget(['error', '_flash']);
        $this->assertNotDenied($this->get(route('society.parameters')), 'opening parameters');
        session()->forget(['error', '_flash']);
        $this->assertDenied($this->post(route('society.parameters'), ['interest_rate' => 1]), false, 'saving parameters');

        $this->teamLoginInSociety(['sm_societyparameters_edit']);
        $this->assertNotDenied($this->post(route('society.parameters'), []), 'saving with edit');
    }

    public function test_the_reseller_itself_and_a_real_society_login_are_never_restricted(): void
    {
        $this->actingAs(User::findOrFail(self::SOCIETY)); // a real Society login: no team-login row in its session
        foreach (self::GUARDED as $what => $route) {
            $this->assertNotDenied($this->hit($route), "society login: $what");
        }

        // the reseller opening its own society
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->actingAs(User::findOrFail(self::RESELLER))->post(route('reseller.societies.switch', self::SOCIETY))->assertRedirect(route('society.dashboard'));
        $this->assertNull(session('sub_reseller'));
        foreach (self::GUARDED as $what => $route) {
            $this->assertNotDenied($this->hit($route), "reseller in society: $what");
        }
    }

    public function test_a_left_over_team_login_row_never_restricts_a_society_login(): void
    {
        $society = User::findOrFail(self::SOCIETY);
        $plain = 'Soc-' . uniqid();
        $original = $society->password;
        try {
            DB::table('users')->where('id', $society->id)->update(['password' => sha1('DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi' . $plain)]);
            $this->flushSession();
            $this->withSession(['sub_reseller' => ['reseller_id' => self::RESELLER, 'software_view' => 1]])
                ->post('/login', ['username' => $society->username, 'password' => $plain])->assertRedirect(route('society.dashboard'));
            $this->assertNull(session('sub_reseller'));
            $this->assertNotDenied($this->hit(self::GUARDED['generate bill']), 'generate bill as the society');
        } finally {
            DB::table('users')->where('id', $society->id)->update(['password' => $original]);
        }
    }

    private function sidebarAndTopBar(): array
    {
        $html = (string) $this->get(route('society.helpGuide'))->assertOk()->getContent();
        preg_match('#<aside class="sidebar">(.*?)</aside>#s', $html, $side);
        preg_match('#<div class="menu-buttons">(.*?)</div>#s', $html, $top);

        return [html_entity_decode(strip_tags($side[1] ?? '')), html_entity_decode(strip_tags($top[1] ?? ''))];
    }

    public function test_menus_inside_the_society_follow_the_grants(): void
    {
        // nothing ticked: a bare sidebar (Dashboard, Help) and an empty top bar
        $this->teamLoginInSociety([]);
        [$side, $top] = $this->sidebarAndTopBar();
        foreach (['Society Identity', 'Member Identity', 'FD Register', 'Employee Category', 'TDS Dashboard', 'GST Dashboard', 'Update Bill Dates', 'Bill Full Page', 'Accounts'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $side, "$hidden hidden");
        }
        $this->assertStringContainsString('Dashboard', $side);
        $this->assertStringContainsString('Help & Guide', $side);
        $this->assertSame('', trim($top), 'no top bar buttons');

        // one item grant keeps its section, and only that item of Member (Building Identity etc. need the section box)
        $this->teamLoginInSociety(['mm_memberpayments_view', 'tb_generatebill_view', 'tb_printbill_view']);
        [$side, $top] = $this->sidebarAndTopBar();
        $this->assertStringContainsString('Member Payments', $side);
        foreach (['Building Identity', 'Member Tariff', 'Society Parameters'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $side, "$hidden hidden");
        }
        $this->assertStringContainsString('Generate Bill', $top);
        $this->assertStringContainsString('Print Bill', $top);
        $this->assertStringNotContainsString('Payment Entry', $top);
        $this->assertStringNotContainsString('Ledger Heads', $top);

        // the section box shows the section's other items too
        $this->teamLoginInSociety(['sb_member_view', 'sb_tds_view']);
        [$side] = $this->sidebarAndTopBar();
        foreach (['Building Identity', 'Member Identity', 'TDS Dashboard'] as $shown) {
            $this->assertStringContainsString($shown, $side, "$shown shown");
        }
        $this->assertStringNotContainsString('GST Dashboard', $side);

        // the reseller itself sees the whole menu
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->actingAs(User::findOrFail(self::RESELLER))->post(route('reseller.societies.switch', self::SOCIETY));
        [$side, $top] = $this->sidebarAndTopBar();
        $this->assertStringContainsString('GST Dashboard', $side);
        $this->assertStringContainsString('Ledger Heads', $top);
        $this->assertStringContainsString('Send SMS', $top);
    }
}
