@extends('layouts.page')

@section('title', 'Forgot Password')
@section('heading', 'Forgot Password')
@section('subheading', 'Enter your e-mail ID - a 6-digit code comes to it.')

@section('content')
    @include('auth._reset-styles')
    <div class="pw-box">
        @if (session('error'))<div class="pw-msg pw-err">{{ session('error') }}</div>@endif
        <form method="POST" action="{{ route('password.code') }}">
            @csrf
            <label for="email">E-mail ID</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="The e-mail saved on your account">
            <button type="submit">Send code</button>
        </form>
        <p class="pw-note">Works for every login - Admin, Reseller, team user, Society and Member - when this e-mail is already saved on the account. No e-mail saved yet? Log in once and add it on <b>My Account</b>, or ask your admin / reseller.</p>
        <p class="pw-note"><a href="{{ route('password.reset.form') }}">I already have a code</a> &middot; <a href="{{ url('/') }}">Back to login</a></p>
    </div>
@endsection
