@extends('layouts.page')

@section('title', 'Reset Password')
@section('heading', 'Reset Password')
@section('subheading', 'Enter the code from your e-mail and choose a new password.')

@section('content')
    @include('auth._reset-styles')
    <div class="pw-box">
        @if (session('success'))<div class="pw-msg pw-ok">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="pw-msg pw-err">{{ session('error') }}</div>@endif
        <form method="POST" action="{{ route('password.reset') }}" autocomplete="off">
            @csrf
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username', $username) }}" required autocomplete="username">
            <label for="code">6-digit code</label>
            <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required placeholder="______" autocomplete="one-time-code">
            <label for="password">New password (at least 8 characters)</label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
            <label for="password_confirmation">New password again</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
            <button type="submit">Reset password and sign in</button>
        </form>
        <p class="pw-note">Code not received? Check the Spam folder, or <a href="{{ route('password.request') }}">ask for a new code</a>.</p>
    </div>
@endsection
