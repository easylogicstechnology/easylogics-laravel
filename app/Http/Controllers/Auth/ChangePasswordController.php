<?php

namespace App\Http\Controllers\Auth;

use App\Hashing\LegacyCakeHasher;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * My Account: save my e-mail for password reset, and change my own password (both need the current password).
 * Not offered while a reseller is working inside a society (that session is the society's login, not the reseller's).
 */
class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        abort_if($request->session()->has('reseller_id'), 403, 'Go back to your reseller login to change your own password.');

        $email = (string) DB::table('users')->where('id', Auth::id())->value('email');

        return view('auth.change-password', compact('email'));
    }

    /** Save the e-mail the Forgot password code is sent to (users.email). */
    public function updateEmail(Request $request)
    {
        abort_if($request->session()->has('reseller_id'), 403, 'Go back to your reseller login to change your own e-mail.');

        $user = Auth::user();
        $email = strtolower(trim((string) $request->input('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            return back()->with('error', 'Please enter a valid e-mail address.');
        }
        if (!(new LegacyCakeHasher())->check((string) $request->input('current_password'), (string) DB::table('users')->where('id', $user->id)->value('password'))) {
            return back()->with('error', 'Your current password is not correct, the e-mail was not saved.');
        }

        DB::table('users')->where('id', $user->id)->update(['email' => $email]);
        Log::warning('E-mail for password reset changed by the user, user #' . $user->id);

        return back()->with('success', 'E-mail saved. A Forgot password code will be sent to ' . $email . '.');
    }

    public function update(Request $request)
    {
        abort_if($request->session()->has('reseller_id'), 403, 'Go back to your reseller login to change your own password.');

        $user = Auth::user();
        $hasher = new LegacyCakeHasher();
        $current = (string) $request->input('current_password');
        $new = (string) $request->input('password');

        if (!$hasher->check($current, (string) DB::table('users')->where('id', $user->id)->value('password'))) {
            return back()->with('error', 'Your current password is not correct.');
        }
        if (strlen($new) < 8) {
            return back()->with('error', 'The new password must have at least 8 characters.');
        }
        if ($new !== (string) $request->input('password_confirmation')) {
            return back()->with('error', 'The two new passwords do not match.');
        }
        if ($new === $current) {
            return back()->with('error', 'The new password must be different from the current one.');
        }

        DB::table('users')->where('id', $user->id)->update(['password' => $hasher->make($new)]);
        Log::warning('Password changed by the user, user #' . $user->id);
        $request->session()->regenerate();

        return back()->with('success', 'Your password has been changed. Use the new password from your next login.');
    }
}
