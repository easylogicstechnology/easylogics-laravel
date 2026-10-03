<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Admin login / authentication - behaviour checked against the CakePHP 2.10.22 flow
 * (UsersController::login/logout, AuthComponent, FormAuthenticate, AdminController::dashboard).
 *
 * Needs a scratch copy of the CakePHP database - it refuses to run against any database whose name does not
 * contain "scratch". Run with:
 *   DB_CONNECTION=mysql DB_DATABASE=<scratch db> vendor/bin/phpunit tests/Feature/AdminLoginTest.php
 */
class AdminLoginTest extends TestCase
{
    private const PREFIX = 'zzlogin_';

    /** CakePHP's Security.salt (app/Config/core.php) - kept literal on purpose, independent of LegacyCakeHasher */
    private const CAKE_SALT = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgsdsfdsfdaC9mi';

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
        DB::table('user_logins')->whereIn('user_id', $ids)->delete();
        DB::table('users')->whereIn('id', $ids)->delete();
    }

    /** Insert a user the way CakePHP would have stored it: password = sha1(Security.salt . password) */
    private function makeUser(string $name, string $password, string $role = 'Admin', int $status = 1): User
    {
        $accessLevel = ['Admin' => 1, 'Society' => 2, 'Reseller' => 3, 'SubReseller' => 3, 'ResellerUser' => 3, 'Member' => 4][$role];

        $id = DB::table('users')->insertGetId([
            'username' => self::PREFIX . $name,
            'password' => sha1(self::CAKE_SALT . $password),
            'role' => $role,
            'access_level' => $accessLevel,
            'added_by' => 1,
            'status' => $status,
            'cdate' => now(),
            'udate' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function attempt(string $name, ?string $password)
    {
        return $this->post('/login', ['username' => self::PREFIX . $name, 'password' => $password]);
    }

    public function test_correct_admin_credentials_log_in_and_land_on_the_dashboard(): void
    {
        $admin = $this->makeUser('admin1', 'Secret#123');

        $this->attempt('admin1', 'Secret#123')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('info', 'Welcome, ' . self::PREFIX . 'admin1');

        $this->assertAuthenticatedAs($admin);

        // user_logins row (user_id, ipaddress, time), like Cake's UserLogin->save()
        $this->assertSame(1, DB::table('user_logins')->where('user_id', $admin->id)->count());
    }

    public function test_wrong_password_fails(): void
    {
        $this->makeUser('admin1', 'Secret#123');

        $this->attempt('admin1', 'wrong')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Invalid username or password');

        $this->assertGuest();
    }

    public function test_wrong_username_fails(): void
    {
        $this->makeUser('admin1', 'Secret#123');

        $this->attempt('nobody', 'Secret#123')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Invalid username or password');

        $this->assertGuest();
    }

    public function test_empty_or_missing_credentials_are_a_plain_failed_login_not_field_validation(): void
    {
        $this->makeUser('admin1', 'Secret#123');

        foreach ([['', 'x'], [self::PREFIX . 'admin1', ''], ['', ''], [null, null]] as [$username, $password]) {
            $this->post('/login', ['username' => $username, 'password' => $password])
                ->assertRedirect(route('login'))
                ->assertSessionHas('error', 'Invalid username or password')
                ->assertSessionHasNoErrors();
        }

        $this->post('/login', [])->assertSessionHas('error', 'Invalid username or password');
        $this->assertGuest();
    }

    public function test_the_login_page_shows_the_failure_message_and_reopens_the_modal(): void
    {
        $this->attempt('nobody', 'x');

        $this->followingRedirects()->get('/login')
            ->assertOk()
            ->assertSee('Invalid username or password')
            ->assertSee('openLogin();', false);
    }

    /** Cake never looks at users.status when logging in (only Reseller/Society have separate lapse checks) */
    public function test_an_inactive_admin_can_still_log_in_exactly_like_cake(): void
    {
        $admin = $this->makeUser('inactive', 'Secret#123', 'Admin', 0);

        $this->attempt('inactive', 'Secret#123')->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_dashboard_opens_after_login(): void
    {
        $admin = $this->makeUser('admin1', 'Secret#123');

        $this->attempt('admin1', 'Secret#123');

        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_dashboard_without_login_is_refused_with_cakes_message(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'You must be logged in to view this page.');

        $this->followingRedirects()->get('/admin/dashboard')
            ->assertSee('You must be logged in to view this page.')
            ->assertSee('openLogin();', false);
    }

    public function test_dashboard_without_login_is_403_for_ajax_like_cake(): void
    {
        $this->get('/admin/dashboard', ['X-Requested-With' => 'XMLHttpRequest'])->assertForbidden();
    }

    public function test_logout_destroys_the_session_and_blocks_the_dashboard_again(): void
    {
        $this->makeUser('admin1', 'Secret#123');
        $this->attempt('admin1', 'Secret#123');
        $this->get(route('admin.dashboard'))->assertOk();
        $oldSessionId = session()->getId();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotSame($oldSessionId, session()->getId());

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    /** a hash written by CakePHP (sha1(salt . password)) verifies as-is, including a password with spaces */
    public function test_existing_cake_password_hashes_work_without_any_reset(): void
    {
        foreach (['plain', 'With Space', ' lead', 'trail ', 'Sp3c!al#@%&', '0'] as $i => $password) {
            $this->makeUser('old' . $i, $password);

            $this->attempt('old' . $i, $password)->assertRedirect(route('admin.dashboard'));
            $this->assertAuthenticated();

            $this->post('/logout');
            $this->assertGuest();
        }
    }

    public function test_the_password_is_compared_exactly_no_trimming_and_case_sensitive(): void
    {
        $this->makeUser('admin1', 'Secret#123');

        $this->attempt('admin1', ' Secret#123')->assertSessionHas('error');
        $this->assertGuest();

        $this->attempt('admin1', 'secret#123')->assertSessionHas('error');
        $this->assertGuest();
    }

    /** users.username uses a case-insensitive collation, in Cake and here alike */
    public function test_the_username_is_case_insensitive_like_cake(): void
    {
        $admin = $this->makeUser('admin1', 'Secret#123');

        $this->post('/login', ['username' => strtoupper(self::PREFIX . 'admin1'), 'password' => 'Secret#123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_multiple_admins_each_get_their_own_session(): void
    {
        $a = $this->makeUser('adminA', 'passA');
        $b = $this->makeUser('adminB', 'passB');

        $this->attempt('adminA', 'passB')->assertSessionHas('error');
        $this->assertGuest();

        $this->attempt('adminA', 'passA')->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($a);
        $this->post('/logout');

        $this->attempt('adminB', 'passB')->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($b);

        $this->assertSame(1, DB::table('user_logins')->where('user_id', $a->id)->count());
        $this->assertSame(1, DB::table('user_logins')->where('user_id', $b->id)->count());
    }

    public function test_an_already_logged_in_admin_opening_login_is_sent_to_the_dashboard(): void
    {
        $this->makeUser('admin1', 'Secret#123');
        $this->attempt('admin1', 'Secret#123');

        $this->get('/login')->assertRedirect('/');
        $this->get('/')->assertRedirect(route('admin.dashboard'));
    }

    /** AdminController::dashboard() sends a Society / Reseller on to their own dashboard; other roles are refused */
    public function test_admin_dashboard_redirects_society_and_reseller_and_refuses_others(): void
    {
        $this->actingAs($this->makeUser('soc', 'x', 'Society'))
            ->get('/admin/dashboard')->assertRedirect(route('society.dashboard'));

        $this->actingAs($this->makeUser('res', 'x', 'Reseller'))
            ->get('/admin/dashboard')->assertRedirect(route('reseller.dashboard'));

        foreach (['Member', 'SubReseller', 'ResellerUser'] as $i => $role) {
            $this->actingAs($this->makeUser('other' . $i, 'x', $role))
                ->get('/admin/dashboard')->assertForbidden();
        }
    }

    public function test_the_other_admin_pages_stay_admin_only(): void
    {
        $this->actingAs($this->makeUser('soc', 'x', 'Society'))
            ->get(route('admin.societies.index'))->assertForbidden();
    }

    public function test_admin_login_time_is_recorded_in_ist(): void
    {
        $admin = $this->makeUser('admin1', 'Secret#123');

        $this->attempt('admin1', 'Secret#123');

        $time = DB::table('user_logins')->where('user_id', $admin->id)->value('time');
        $this->assertNotNull($time);

        $ist = now('Asia/Kolkata');
        $this->assertEqualsWithDelta($ist->timestamp, \Carbon\Carbon::parse($time, 'Asia/Kolkata')->timestamp, 60);
        // 5h30 ahead of UTC, so it must not equal the UTC wall clock
        $this->assertNotSame(now('UTC')->format('Y-m-d H:i'), substr($time, 0, 16));
    }

    public function test_existing_admin_rows_are_not_touched_by_any_login_flow(): void
    {
        $snapshot = fn () => md5(json_encode(DB::table('users')->where('role', 'Admin')
            ->where('username', 'not like', self::PREFIX . '%')->orderBy('id')->get()));
        $before = $snapshot();

        $this->makeUser('admin1', 'Secret#123');
        $this->attempt('admin1', 'Secret#123');
        $this->post('/logout');
        $this->attempt('admin1', 'wrong');

        $this->assertSame($before, $snapshot());
    }

    public function test_clear_session_route_is_gone(): void
    {
        $this->get('/clear-session')->assertNotFound();
    }

    /** Cake: Session.timeout = 4320 minutes (3 days) */
    public function test_the_session_lifetime_matches_cake(): void
    {
        $this->assertSame(4320, (int) env('SESSION_LIFETIME', config('session.lifetime')));
    }

    public function test_no_password_hash_is_written_to_the_log(): void
    {
        $this->makeUser('admin1', 'Secret#123');
        $log = storage_path('logs/laravel.log');
        $before = is_file($log) ? filesize($log) : 0;

        $this->attempt('admin1', 'Secret#123');
        $this->attempt('admin1', 'wrong');

        clearstatcache();
        $added = is_file($log) ? substr((string) file_get_contents($log), $before) : '';
        $this->assertStringNotContainsString(sha1(self::CAKE_SALT . 'Secret#123'), $added);
        $this->assertStringNotContainsString(sha1(self::CAKE_SALT . 'wrong'), $added);
        $this->assertStringNotContainsString('stored_hash', $added);
    }
}
