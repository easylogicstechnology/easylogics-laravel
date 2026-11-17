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
            <label for="email">E-mail ID</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            <label for="code">6-digit code</label>
            <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required placeholder="______" autocomplete="one-time-code">
            <label for="username">Username <span style="font-weight:normal; color:#888;">(only if the e-mail says this e-mail has more than one login)</span></label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" autocomplete="username">
            <label for="password">New password (at least 8 characters)</label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
            <label for="password_confirmation">New password again</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
            <button type="submit">Reset password and sign in</button>
        </form>
        <p class="pw-note">Code not received? Check the Spam folder, or <a href="{{ route('password.request') }}">ask for a new code</a>.</p>
    </div>
@endsection
