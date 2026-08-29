<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
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

        $stats = $this->getGlanceStats();

        return view('auth.login', compact('stats'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $request->session()->put('db_connection', 'mysql');

        $loginField = $request->input('username');
        $password = $request->input('password');

        $user = \App\Models\User::where('username', $loginField)
            ->orWhere('email', $loginField)
            ->first();

        if (!$user) {
            \Log::info('LOGIN FAIL: No user found for: ' . $loginField);
            return redirect()->route('login')
                ->with('error', 'Invalid username or password')
                ->withInput($request->only('username'));
        }

        \Log::info('LOGIN: Found user', [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'stored_hash' => $user->password,
            'computed_hash' => (new \App\Hashing\LegacyCakeHasher())->make($password),
            'hash_match' => (new \App\Hashing\LegacyCakeHasher())->check($password, $user->password),
        ]);

        $authenticated = Auth::attempt(['username' => $user->username, 'password' => $password]);

        if (!$authenticated) {
            \Log::info('LOGIN FAIL: Auth::attempt failed for username=' . $user->username);
            return redirect()->route('login')
                ->with('error', 'Invalid username or password')
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // DB WRITE: INSERT into user_logins — matches CakePHP behavior
        UserLogin::create([
            'user_id' => $user->id,
            'ipaddress' => $request->ip(),
            'time' => now(),
        ]);

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
            'Reseller', 'ResellerUser' => redirect()->route('reseller.dashboard'),
            'Member' => redirect()->route('member.dashboard'),
            default => redirect()->route('login'),
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

    private function getGlanceStats(): array
    {
        return [
            'societies' => DB::table('users')->where('access_level', 2)->count(),
            'members' => DB::table('members')->where('status', 1)->count(),
            'bills' => DB::table('member_bill_generates')->count(),
            'resellers' => DB::table('users')->where('access_level', 3)->count(),
            'buildings' => DB::table('buildings')->count(),
            'wings' => DB::table('wings')->count(),
        ];
    }
}
