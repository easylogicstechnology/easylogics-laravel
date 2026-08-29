@extends('layouts.app')

@section('title', 'Create User - EasyLogics')

@section('content')
<style>
    .create-user-header {
        background: linear-gradient(135deg, #4a3f9f 0%, #6c5ce7 50%, #a29bfe 100%);
        border-radius: 12px;
        padding: 24px 28px;
        color: #fff;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    .create-user-header::after {
        content: '';
        position: absolute;
        right: -20px;
        top: -20px;
        width: 160px;
        height: 160px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .create-user-header h2 {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .create-user-header p {
        font-size: 13px;
        opacity: 0.85;
        margin: 0;
    }
    .create-user-header .header-icon {
        font-size: 48px;
        opacity: 0.3;
        position: relative;
        z-index: 1;
    }

    .form-card {
        max-width: 560px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .form-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-card-header .icon-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .form-card-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        text-transform: none;
        letter-spacing: 0;
    }
    .form-card-body {
        padding: 20px;
    }

    .form-field {
        margin-bottom: 18px;
    }
    .form-field:last-child {
        margin-bottom: 0;
    }
    .form-field label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }
    .form-field label .req {
        color: #e74c3c;
    }
    .form-field .input-wrap {
        position: relative;
    }
    .form-field .input-wrap input {
        width: 100%;
        padding: 10px 14px 10px 40px;
        border: 1.5px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #fafbfc;
    }
    .form-field .input-wrap input:focus {
        outline: none;
        border-color: #6c5ce7;
        box-shadow: 0 0 0 3px rgba(108,92,231,0.12);
        background: #fff;
    }
    .form-field .input-wrap .field-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 16px;
        pointer-events: none;
    }

    .status-toggle {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 8px;
    }
    .toggle-switch {
        position: relative;
        width: 52px;
        height: 28px;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background: #ccc;
        border-radius: 28px;
        transition: 0.3s;
    }
    .toggle-slider::before {
        content: '';
        position: absolute;
        height: 22px;
        width: 22px;
        left: 3px;
        bottom: 3px;
        background: #fff;
        border-radius: 50%;
        transition: 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-switch input:checked + .toggle-slider {
        background: #27ae60;
    }
    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(24px);
    }
    .toggle-label {
        font-size: 13px;
        font-weight: 600;
        color: #27ae60;
    }
    .toggle-label.inactive {
        color: #e74c3c;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        padding: 20px;
        border-top: 1px solid #f0f0f0;
        justify-content: flex-end;
    }
    .btn-cancel {
        background: #f5f5f5;
        color: #666;
        border: 1px solid #ddd;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s;
    }
    .btn-cancel:hover {
        background: #eee;
    }
    .btn-create {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: #fff;
        border: none;
        padding: 10px 28px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: opacity 0.15s;
        box-shadow: 0 2px 8px rgba(52,152,219,0.3);
    }
    .btn-create:hover {
        opacity: 0.9;
    }

    .error-text {
        color: #e74c3c;
        font-size: 12px;
        margin-top: 4px;
    }
</style>

<div class="page-header">
    <h2>Create User</h2>
</div>

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="create-user-header">
    <div>
        <h2>Create New User</h2>
        <p>Add a new user to your team</p>
    </div>
    <div class="header-icon">&#128100;</div>
</div>

<form method="POST" action="{{ route('reseller.users.store') }}">
    @csrf
    <div class="form-card">
            <div class="form-card-header">
                <div class="icon-circle" style="background:linear-gradient(135deg,#3498db,#2980b9);">
                    <span style="color:#fff;">&#128100;</span>
                </div>
                <h3>User Information</h3>
            </div>
            <div class="form-card-body">
                <div class="form-field">
                    <label>Full Name <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="field-icon">&#128100;</span>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Enter full name" required>
                    </div>
                    @error('name') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Email Address <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="field-icon">&#9993;</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" required>
                    </div>
                    @error('email') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Mobile Number <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="field-icon">&#128222;</span>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" placeholder="Enter mobile number" required>
                    </div>
                    @error('mobile') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="field-icon">&#128274;</span>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                    @error('password') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Confirm Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="field-icon">&#128274;</span>
                        <input type="password" name="password_confirmation" placeholder="Confirm password" required>
                    </div>
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <div class="status-toggle">
                        <label class="toggle-switch">
                            <input type="hidden" name="status" value="0">
                            <input type="checkbox" name="status" value="1" checked id="statusToggle">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label" id="statusLabel">Active</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('reseller.users.index') }}" class="btn-cancel">&#10005; Cancel</a>
                <button type="submit" class="btn-create">&#128100;+ Create User</button>
            </div>
    </div>
</form>

<script>
document.getElementById('statusToggle').addEventListener('change', function() {
    var label = document.getElementById('statusLabel');
    if (this.checked) {
        label.textContent = 'Active';
        label.className = 'toggle-label';
    } else {
        label.textContent = 'Inactive';
        label.className = 'toggle-label inactive';
    }
});
</script>
@endsection
