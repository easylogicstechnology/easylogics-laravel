@extends('layouts.app')

@section('title', 'Personal Identity - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Personal Identity</h2>
</div>

@if(session('info'))
<div class="alert alert-success">{{ session('info') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('reseller.profile') }}">
        @csrf

        <div class="grid-2" style="gap:16px;">
            <div class="form-group">
                <label>First Name <span style="color:red;">*</span></label>
                <input type="text" name="firstname" class="form-control" value="{{ old('firstname', $profile->firstname ?? '') }}" required>
                @error('firstname')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Last Name <span style="color:red;">*</span></label>
                <input type="text" name="lastname" class="form-control" value="{{ old('lastname', $profile->lastname ?? '') }}" required>
                @error('lastname')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="grid-2" style="gap:16px; margin-top:12px;">
            <div class="form-group">
                <label>Email <span style="color:red;">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $profile->email ?? '') }}" required>
                @error('email')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Mobile No. <span style="color:red;">*</span></label>
                <input type="text" name="contact_no" class="form-control" value="{{ old('contact_no', $profile->contact_no ?? '') }}" required>
                @error('contact_no')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="grid-2" style="gap:16px; margin-top:12px;">
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $profile->date_of_birth ?? '') }}">
            </div>
            <div class="form-group">
                <label>License No.</label>
                <input type="text" name="license_no" class="form-control" value="{{ old('license_no', $profile->license_no ?? '') }}">
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2">{{ old('address', $profile->address ?? '') }}</textarea>
        </div>

        <div class="grid-3" style="gap:16px; margin-top:12px;">
            <div class="form-group">
                <label>Country</label>
                <select name="country" class="form-control" id="countrySelect">
                    <option value="0">-- Select Country --</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->country_id }}" {{ (old('country', $profile->country ?? 0) == $c->country_id) ? 'selected' : '' }}>{{ $c->country_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>State</label>
                <select name="state" class="form-control" id="stateSelect">
                    <option value="0">-- Select State --</option>
                    @foreach($states as $s)
                        <option value="{{ $s->state_id }}" {{ (old('state', $profile->state ?? 0) == $s->state_id) ? 'selected' : '' }}>{{ $s->state_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $profile->city ?? '') }}">
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label>Job Role</label>
            <input type="text" name="job_role" class="form-control" value="{{ old('job_role', $profile->job_role ?? '') }}">
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
</div>
@endsection
