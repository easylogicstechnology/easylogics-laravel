<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\Member;
use App\Models\Reseller;
use App\Models\ResellerSociety;
use App\Models\ResellerSubUser;
use App\Models\UserLogin;
use App\Services\FinancialYearService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectByRole($request);
        }

        // The login form lives in a modal on the landing page; a failed login lands here.
        return app(HomeController::class)->index($request);
    }

    public function login(Request $request)
    {
        $request->session()->put('db_connection', 'mysql');

        $loginField = $request->input('username');
        $password = $request->input('password');

        // CakePHP FormAuthenticate::_checkFields(): a missing/empty (or non-string) username or password is
        // just a failed login - the same "Invalid username or password" message, no field validation.
        foreach ([$loginField, $password] as $value) {
            if (!is_string($value) || $value === '') {
                return redirect()->route('login')
                    ->with('error', 'Invalid username or password')
                    ->withInput($request->only('username'));
            }
        }

        $user = \App\Models\User::where('username', $loginField)->first();

        if (!$user) {
            \Log::info('LOGIN FAIL: No user found for: ' . $loginField);
            return redirect()->route('login')
                ->with('error', 'Invalid username or password')
                ->withInput($request->only('username'));
        }

        $authenticated = Auth::attempt(['username' => $user->username, 'password' => $password]);

        if (!$authenticated) {
            \Log::info('LOGIN FAIL: Auth::attempt failed for username=' . $user->username);
            return redirect()->route('login')
                ->with('error', 'Invalid username or password')
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Only a team login carries a reseller_sub_users row (permissions) in its session
        if ($user->role !== 'SubReseller') {
            $request->session()->forget('sub_reseller');
        }

        if ($user->role === 'Society') {
            $assignment = ResellerSociety::active()->where('societie_id', $user->id)->first();

            if ($assignment && $assignment->reseller_id) {
                $expiry = Reseller::where('user_id', $assignment->reseller_id)->value('subscription_expiry');

                if (!empty($expiry) && $expiry < date('Y-m-d')) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')
                        ->with('error', "Your access is temporarily suspended because your reseller's subscription has expired. Please contact your reseller to renew.");
                }
            }
        }

        // DB WRITE: INSERT into user_logins — matches CakePHP behavior
        UserLogin::create([
            'user_id' => $user->id,
            'ipaddress' => $request->ip(),
            // The login time is recorded in IST (the app itself runs in UTC).
            'time' => now('Asia/Kolkata')->format('Y-m-d H:i:s'),
        ]);

        // A reseller's team login acts for its parent reseller (CakePHP: Auth.sub_reseller). Its reseller_sub_users
        // row - permissions and allowed societies - is kept in the session, see ResellerContext / User::hasPermission.
        if ($user->role === 'SubReseller') {
            $subUserData = ResellerSubUser::where('user_id', $user->id)->first();

            if (!$subUserData) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Your account is not linked to a reseller. Please contact your reseller.');
            }

            $request->session()->put('sub_reseller', $subUserData->toArray());
        }

        if ($user->role === 'Member') {
            $memberData = Member::where('user_id', $user->id)->first();

            if ($memberData) {
                $request->session()->put('member', $memberData->toArray());
                $request->session()->put('society_id', $memberData->society_id);
            }
        }

        $societyId = $this->getEffectiveSocietyId($request);
        if ($societyId && in_array($user->role, ['Society', 'Member'], true)) {
            app(FinancialYearService::class)->loadForSociety($request, $societyId);
        }

        return $this->redirectByRole($request)
            ->with('info', 'Welcome, ' . $user->username);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(Request $request)
    {
        $user = Auth::user();

        return match ($user->role) {
            'Admin' => redirect()->route('admin.dashboard'),
            'Society' => redirect()->route('society.dashboard'),
            'Reseller', 'SubReseller' => redirect()->route('reseller.dashboard'),
            'Member' => redirect()->route('member.dashboard'),
            // An authenticated user with no matching role must not be sent back to
            // /login: that route redirects an authenticated session straight back
            // here (guest middleware), which loops forever (ERR_TOO_MANY_REDIRECTS).
            // Logging out first breaks the loop.
            default => tap(redirect()->route('login'), function () use ($request) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }),
        };
    }

    private function getEffectiveSocietyId(Request $request): ?int
    {
        $user = Auth::user();

        if ($user->role === 'Society') {
            return $user->id;
        }

        if ($user->role === 'Member') {
            return $request->session()->get('society_id');
        }

        return null;
    }
}
