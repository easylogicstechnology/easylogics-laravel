@extends('layouts.app')

@section('title', 'Permissions - EasyLogics')

@section('content')
<style>
    .perm-table th { text-align: center; font-weight: bold; font-size: 13px; }
    .perm-table td { text-align: center; vertical-align: middle; }
    .perm-table td:first-child, .perm-table th:first-child { text-align: left; }
    .perm-table .module-name { font-weight: 600; font-size: 14px; }
    .perm-table input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
    .th-add { color: #27ae60; }
    .th-edit { color: #2980b9; }
    .th-delete { color: #e74c3c; }
    .th-generate { color: #8e44ad; }
    .th-update { color: #16a085; }
    .th-view { color: #e67e22; }
    .text-center { text-align: center; }
    .perm-select { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
</style>

<div class="card">
    <div class="page-header">
        <h2><i class="fa fa-shield"></i> User Permissions</h2>
    </div>
    <div class="form-group" style="max-width:400px;">
        <label class="control-label"><i class="fa fa-user"></i> Select User</label>
        <select class="perm-select" onchange="if(this.value){ window.location='{{ route('reseller.users.permissions') }}?user_id='+this.value; } else { window.location='{{ route('reseller.users.permissions') }}'; }">
            <option value="">-- Select a User --</option>
            @foreach($subUsers as $sub)
                @php
                    $u = $userMap[$sub->user_id] ?? null;
                    $uName = $u && $u->full_name ? $u->full_name : ($u && isset($u->username) ? $u->username : 'N/A');
                    $uEmail = $u && isset($u->email) ? $u->email : '';
                @endphp
                <option value="{{ $sub->user_id }}" {{ (!empty($selectedUserId) && $selectedUserId == $sub->user_id) ? 'selected' : '' }}>
                    {{ $uName . ($uEmail ? ' (' . $uEmail . ')' : '') }}
                </option>
            @endforeach
        </select>
        @if($subUsers->isEmpty())
        <p class="text-muted" style="margin-top:8px;">No users found. <a href="{{ route('reseller.users.create') }}">Create a user first</a>.</p>
        @endif
    </div>
</div>

@if(!empty($selectedUserId) && !empty($selectedSubUser))
<div class="card">
    <div class="page-header">
        <h2><i class="fa fa-shield"></i> Module Permissions</h2>
    </div>
    <p class="text-muted">Check the permissions you want to assign to this user.</p>
    <form method="POST" action="{{ route('reseller.users.savePermissions') }}">
        @csrf
        <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
        <div class="table-responsive">
            <table class="table table-bordered perm-table">
                <thead>
                    <tr>
                        <th>MODULE</th>
                        <th class="th-add">ADD</th>
                        <th class="th-edit">EDIT</th>
                        <th class="th-delete">DELETE</th>
                        <th class="th-generate">GENERATE</th>
                        <th class="th-update">UPDATE</th>
                        <th class="th-view">VIEW</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($modules as $mod)
                    @php $modLabel = $moduleLabels[$mod] ?? ucwords(str_replace('_', ' ', $mod)); @endphp
                    <tr>
                        <td class="module-name">{{ $modLabel }}</td>
                        @foreach($permActions as $act)
                        @php $key = $mod . '_' . $act; @endphp
                        <td><input type="checkbox" name="Permission[{{ $key }}]" value="1" {{ !empty($selectedSubUser->$key) ? 'checked' : '' }}></td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <hr style="margin:20px 0;border:0;border-top:1px solid #eee;">
        <h6 style="font-weight:600;font-size:14px;"><i class="fa fa-building"></i> Allowed Societies</h6>
        <p class="text-muted">Check which of your societies this user can access under "My Societies". Leave all unchecked to give them no society access.</p>
        @if(!empty($resellerSocietiesList))
            @php $selectedSocietyIdsStr = array_map('strval', $selectedSocietyIds); @endphp
            <div style="margin-bottom:10px;">
                <label style="font-weight:normal;"><input type="checkbox" id="selectAllSocieties"> Select All Societies</label>
            </div>
            <table class="table table-bordered" style="max-width:600px;">
                <thead>
                    <tr><th>Society</th><th style="width:100px;text-align:center;">Access</th></tr>
                </thead>
                <tbody>
                @foreach($resellerSocietiesList as $soc)
                    <tr>
                        <td>{{ (string) $soc->society_name }}</td>
                        <td style="text-align:center;"><input type="checkbox" class="society-checkbox" name="society_ids[]" value="{{ (string) $soc->id }}" {{ in_array((string) $soc->id, $selectedSocietyIdsStr, true) ? 'checked' : '' }}></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">You have no societies assigned yet.</p>
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:15px;">
            <div>
                <label style="margin-right:15px;font-weight:normal;"><input type="checkbox" id="selectAllPerms"> Select All Permissions</label>
                <label style="font-weight:normal;"><input type="checkbox" id="selectAllView"> Select All View</label>
            </div>
            <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Save Permissions</button>
        </div>
    </form>
</div>
@elseif($subUsers->isNotEmpty())
<div class="card text-center" style="padding:40px;">
    <i class="fa fa-user" style="font-size:48px;color:#ccc;"></i>
    <p class="text-muted" style="margin-top:15px;">Select a user from the dropdown above to assign permissions.</p>
</div>
@endif
@endsection

@section('scripts')
<script>
var selectAllPerms = document.getElementById('selectAllPerms');
if (selectAllPerms) {
    selectAllPerms.addEventListener('change', function() {
        var boxes = document.querySelectorAll('.perm-table input[type="checkbox"]');
        for (var i = 0; i < boxes.length; i++) { boxes[i].checked = this.checked; }
    });
}
var selectAllView = document.getElementById('selectAllView');
if (selectAllView) {
    selectAllView.addEventListener('change', function() {
        var boxes = document.querySelectorAll('.perm-table td:last-child input[type="checkbox"]');
        for (var i = 0; i < boxes.length; i++) { boxes[i].checked = this.checked; }
    });
}
var selectAllSocieties = document.getElementById('selectAllSocieties');
if (selectAllSocieties) {
    selectAllSocieties.addEventListener('change', function() {
        var boxes = document.querySelectorAll('.society-checkbox');
        for (var i = 0; i < boxes.length; i++) { boxes[i].checked = this.checked; }
    });
}
</script>
@endsection
