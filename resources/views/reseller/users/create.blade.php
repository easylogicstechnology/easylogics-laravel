@extends('layouts.app')

@section('title', 'Create User - EasyLogics')

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
    .text-danger { color: #e74c3c; }
    .btn-default { background:#e7e7e7; color:#333; }
    .user-cols { display:flex; flex-wrap:wrap; gap:30px; }
    .user-cols > div { flex:1 1 420px; min-width:0; }
    .user-cols .form-control { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
</style>

<div class="card">
    <div class="page-header">
        <h2><i class="fa fa-user-plus"></i> Create User &amp; Assign Permissions</h2>
        <a href="{{ route('reseller.users.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Users</a>
    </div>

    <form method="POST" action="{{ route('reseller.users.store') }}" autocomplete="off">
        @csrf
        <input type="text" style="display:none;" name="fakeusername">
        <input type="password" style="display:none;" name="fakepassword">
        <div class="user-cols">
            <div>
                <h5 style="font-weight:bold;margin-bottom:20px;"><i class="fa fa-user"></i> User Information</h5>
                <div class="form-group">
                    <label class="control-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="full_name" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label class="control-label">Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label class="control-label">Mobile Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="mobile" required placeholder="Enter mobile number" maxlength="10" pattern="[0-9]{10}">
                </div>
                <div class="form-group">
                    <label class="control-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password" required placeholder="Enter password" minlength="4" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="control-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="password_confirmation" required placeholder="Confirm password" minlength="4" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="control-label">Status</label>
                    <div>
                        <label class="status-toggle" style="display:inline-block;">
                            <input type="checkbox" name="status" value="1" checked>
                            <span class="status-slider"></span>
                        </label>
                        <span style="margin-left:10px;font-weight:bold;color:#27ae60;">Active</span>
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
                                <td><input type="checkbox" name="Permission[{{ $mod . '_' . $act }}]" value="1"></td>
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
        <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> Create User</button>
    </form>
</div>
@endsection
