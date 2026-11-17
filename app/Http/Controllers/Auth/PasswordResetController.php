<?php

namespace App\Http\Controllers\Auth;

use App\Hashing\LegacyCakeHasher;
use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Forgot password for a reseller (and its team logins): a 6-digit code goes to the e-mail on the account, the code
 * plus a new password resets the login and signs the person straight in.
 *
 * The code lives only in the file cache (hashed, 10 minutes, 5 tries) - no table, no migration. Messages never say
 * whether a username exists, so the page cannot be used to find accounts.
 */
class PasswordResetController extends Controller
{
    /** Roles that may reset their own password here. */
    private const ROLES = ['Reseller', 'SubReseller'];

    private const CODE_MINUTES = 10;

    private const MAX_TRIES = 5;

    private const GENERIC_SENT = 'If this username belongs to a reseller account with an e-mail address, a 6-digit code has been sent to that e-mail. It is valid for 10 minutes.';

    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $username = trim((string) $request->input('username'));
        if ($username === '') {
            return back()->with('error', 'Please enter your username.');
        }

        // max 5 code requests per IP and 3 per username in 15 minutes
        $ipKey = 'pwreset-ip:' . $request->ip();
        $userKey = 'pwreset-user:' . strtolower($username);
        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($userKey, 3)) {
            return back()->withInput()->with('error', 'Too many requests. Please try again after 15 minutes.');
        }
        RateLimiter::hit($ipKey, 900);
        RateLimiter::hit($userKey, 900);

        $user = User::where('username', $username)->first();
        $email = $user ? $this->emailFor($user) : null;

        if ($user && in_array($user->role, self::ROLES, true) && (int) $user->status === 1 && $email) {
            $code = (string) random_int(100000, 999999);
            Cache::store('file')->put($this->cacheKey($user->id), [
                'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
                'tries' => 0,
            ], now()->addMinutes(self::CODE_MINUTES));

            try {
                Mail::raw(
                    "Hello {$user->username},\n\nYour Easy Logics password reset code is: {$code}\n\n"
                    . 'It is valid for ' . self::CODE_MINUTES . " minutes. If you did not ask for this, ignore this e-mail - your password stays the same.\n\nEasy Logics Technology",
                    function ($m) use ($email) {
                        $m->to($email)->subject('Easy Logics password reset code');
                    }
                );
            } catch (\Throwable $e) {
                report($e);
                Cache::store('file')->forget($this->cacheKey($user->id));

                return back()->withInput()->with('error', 'The e-mail could not be sent right now. Please try again later or contact Easy Logics.');
            }
            Log::warning('Password reset code sent for user #' . $user->id);
        }

        $request->session()->put('pwreset_username', $username);

        return redirect()->route('password.reset.form')->with('success', self::GENERIC_SENT);
    }

    public function showResetForm(Request $request)
    {
        return view('auth.reset-password', ['username' => (string) $request->session()->get('pwreset_username', '')]);
    }

    public function reset(Request $request)
    {
        $username = trim((string) $request->input('username'));
        $code = preg_replace('/\D/', '', (string) $request->input('code'));
        $password = (string) $request->input('password');

        $back = fn (string $msg) => back()->withInput($request->only('username'))->with('error', $msg);

        if ($username === '' || strlen($code) !== 6) {
            return $back('Enter your username and the 6-digit code from the e-mail.');
        }
        if (strlen($password) < 8) {
            return $back('The new password must have at least 8 characters.');
        }
        if ($password !== (string) $request->input('password_confirmation')) {
            return $back('The two passwords do not match.');
        }
        if (RateLimiter::tooManyAttempts('pwreset-verify:' . $request->ip(), 10)) {
            return $back('Too many wrong codes. Please try again after 15 minutes.');
        }

        $user = User::where('username', $username)->first();
        $key = $user ? $this->cacheKey($user->id) : null;
        $entry = $key ? Cache::store('file')->get($key) : null;

        if (!$user || !in_array($user->role, self::ROLES, true) || !is_array($entry)) {
            RateLimiter::hit('pwreset-verify:' . $request->ip(), 900);

            return $back('The code is wrong or has expired. Please ask for a new code.');
        }
        if (!hash_equals($entry['hash'], hash_hmac('sha256', $code, (string) config('app.key')))) {
            RateLimiter::hit('pwreset-verify:' . $request->ip(), 900);
            $entry['tries']++;
            if ($entry['tries'] >= self::MAX_TRIES) {
                Cache::store('file')->forget($key);

                return $back('Too many wrong codes. Please ask for a new code.');
            }
            Cache::store('file')->put($key, $entry, now()->addMinutes(self::CODE_MINUTES));

            return $back('The code is wrong. ' . (self::MAX_TRIES - $entry['tries']) . ' tries left.');
        }

        Cache::store('file')->forget($key);
        DB::table('users')->where('id', $user->id)->update(['password' => (new LegacyCakeHasher())->make($password)]);
        Log::warning('Password reset by e-mail code for user #' . $user->id);
        $request->session()->forget('pwreset_username');

        // Sign in through the normal login (subscription check, team-login session, user_logins row).
        $request->merge(['username' => $user->username, 'password' => $password]);

        return app(LoginController::class)->login($request);
    }

    /** The e-mail of the login, or of its reseller record. */
    private function emailFor(User $user): ?string
    {
        $email = trim((string) ($user->email ?? ''));
        if ($email === '' && $user->role === 'Reseller') {
            $email = trim((string) Reseller::where('user_id', $user->id)->value('email'));
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function cacheKey(int $userId): string
    {
        return 'pwreset-code:' . $userId;
    }
}
