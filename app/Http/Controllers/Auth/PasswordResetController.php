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
 * Forgot password for every login (Admin, Reseller, team login, Society, Member): the person types an e-mail; when it
 * is already saved on a login, a 6-digit code goes to it, and the code plus a new password resets that login and signs
 * the person straight in. A code never goes to an address that is not saved on the account.
 *
 * The code lives only in the file cache (hashed, 10 minutes, 5 tries) - no table, no migration. Messages never say
 * whether an e-mail is known, so the page cannot be used to find accounts.
 */
class PasswordResetController extends Controller
{
    /** Every login can reset its own password; the code only ever goes to an e-mail already saved on that login. */
    private const ROLES = ['Admin', 'Reseller', 'SubReseller', 'Society', 'Member'];

    private const CODE_MINUTES = 10;

    private const MAX_TRIES = 5;

    private const GENERIC_SENT = 'If this e-mail is saved on an account, a 6-digit code has been sent to it. It is valid for 10 minutes. Check the Spam folder too.';

    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withInput()->with('error', 'Please enter a valid e-mail address.');
        }

        // max 5 code requests per IP and 3 per e-mail in 15 minutes
        $ipKey = 'pwreset-ip:' . $request->ip();
        $mailKey = 'pwreset-mail:' . sha1($email);
        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($mailKey, 3)) {
            return back()->withInput()->with('error', 'Too many requests. Please try again after 15 minutes.');
        }
        RateLimiter::hit($ipKey, 900);
        RateLimiter::hit($mailKey, 900);

        // Only logins that already have this e-mail saved (a code never goes to a new, unknown address).
        $users = $this->usersWithEmail($email);
        if ($users) {
            $code = (string) random_int(100000, 999999);
            Cache::store('file')->put($this->cacheKey($email), [
                'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
                'tries' => 0,
                'user_ids' => array_map(fn ($u) => (int) $u->id, $users),
            ], now()->addMinutes(self::CODE_MINUTES));

            $names = implode(', ', array_map(fn ($u) => $u->username . ' (' . $u->role . ')', $users));
            $which = count($users) > 1
                ? "\nThis e-mail is saved on more than one login: {$names}.\nType the username you want to reset on the reset page.\n"
                : "\nLogin: {$names}\n";
            try {
                Mail::raw(
                    "Hello,\n\nYour Easy Logics password reset code is: {$code}\n{$which}\n"
                    . 'It is valid for ' . self::CODE_MINUTES . " minutes. If you did not ask for this, ignore this e-mail - your password stays the same.\n\nEasy Logics Technology",
                    function ($m) use ($email) {
                        $m->to($email)->subject('Easy Logics password reset code');
                    }
                );
            } catch (\Throwable $e) {
                report($e);
                Cache::store('file')->forget($this->cacheKey($email));

                return back()->withInput()->with('error', 'The e-mail could not be sent right now. Please try again later or contact Easy Logics.');
            }
            Log::warning('Password reset code sent for user(s) #' . implode(',', array_map(fn ($u) => $u->id, $users)));
        }

        $request->session()->put('pwreset_email', $email);

        return redirect()->route('password.reset.form')->with('success', self::GENERIC_SENT);
    }

    public function showResetForm(Request $request)
    {
        return view('auth.reset-password', ['email' => (string) $request->session()->get('pwreset_email', '')]);
    }

    public function reset(Request $request)
    {
        $email = strtolower(trim((string) $request->input('email')));
        $username = trim((string) $request->input('username'));
        $code = preg_replace('/\D/', '', (string) $request->input('code'));
        $password = (string) $request->input('password');

        $back = fn (string $msg) => back()->withInput($request->only('email', 'username'))->with('error', $msg);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($code) !== 6) {
            return $back('Enter your e-mail and the 6-digit code from the e-mail.');
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

        $key = $this->cacheKey($email);
        $entry = Cache::store('file')->get($key);
        if (!is_array($entry)) {
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

        // Which login: the only one with this e-mail, or the username typed when the e-mail has several logins.
        $candidates = User::whereIn('id', $entry['user_ids'] ?? [])->get();
        if ($candidates->count() > 1) {
            $user = $candidates->first(fn ($u) => strcasecmp($u->username, $username) === 0);
            if (!$user) {
                return $back('This e-mail is saved on more than one login. Type the username (listed in the e-mail) in the Username box.');
            }
        } else {
            $user = $candidates->first();
        }
        if (!$user) {
            return $back('The code is wrong or has expired. Please ask for a new code.');
        }

        Cache::store('file')->forget($key);
        DB::table('users')->where('id', $user->id)->update(['password' => (new LegacyCakeHasher())->make($password)]);
        Log::warning('Password reset by e-mail code for user #' . $user->id);
        $request->session()->forget('pwreset_email');

        // Sign in through the normal login (subscription check, team-login session, user_logins row).
        $request->merge(['username' => $user->username, 'password' => $password]);

        return app(LoginController::class)->login($request);
    }

    /** Active logins (any role; an Admin row may be status 0, as in CakePHP) that have this e-mail saved. */
    private function usersWithEmail(string $email): array
    {
        $ids = DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->pluck('id')->all();
        $ids = array_merge($ids,
            DB::table('resellers')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->pluck('user_id')->all(),
            DB::table('societies')->whereRaw('LOWER(TRIM(email_id)) = ?', [$email])->pluck('user_id')->all(),
            DB::table('members')->whereRaw('LOWER(TRIM(member_email)) = ?', [$email])->whereNotNull('user_id')->pluck('user_id')->all()
        );
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }

        return User::whereIn('id', $ids)->get()
            ->filter(fn ($u) => in_array($u->role, self::ROLES, true) && ($u->role === 'Admin' || (int) $u->status === 1))
            // the e-mail must really belong to this login (e.g. a reseller-table e-mail only counts for that reseller)
            ->filter(fn ($u) => in_array($email, $this->emailsFor($u), true))
            ->sortBy('id')->take(10)->values()->all();
    }

    /**
     * E-mails saved for this login, lower case: the login's own (users.email, set on My Account), plus the reseller
     * Profile, the society's e-mail or the member's e-mail, depending on the role.
     */
    private function emailsFor(User $user): array
    {
        $list = [(string) ($user->email ?? '')];
        if ($user->role === 'Reseller') {
            $list[] = (string) Reseller::where('user_id', $user->id)->value('email');
        } elseif ($user->role === 'Society') {
            $list[] = (string) DB::table('societies')->where('user_id', $user->id)->value('email_id');
        } elseif ($user->role === 'Member') {
            $list = array_merge($list, DB::table('members')->where('user_id', $user->id)->pluck('member_email')->map(fn ($e) => (string) $e)->all());
        }

        return array_values(array_unique(array_filter(array_map(fn ($e) => strtolower(trim($e)), $list),
            fn ($e) => (bool) filter_var($e, FILTER_VALIDATE_EMAIL))));
    }

    private function cacheKey(string $email): string
    {
        return 'pwreset-code:' . sha1($email);
    }
}
