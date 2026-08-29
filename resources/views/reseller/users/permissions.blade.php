@extends('layouts.app')

@section('title', 'User Permissions - EasyLogics')

@section('content')
<style>
    .perm-header {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 50%, #a8e6cf 100%);
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
    .perm-header::after {
        content: '';
        position: absolute;
        right: -20px;
        top: -20px;
        width: 160px;
        height: 160px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }
    .perm-header h2 {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .perm-header p {
        font-size: 13px;
        opacity: 0.85;
        margin: 0;
    }
    .perm-header .header-icon {
        font-size: 48px;
        opacity: 0.3;
        position: relative;
        z-index: 1;
    }

    .user-selector-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 20px;
        margin-bottom: 24px;
    }
    .user-selector-card label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
    }
    .user-selector-card select {
        width: 100%;
        max-width: 400px;
        padding: 10px 14px;
        border: 1.5px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        background: #fafbfc;
        color: #333;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .user-selector-card select:focus {
        outline: none;
        border-color: #27ae60;
        box-shadow: 0 0 0 3px rgba(39,174,96,0.12);
        background: #fff;
    }

    .form-card {
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

    .perm-info {
        background: #e8f8f0;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 13px;
        color: #1a7a4a;
        margin: 16px;
    }

    .perm-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }
    .perm-table thead th {
        padding: 12px 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        border-bottom: 2px solid #eee;
        background: transparent;
    }
    .perm-table thead th:first-child {
        text-align: left;
        padding-left: 16px;
    }
    .perm-table thead th.col-add { color: #27ae60; }
    .perm-table thead th.col-edit { color: #3498db; }
    .perm-table thead th.col-delete { color: #e74c3c; }
    .perm-table thead th.col-generate { color: #8e44ad; }
    .perm-table thead th.col-view { color: #e67e22; }

    .perm-table tbody tr {
        transition: background 0.15s;
    }
    .perm-table tbody tr:hover {
        background: #f8f9ff;
    }
    .perm-table tbody td {
        padding: 12px 8px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
    }
    .perm-table tbody td:first-child {
        text-align: left;
        padding-left: 16px;
    }

    .module-name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: #2c3e50;
    }
    .module-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: #fff;
        flex-shrink: 0;
    }

    .perm-check {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .perm-check input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .perm-check .checkmark {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 2px solid #ddd;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .perm-check .checkmark svg {
        width: 14px;
        height: 14px;
        fill: none;
        stroke: #fff;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
        opacity: 0;
        transition: opacity 0.15s;
    }
    .perm-check input:checked + .checkmark {
        border-color: transparent;
    }
    .perm-check input:checked + .checkmark svg {
        opacity: 1;
    }
    .perm-check.check-add input:checked + .checkmark { background: #27ae60; }
    .perm-check.check-edit input:checked + .checkmark { background: #3498db; }
    .perm-check.check-delete input:checked + .checkmark { background: #e74c3c; }
    .perm-check.check-generate input:checked + .checkmark { background: #8e44ad; }
    .perm-check.check-view input:checked + .checkmark { background: #e67e22; }

    .form-actions {
        display: flex;
        gap: 12px;
        padding: 20px;
        border-top: 1px solid #f0f0f0;
        justify-content: space-between;
        align-items: center;
    }
    .btn-save {
        background: linear-gradient(135deg, #27ae60, #219a52);
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
        box-shadow: 0 2px 8px rgba(39,174,96,0.3);
    }
    .btn-save:hover {
        opacity: 0.9;
    }

    .select-all-bar {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    .select-all-bar label {
        font-size: 13px;
        color: #666;
        cursor: pointer;
        user-select: none;
    }
    .select-all-bar label input {
        margin-right: 6px;
    }

    .no-user-msg {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .no-user-msg .icon {
        font-size: 48px;
        margin-bottom: 12px;
    }
    .no-user-msg p {
        font-size: 15px;
        margin: 0;
    }
</style>

<div class="page-header">
    <h2>User Permissions</h2>
</div>

@if(session('success'))
    <div class="alert alert-success" style="background:#e8f8f0; color:#1a7a4a; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="perm-header">
    <div>
        <h2>Assign Permissions</h2>
        <p>Select a user and set module-wise permissions</p>
    </div>
    <div class="header-icon">&#128737;</div>
</div>

<div class="user-selector-card">
    <label>&#128100; Select User</label>
    <select id="userSelector" onchange="if(this.value) window.location='{{ route('reseller.users.permissions') }}?user_id='+this.value; else window.location='{{ route('reseller.users.permissions') }}';">
        <option value="">-- Select a User --</option>
        @foreach($users as $u)
            <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                {{ $u->name }} ({{ $u->email }})
            </option>
        @endforeach
    </select>
    @if($users->isEmpty())
        <p style="color:#999; font-size:13px; margin-top:8px;">No users found. <a href="{{ route('reseller.users.create') }}" style="color:#27ae60;">Create a user first</a>.</p>
    @endif
</div>

@if($selectedUserId)
<form method="POST" action="{{ route('reseller.users.savePermissions') }}">
    @csrf
    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">

    <div class="form-card">
        <div class="form-card-header">
            <div class="icon-circle" style="background:linear-gradient(135deg,#27ae60,#219a52);">
                <span style="color:#fff;">&#128737;</span>
            </div>
            <h3>Module Permissions</h3>
        </div>

        <div class="perm-info">
            Check the permissions you want to assign to this user.
        </div>

        <div style="padding:0 16px 16px; overflow-x:auto;">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th style="min-width:140px;">Module</th>
                        <th class="col-add">Add</th>
                        <th class="col-edit">Edit</th>
                        <th class="col-delete">Delete</th>
                        <th class="col-generate">Generate</th>
                        <th class="col-view">View</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $moduleIcons = [
                            'dashboard'       => ['icon' => '&#128202;', 'color' => '#3498db', 'label' => 'Dashboard'],
                            'software'        => ['icon' => '&#128187;', 'color' => '#27ae60', 'label' => 'Software'],
                            'members'         => ['icon' => '&#128101;', 'color' => '#e67e22', 'label' => 'Members'],
                            'bills'           => ['icon' => '&#128196;', 'color' => '#9b59b6', 'label' => 'Bills'],
                            'payments'        => ['icon' => '&#128179;', 'color' => '#e74c3c', 'label' => 'Payments'],
                            'settlement'      => ['icon' => '&#9878;',  'color' => '#1abc9c', 'label' => 'Settlement'],
                            'reports'         => ['icon' => '&#128200;', 'color' => '#f39c12', 'label' => 'Reports'],
                            'settings'        => ['icon' => '&#9881;',  'color' => '#7f8c8d', 'label' => 'Settings'],
                            'bill_generated'  => ['icon' => '&#128203;', 'color' => '#8e44ad', 'label' => 'Bill Generated'],
                            'member_payment'  => ['icon' => '&#128176;', 'color' => '#16a085', 'label' => 'Member Payment'],
                            'general_receipt' => ['icon' => '&#128220;', 'color' => '#2c3e50', 'label' => 'General Receipt'],
                        ];
                    @endphp
                    @foreach($modules as $module)
                        @php
                            $mi = $moduleIcons[$module] ?? ['icon' => '&#128196;', 'color' => '#95a5a6', 'label' => ucfirst($module)];
                            $existing = $userPermissions[$module] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div class="module-name">
                                    <div class="module-icon" style="background:{{ $mi['color'] }};">{!! $mi['icon'] !!}</div>
                                    {{ $mi['label'] }}
                                </div>
                            </td>
                            @foreach($permissionTypes as $perm)
                                @php
                                    $checkClass = str_replace('can_', 'check-', $perm);
                                    $isChecked = $existing && $existing->$perm == 1;
                                @endphp
                                <td>
                                    <label class="perm-check {{ $checkClass }}">
                                        <input type="checkbox"
                                               name="permissions[{{ $module }}][{{ $perm }}]"
                                               value="1"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <span class="checkmark">
                                            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        </span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <div class="select-all-bar">
                <label>
                    <input type="checkbox" id="selectAllPerms">
                    Select All Permissions
                </label>
                <label>
                    <input type="checkbox" id="selectAllView">
                    Select All View
                </label>
            </div>
            <button type="submit" class="btn-save">&#10004; Save Permissions</button>
        </div>
    </div>
</form>
@else
    @if(!$users->isEmpty())
    <div class="form-card">
        <div class="no-user-msg">
            <div class="icon">&#128100;</div>
            <p>Select a user from the dropdown above to assign permissions</p>
        </div>
    </div>
    @endif
@endif

<script>
var selectAllPerms = document.getElementById('selectAllPerms');
if (selectAllPerms) {
    selectAllPerms.addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.perm-table input[type="checkbox"]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    });
}

var selectAllView = document.getElementById('selectAllView');
if (selectAllView) {
    selectAllView.addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('.perm-check.check-view input[type="checkbox"]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
        }
    });
}
</script>
@endsection
