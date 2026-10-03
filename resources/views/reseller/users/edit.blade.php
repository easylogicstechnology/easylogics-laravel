@extends('layouts.app')

@section('title', 'Edit User - EasyLogics')

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
    .status-toggle { position: relative; display: inline-block; width: 50px; height: 26px; }
    .status-toggle input { opacity: 0; width: 0; height: 0; }
    .status-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 26px; }
    .status-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    .status-toggle input:checked + .status-slider { background-color: #27ae60; }
    .status-toggle input:checked + .status-slider:before { transform: translateX(24px); }
    .text-success { color: #27ae60; }
    .text-danger { color: #e74c3c; }
    .btn-default { background:#e7e7e7; color:#333; }
    .user-cols { display:flex; flex-wrap:wrap; gap:30px; }
    .user-cols > div { flex:1 1 420px; min-width:0; }
    .user-cols .form-control { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
</style>

<div class="card">
    <div class="page-header">
        <h2><i class="fa fa-user"></i> Edit User - {{ optional($userData)->full_name ?: optional($userData)->username }}</h2>
        <a href="{{ route('reseller.users.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Users</a>
    </div>

    <form method="POST" action="{{ route('reseller.users.update', $subUser->id) }}" autocomplete="off">
        @csrf
        @method('PUT')
        <div class="user-cols">
            <div>
                <h5 style="font-weight:bold;margin-bottom:20px;"><i class="fa fa-user"></i> User Information</h5>
                <div class="form-group">
                    <label class="control-label">Full Name</label>
                    <input type="text" class="form-control" name="full_name" value="{{ optional($userData)->full_name }}" placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label class="control-label">Email Address</label>
                    <input type="email" class="form-control" name="email" value="{{ optional($userData)->email }}" placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label class="control-label">Mobile Number</label>
                    <input type="text" class="form-control" name="mobile" value="{{ optional($userData)->mobile }}" placeholder="Enter mobile number" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="control-label">Username</label>
                    <input type="text" class="form-control" value="{{ optional($userData)->username }}" disabled>
                </div>
                <div class="form-group">
                    <label class="control-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" class="form-control" name="password" placeholder="Enter new password" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="control-label">Status</label>
                    <div>
                        <label class="status-toggle" style="display:inline-block;">
                            <input type="checkbox" name="status" value="1" {{ optional($userData)->status == 1 ? 'checked' : '' }}>
                            <span class="status-slider"></span>
                        </label>
                        <span style="margin-left:10px;font-weight:bold;" class="{{ optional($userData)->status == 1 ? 'text-success' : 'text-danger' }}">{{ optional($userData)->status == 1 ? 'Active' : 'Inactive' }}</span>
                    </div>
                </div>
            </div>
            <div>
                <h5 style="font-weight:bold;margin-bottom:20px;"><i class="fa fa-shield"></i> Set Permissions</h5>
                <p class="text-muted" style="margin-bottom:15px;">Check the permissions you want to assign to this user.</p>
                <div class="table-responsive">
                    <table class="table table-bordered perm-table">
                        <thead>
                            <tr style="background:#f5f5f5;">
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
                            <tr>
                                <td class="module-name">{{ ucwords(str_replace('_', ' ', $mod)) }}</td>
                                @foreach($permActions as $act)
                                @php $key = $mod . '_' . $act; @endphp
                                <td><input type="checkbox" name="Permission[{{ $key }}]" value="1" {{ !empty($subUser->$key) ? 'checked' : '' }}></td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <hr style="margin:20px 0;border:0;border-top:1px solid #eee;">
        <a href="{{ route('reseller.users.index') }}" class="btn btn-default"><i class="fa fa-times"></i> Cancel</a>
        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Update User</button>
    </form>
</div>
@endsection
