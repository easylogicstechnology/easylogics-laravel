<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Developer-only admin password reset, run on the server over SSH:
 *     php artisan admin:password admin
 * The password is typed at a hidden prompt (never in code, git, or shell history) and saved with the
 * same CakePHP sha1+salt hash the login uses. There is no web page that changes the admin password,
 * so only someone with server access can change it.
 */
Artisan::command('admin:password {username=admin}', function (string $username) {
    $user = \App\Models\User::where('username', $username)->first();

    if (!$user) {
        $this->error("No user with username '{$username}'.");
        $admins = \App\Models\User::where('role', 'Admin')->pluck('username')->all();
        $this->line($admins ? 'Admin usernames in this database: ' . implode(', ', $admins) : 'This database has no user with role Admin.');
        return 1;
    }

    $this->info("Found user #{$user->id} '{$user->username}', role '{$user->role}', status '" . ($user->status ?? '-') . "'.");
    if ($user->role !== 'Admin') {
        $this->warn("Role is not 'Admin', so this login will not open the admin panel.");
    }

    $password = (string) $this->secret('New password (hidden)');
    if (strlen($password) < 8) {
        $this->error('Use at least 8 characters. Nothing was changed.');
        return 1;
    }
    if ($password !== (string) $this->secret('Type it again')) {
        $this->error('The two passwords do not match. Nothing was changed.');
        return 1;
    }

    \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)
        ->update(['password' => (new \App\Hashing\LegacyCakeHasher())->make($password)]);

    $this->info("Password updated for '{$user->username}'. Log in at the site with this username and the new password.");
    return 0;
})->purpose('Developer-only: reset a login password from the server (default user: admin)');
