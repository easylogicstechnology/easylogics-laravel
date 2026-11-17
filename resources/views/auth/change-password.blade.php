@extends('layouts.app')
@section('title', 'My Account')
@section('content')
<div class="page-header"><h2>My Account</h2></div>
<div class="card" style="max-width:460px;">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <h3 style="font-size:16px; margin:0 0 8px;">E-mail for password reset</h3>
    <form method="POST" action="{{ route('account.email.update') }}" autocomplete="off" style="margin-bottom:22px;">
        @csrf
        <div class="form-group">
            <label for="email">E-mail ID @if($email)<span style="color:#137333; font-weight:normal;">(saved: {{ $email }})</span>@else<span style="color:#c5221f; font-weight:normal;">(not saved yet)</span>@endif</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="email_current_password">Current password (to confirm)</label>
            <input type="password" class="form-control" id="email_current_password" name="current_password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary">Save E-mail</button>
    </form>

    <h3 style="font-size:16px; margin:0 0 8px;">Change password</h3>
    <form method="POST" action="{{ route('account.password.update') }}" autocomplete="off">
        @csrf
        <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="{{ Auth::user()->username }}" readonly>
        </div>
        <div class="form-group">
            <label for="current_password">Current password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label for="password">New password (at least 8 characters)</label>
            <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="password_confirmation">New password again</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-success">Change Password</button>
    </form>
    <p style="font-size:12px; color:#777; margin-top:12px;">Forgot your password later? On the login box use <b>Forgot password?</b>, type your username and this e-mail - a code comes to the e-mail.</p>
</div>
@endsection
