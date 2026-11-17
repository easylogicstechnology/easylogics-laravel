@extends('layouts.page')

@section('title', 'Forgot Password')
@section('heading', 'Forgot Password')
@section('subheading', 'Reseller login: get a 6-digit code on your registered e-mail.')

@section('content')
    @include('auth._reset-styles')
    <div class="pw-box">
        @if (session('error'))<div class="pw-msg pw-err">{{ session('error') }}</div>@endif
        <form method="POST" action="{{ route('password.code') }}">
            @csrf
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Your reseller username">
            <button type="submit">Send code to my e-mail</button>
        </form>
        <p class="pw-note">The code goes to the e-mail address saved on your account. No e-mail on the account? Please contact Easy Logics.</p>
        <p class="pw-note"><a href="{{ route('password.reset.form') }}">I already have a code</a> &middot; <a href="{{ url('/') }}">Back to login</a></p>
    </div>
@endsection
