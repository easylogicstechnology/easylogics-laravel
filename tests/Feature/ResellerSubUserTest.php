<?php

namespace Tests\Feature;

use App\Hashing\LegacyCakeHasher;
use App\Models\ResellerSubUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Manage Users (a reseller's team logins) - behaviour checked against the CakePHP ResellersController
 * (manage_users / add_user / edit_user / delete_user / permissions).
 *
 * Needs a scratch copy of the CakePHP database (users, reseller_sub_users with the full permission columns,
 * reseller_societies, societies, user_logins, financial_year_master, society_year_mapping) - it refuses to run
 * against any database whose name does not contain "scratch". Reseller 104350 is the scratch copy's reseller with
 * five societies (10405, 171, 103850, 24, 103955). Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=<scratch db> vendor/bin/phpunit tests/Feature/ResellerSubUserTest.php
 */
class ResellerSubUserTest extends TestCase
{
    private const RESELLER = 104350;

    private const TEST_MOBILE_PREFIX = '98999';

    /** @var \Illuminate\Testing\TestResponse|null the response of the last createSubUser() call */
    private $created;

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
        $ids = DB::table('users')->where('username', 'like', self::TEST_MOBILE_PREFIX . '%')
            ->orWhere('username', 'LegacyReseller1')->pluck('id');
        DB::table('reseller_sub_users')->whereIn('user_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();
    }

    private function reseller(): User
    {
        return User::findOrFail(self::RESELLER);
    }

    private function createUrl(): string
    {
        return route('reseller.users.store');
    }

    /** POST /reseller/users as the reseller and return the reseller_sub_users row it made */
    private function createSubUser(array $overrides = [], array $permissions = []): ?ResellerSubUser
    {
        $mobile = $overrides['mobile'] ?? self::TEST_MOBILE_PREFIX . '00001';
        $this->created = $this->actingAs($this->reseller())->post($this->createUrl(), $overrides + [
            'full_name' => 'Team Member',
            'email' => 'team@example.com',
            'mobile' => $mobile,
            'password' => 'secret1',
            'password_confirmation' => 'secret1',
            'Permission' => $permissions,
        ]);

        $user = User::where('username', trim($mobile))->first();

        return $user ? ResellerSubUser::where('user_id', $user->id)->first() : null;
    }

    public function test_manage_users_routes_need_a_login(): void
    {
        foreach (['reseller.users.index', 'reseller.users.create', 'reseller.users.permissions'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
        $this->post($this->createUrl(), [])->assertRedirect(route('login'));
        $this->put(route('reseller.users.update', 1), [])->assertRedirect(route('login'));
        $this->post(route('reseller.users.deactivate', 1))->assertRedirect(route('login'));
        $this->post(route('reseller.users.savePermissions'), [])->assertRedirect(route('login'));
    }

    public function test_only_a_reseller_can_use_manage_users(): void
    {
        $logins = [
            'Society' => User::where('role', 'Society')->firstOrFail(),
            'Member' => User::where('role', 'Member')->firstOrFail(),
            'SubReseller' => User::where('role', 'SubReseller')->firstOrFail(),
        ];

        foreach ($logins as $role => $user) {
            $this->actingAs($user);
            $this->get(route('reseller.users.index'))->assertForbidden();
            $this->get(route('reseller.users.create'))->assertForbidden();
            $this->get(route('reseller.users.permissions'))->assertForbidden();
            $this->post($this->createUrl(), ['full_name' => 'x', 'mobile' => self::TEST_MOBILE_PREFIX . '11111', 'password' => 'abcd', 'password_confirmation' => 'abcd'])->assertForbidden();
            $this->put(route('reseller.users.update', 3), [])->assertForbidden();
            $this->post(route('reseller.users.deactivate', 3))->assertForbidden();
            $this->post(route('reseller.users.savePermissions'), ['user_id' => 1])->assertForbidden();
        }

        $this->assertNull(User::where('username', self::TEST_MOBILE_PREFIX . '11111')->first());
    }

    public function test_create_validation_messages_and_duplicate_check(): void
    {
        $base = ['full_name' => 'X', 'email' => '', 'mobile' => self::TEST_MOBILE_PREFIX . '00001', 'password' => 'abcd', 'password_confirmation' => 'abcd'];
        $required = 'Full Name, Mobile Number and Password are required.';

        foreach ([['full_name' => ''], ['mobile' => ''], ['password' => '', 'password_confirmation' => ''], ['full_name' => '0'], ['password' => '0', 'password_confirmation' => '0']] as $bad) {
            $this->actingAs($this->reseller())->post($this->createUrl(), $bad + $base)
                ->assertRedirect(route('reseller.users.create'))->assertSessionHas('error', $required);
        }

        $this->post($this->createUrl(), ['password_confirmation' => 'abce'] + $base)
            ->assertRedirect(route('reseller.users.create'))->assertSessionHas('error', 'Password and Confirm Password do not match.');

        // mobile number = username, unique across every role (a reseller's own username too)
        foreach (['9820642410', 'Easy@2649'] as $taken) {
            $this->post($this->createUrl(), ['mobile' => $taken] + $base)
                ->assertRedirect(route('reseller.users.create'))->assertSessionHas('error', 'A user with this mobile number already exists.');
        }

        $this->assertNull(User::where('username', self::TEST_MOBILE_PREFIX . '00001')->first());
    }

    public function test_create_edit_deactivate_and_the_permission_grid(): void
    {
        $sub = $this->createSubUser(
            ['full_name' => ' Team Member ', 'email' => ' team@example.com ', 'mobile' => ' ' . self::TEST_MOBILE_PREFIX . '00001 '],
            ['software_view' => '1', 'software_add' => '1', 'sb_gst_view' => '1', 'mm_membertariff_update' => '1']
        );
        $this->assertNotNull($sub);
        $this->created->assertRedirect(route('reseller.users.index'))->assertSessionHas('success', 'User created successfully.');

        $user = User::find($sub->user_id);
        $this->assertSame(self::TEST_MOBILE_PREFIX . '00001', $user->username);
        $this->assertSame(self::TEST_MOBILE_PREFIX . '00001', $user->mobile);
        $this->assertSame('Team Member', $user->full_name);
        $this->assertSame('team@example.com', $user->email);
        $this->assertSame('SubReseller', $user->role);
        $this->assertSame(3, (int) $user->access_level);
        $this->assertSame(self::RESELLER, (int) $user->added_by);
        $this->assertSame(1, (int) $user->status, 'status defaults to 1 when the checkbox is not posted');
        $this->assertSame(sha1('DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi' . 'secret1'), $user->password, 'CakePHP SHA1+salt');
        $this->assertSame(self::RESELLER, (int) $sub->reseller_id);
        $this->assertSame(1, (int) $sub->status);
        $this->assertNull($sub->allowed_society_ids);
        $on = collect(ResellerSubUser::permissionColumns())->filter(fn ($c) => $sub->$c == 1)->values()->all();
        $this->assertEqualsCanonicalizing(['software_add', 'software_view', 'sb_gst_view', 'mm_membertariff_update'], $on);

        // Edit: password only when non-blank ("0" counts as blank), unposted status is left alone, the grid is replaced
        $this->put(route('reseller.users.update', $sub->id), [
            'full_name' => 'Renamed', 'email' => '', 'mobile' => '9111111111', 'password' => '0',
            'Permission' => ['dashboard_view' => '1'],
        ])->assertRedirect(route('reseller.users.index'))->assertSessionHas('success', 'User updated successfully.');
        $user->refresh();
        $this->assertSame('Renamed', $user->full_name);
        $this->assertSame('', $user->email);
        $this->assertSame('9111111111', $user->mobile);
        $this->assertSame(self::TEST_MOBILE_PREFIX . '00001', $user->username, 'username never changes');
        $this->assertSame(sha1('DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi' . 'secret1'), $user->password);
        $this->assertSame(1, (int) $user->status);
        $sub->refresh();
        $this->assertSame(['dashboard_view'], collect(ResellerSubUser::permissionColumns())->filter(fn ($c) => $sub->$c == 1)->values()->all());

        $this->put(route('reseller.users.update', $sub->id), ['full_name' => 'Renamed', 'password' => 'newpw']);
        $this->assertSame(sha1('DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi' . 'newpw'), $user->refresh()->password);

        // Deactivate: both rows go to status 0, nothing is deleted
        $activeBefore = substr_count($this->get(route('reseller.users.index'))->getContent(), 'title="Deactivate"');
        $this->assertGreaterThan(0, $activeBefore);
        $this->post(route('reseller.users.deactivate', $sub->id))
            ->assertRedirect(route('reseller.users.index'))->assertSessionHas('success', 'User deactivated successfully.');
        $this->assertSame(0, (int) $user->refresh()->status);
        $this->assertSame(0, (int) $sub->refresh()->status);
        $after = $this->get(route('reseller.users.index'))->assertSee('Inactive');
        $this->assertSame($activeBefore - 1, substr_count($after->getContent(), 'title="Deactivate"'), 'an inactive user has no Deactivate button');

        // Editing an inactive user with status posted re-activates users.status only (Cake quirk: the link row stays 0)
        $this->put(route('reseller.users.update', $sub->id), ['full_name' => 'Renamed', 'status' => '1']);
        $this->assertSame(1, (int) $user->refresh()->status);
        $this->assertSame(0, (int) $sub->refresh()->status);
    }

    public function test_permissions_page_saves_the_grid_and_only_the_resellers_own_societies(): void
    {
        $sub = $this->createSubUser();
        $userId = $sub->user_id;

        $this->get(route('reseller.users.permissions'))->assertOk()->assertSee('Select a user from the dropdown above');
        $this->get(route('reseller.users.permissions', ['user_id' => $userId]))->assertOk()
            ->assertSee('Allowed Societies')->assertSee('Select All View');

        $this->post(route('reseller.users.savePermissions'), [
            'user_id' => $userId,
            'Permission' => ['software_view' => '1', 'sb_tds_add' => '1', 'sm_societycashcontra_delete' => '1'],
            'society_ids' => ['10405', '24', '999', 'abc', '10405'],
        ])->assertRedirect(route('reseller.users.permissions', ['user_id' => $userId]))
            ->assertSessionHas('success', 'Permissions updated successfully.');

        $sub->refresh();
        $this->assertSame('10405,24,10405', $sub->allowed_society_ids, 'ids the reseller does not own are dropped; the rest keep posted order');
        $this->assertEqualsCanonicalizing(['software_view', 'sb_tds_add', 'sm_societycashcontra_delete'],
            collect(ResellerSubUser::permissionColumns())->filter(fn ($c) => $sub->$c == 1)->values()->all());

        $this->get(route('reseller.users.permissions', ['user_id' => $userId]))
            ->assertSee('Society (in-society sidebar)')->assertSee('Society Cash Contra (in Society submenu)');

        // Unchecking every society saves an empty list
        $this->post(route('reseller.users.savePermissions'), ['user_id' => $userId, 'Permission' => ['members_view' => '1']]);
        $this->assertSame('', $sub->refresh()->allowed_society_ids);

        // A user id that is not this reseller's: nothing changes and there is no message
        $before = ResellerSubUser::find(1)->toArray();
        $this->post(route('reseller.users.savePermissions'), ['user_id' => ResellerSubUser::find(1)->user_id, 'Permission' => ['members_view' => '1'], 'society_ids' => ['24']])
            ->assertRedirect()->assertSessionMissing('success');
        $this->assertSame($before, ResellerSubUser::find(1)->toArray());
        $this->post(route('reseller.users.savePermissions'), ['user_id' => 999999])->assertSessionMissing('success');
    }

    public function test_a_reseller_cannot_read_or_change_another_resellers_users(): void
    {
        $foreign = ResellerSubUser::findOrFail(1); // belongs to reseller 103850
        $foreignUser = User::find($foreign->user_id)->toArray();
        $foreignRow = $foreign->toArray();

        $this->actingAs($this->reseller());
        $this->get(route('reseller.users.edit', $foreign->id))->assertRedirect(route('reseller.users.index'))->assertSessionHas('error', 'User not found.');
        $this->put(route('reseller.users.update', $foreign->id), ['full_name' => 'Hacked', 'Permission' => ['members_view' => '1']])->assertSessionHas('error', 'User not found.');
        $this->post(route('reseller.users.deactivate', $foreign->id))->assertSessionHas('error', 'User not found.');
        $this->get(route('reseller.users.edit', 99999))->assertSessionHas('error', 'User not found.');

        $this->assertSame($foreignUser, User::find($foreign->user_id)->toArray());
        $this->assertSame($foreignRow, ResellerSubUser::find(1)->toArray());

        $list = $this->get(route('reseller.users.index'))->assertOk();
        $list->assertDontSee('8108107495')->assertDontSee('8208902644');
        $list->assertSee('9820642410'); // the scratch copy's one team login of this reseller
    }

    public function test_team_login_signs_in_as_the_reseller_and_sees_only_what_it_was_given(): void
    {
        $sub = $this->createSubUser(['mobile' => self::TEST_MOBILE_PREFIX . '00002'], ['software_view' => '1', 'settings_view' => '1']);
        $this->post(route('reseller.users.savePermissions'), [
            'user_id' => $sub->user_id, 'Permission' => ['software_view' => '1', 'settings_view' => '1'], 'society_ids' => ['10405', '24'],
        ]);
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        // login (CakePHP SHA1+salt hash), lands on the reseller dashboard with the team login's row in the session
        $this->post('/login', ['username' => self::TEST_MOBILE_PREFIX . '00002', 'password' => 'secret1'])
            ->assertRedirect(route('reseller.dashboard'));
        $this->assertAuthenticatedAs(User::find($sub->user_id));
        $this->assertSame(self::RESELLER, session('sub_reseller')['reseller_id']);
        $this->assertSame('10405,24', session('sub_reseller')['allowed_society_ids']);

        // the reseller's own dashboard, narrowed to the allowed societies (the reseller owns five)
        $dash = $this->get(route('reseller.dashboard'))->assertOk();
        $this->assertSame(2, $dash->viewData('totalSocieties'));
        $this->assertEqualsCanonicalizing([10405, 24], $dash->viewData('assignedSocieties')->pluck('societie_id')->map(fn ($i) => (int) $i)->all());

        $assigned = $this->get(route('reseller.societies.assigned'))->assertOk();
        $this->assertEqualsCanonicalizing([10405, 24], $assigned->viewData('assignedSocieties')->pluck('societie_id')->map(fn ($i) => (int) $i)->all());

        // menu: Cake's reduced team-login menu, driven by the permission grid (and the sidebar is actually shown)
        $this->assertDoesNotMatchRegularExpression('/class="app-wrapper[^"]*no-sidebar/', $dash->getContent());
        $dash->assertSee('My Societies')->assertSee('Society Finance Year Mapping')->assertSee('Complaints')
            ->assertDontSee('Manage Users')->assertDontSee('Create Society')->assertDontSee('Payment Dashboard')->assertDontSee('Personal Identity');

        // Manage Users itself is not open to a team login
        $this->get(route('reseller.users.index'))->assertForbidden();
        $this->post($this->createUrl(), ['full_name' => 'x', 'mobile' => self::TEST_MOBILE_PREFIX . '22222', 'password' => 'abcd', 'password_confirmation' => 'abcd'])->assertForbidden();

        // opening a society that the reseller owns but did not grant
        $this->post(route('reseller.societies.switch', 171))
            ->assertRedirect(route('reseller.societies.assigned'))->assertSessionHas('error', 'You do not have access to that society.');
        $this->assertAuthenticatedAs(User::find($sub->user_id));

        // opening a granted society, then back: returns to the team login, never to the reseller itself
        $this->post(route('reseller.societies.switch', 10405))->assertRedirect(route('society.dashboard'));
        $this->assertAuthenticatedAs(User::find(10405));
        $this->post(route('reseller.switchBack'))->assertRedirect(route('reseller.dashboard'));
        $this->assertAuthenticatedAs(User::find($sub->user_id));
    }

    public function test_team_login_without_any_grant_sees_no_societies_and_a_short_menu(): void
    {
        $sub = $this->createSubUser(['mobile' => self::TEST_MOBILE_PREFIX . '00003']);
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->post('/login', ['username' => self::TEST_MOBILE_PREFIX . '00003', 'password' => 'secret1'])->assertRedirect(route('reseller.dashboard'));

        $dash = $this->get(route('reseller.dashboard'))->assertOk();
        $this->assertSame(0, $dash->viewData('totalSocieties'));
        $dash->assertDontSee('My Societies')->assertDontSee('Society Finance Year Mapping')->assertSee('Complaints');
        $this->get(route('reseller.societies.assigned'))->assertForbidden();
        $this->get(route('reseller.financeYearMapping'))->assertForbidden();
    }

    public function test_team_login_that_is_not_linked_to_a_reseller_is_signed_out(): void
    {
        $sub = $this->createSubUser(['mobile' => self::TEST_MOBILE_PREFIX . '00004']);
        DB::table('reseller_sub_users')->where('id', $sub->id)->delete();
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->post('/login', ['username' => self::TEST_MOBILE_PREFIX . '00004', 'password' => 'secret1'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Your account is not linked to a reseller. Please contact your reseller.');
        $this->assertGuest();
    }

    public function test_existing_logins_with_a_cakephp_hash_still_work(): void
    {
        $id = DB::table('users')->insertGetId([
            'username' => 'LegacyReseller1', 'password' => (new LegacyCakeHasher())->make('legacy-pw'), 'role' => 'Reseller',
            'access_level' => 3, 'added_by' => 0, 'cdate' => now(), 'udate' => now(), 'status' => 1,
        ]);

        $this->post('/login', ['username' => 'LegacyReseller1', 'password' => 'wrong'])->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post('/login', ['username' => 'LegacyReseller1', 'password' => 'legacy-pw'])->assertRedirect(route('reseller.dashboard'));
        $this->assertAuthenticatedAs(User::find($id));
        $this->assertSame(sha1('DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi' . 'legacy-pw'), User::find($id)->password, 'hash untouched');
    }
}
