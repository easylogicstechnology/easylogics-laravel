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

/*
 * Set up the e-mail that sends the Forgot password codes, on the server:
 *     php artisan mail:setup
 * Asks only for the Gmail App Password (hidden), writes the gitignored .env.secrets (kept by every deploy), sends a
 * test mail, rebuilds the config cache, and can save the e-mail on the admin login. Nothing is printed of the password.
 */
Artisan::command('mail:setup {email=akhileshchoudhary843@gmail.com} {--admin=admin : username whose login gets this e-mail}', function (string $email) {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Not a valid e-mail address.');
        return 1;
    }
    $this->info("Sender e-mail: {$email} (Gmail, smtp.gmail.com:465)");
    $this->line('Gmail App Password: myaccount.google.com > Security > 2-Step Verification ON > App passwords > Create.');
    // the web terminal wraps a paste in [200~ ... [201~ - drop that, and the spaces Google shows in the password
    $pass = preg_replace(['/\e?\[20[01]~/', '/\s+/'], '', (string) $this->secret('Paste or type the 16-letter App Password (hidden)'));
    if (strlen($pass) !== 16) {
        $this->error('An App Password has exactly 16 letters (got ' . strlen($pass) . '). Nothing was changed.');
        return 1;
    }

    $settings = [
        'MAIL_MAILER' => 'smtp', 'MAIL_SCHEME' => 'smtps', 'MAIL_HOST' => 'smtp.gmail.com', 'MAIL_PORT' => '465',
        'MAIL_USERNAME' => $email, 'MAIL_PASSWORD' => $pass, 'MAIL_FROM_ADDRESS' => $email, 'MAIL_FROM_NAME' => '"Easy Logics"',
        'SESSION_PATH' => '/',
    ];

    // 1. try the settings first: a test mail with exactly these values
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.scheme' => 'smtps', 'mail.mailers.smtp.host' => 'smtp.gmail.com', 'mail.mailers.smtp.port' => 465,
        'mail.mailers.smtp.username' => $email, 'mail.mailers.smtp.password' => $pass,
        'mail.from.address' => $email, 'mail.from.name' => 'Easy Logics',
    ]);
    app('mail.manager')->forgetMailers();
    try {
        \Illuminate\Support\Facades\Mail::mailer('smtp')->raw(
            "Easy Logics: e-mail for password reset codes is working.\n\nSent by php artisan mail:setup on " . gethostname() . '.',
            fn ($m) => $m->to($email)->subject('Easy Logics test mail')
        );
    } catch (\Throwable $e) {
        $this->error('Gmail did not accept the mail: ' . mb_substr($e->getMessage(), 0, 300));
        $this->line('Usually: wrong App Password, or 2-Step Verification is off. Nothing was changed.');
        return 1;
    }
    $this->info("Test mail sent to {$email} - check the inbox (and Spam).");

    // 2. write .env.secrets, keeping any other key already in it
    $file = base_path('.env.secrets');
    $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : [];
    $lines = array_values(array_filter($lines, fn ($l) => !preg_match('/^\s*(' . implode('|', array_keys($settings)) . ')\s*=/', $l)));
    foreach ($settings as $k => $v) {
        $lines[] = "{$k}={$v}";
    }
    file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL);
    @chmod($file, 0640);
    @chown($file, fileowner(base_path()));
    @chgrp($file, filegroup(base_path()));
    $this->info('Saved .env.secrets (not in git, kept by every deploy).');

    // 3. rebuild the cached config in a fresh process so the website reads the new file
    $out = [];
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(base_path('artisan')) . ' config:cache 2>&1', $out, $code);
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(base_path('artisan')) . ' config:show mail.default 2>&1', $show);
    $this->line(trim(implode(' ', $show)));
    if ($code !== 0) {
        $this->warn('config:cache said: ' . implode(' ', $out));
    }

    // 4. the admin login's e-mail (Forgot password sends the code only to an e-mail saved on the login)
    $admin = \App\Models\User::where('username', (string) $this->option('admin'))->first();
    if ($admin) {
        $current = (string) \Illuminate\Support\Facades\DB::table('users')->where('id', $admin->id)->value('email');
        if (strtolower(trim($current)) === $email) {
            $this->info("Login '{$admin->username}' already has {$email} saved.");
        } elseif ($this->confirm("Save {$email} on login '{$admin->username}' (now: " . ($current ?: 'empty') . ')?', true)) {
            \Illuminate\Support\Facades\DB::table('users')->where('id', $admin->id)->update(['email' => $email]);
            $this->info("Saved. Forgot password for '{$admin->username}' now sends the code to {$email}.");
        }
    }
    $this->info('Done. Try Forgot password on the website now.');
    return 0;
})->purpose('Set up Gmail for Forgot password codes (writes .env.secrets, sends a test mail)');
