@extends('layouts.app')

@section('title', 'Manage Users - EasyLogics')

@section('content')
<style>
    .users-header {
        background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
        border-radius: 12px;
        padding: 20px 24px;
        color: #fff;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .users-header h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 4px 0;
    }
    .users-header p {
        font-size: 13px;
        opacity: 0.85;
        margin: 0;
    }

    .user-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .user-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .user-card-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        text-transform: none;
        letter-spacing: 0;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }
    .users-table thead th {
        background: #f8f9fa;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #666;
        border-bottom: 2px solid #eee;
    }
    .users-table tbody tr {
        transition: background 0.15s;
    }
    .users-table tbody tr:hover {
        background: #f8f9ff;
    }
    .users-table tbody td {
        padding: 12px 14px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        margin-right: 10px;
        vertical-align: middle;
    }
    .user-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-name-cell .uname {
        font-weight: 600;
        color: #2c3e50;
    }
    .user-name-cell .uemail {
        font-size: 12px;
        color: #999;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    .perm-dots {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    .perm-dot {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        background: #eef2ff;
        color: #4a5568;
    }
    .action-btns {
        display: flex;
        gap: 6px;
    }
    .btn-edit {
        background: #3498db;
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: opacity 0.15s;
    }
    .btn-edit:hover { opacity: 0.85; }
    .btn-del {
        background: #e74c3c;
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .btn-del:hover { opacity: 0.85; }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    .empty-state .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.4;
    }
    .empty-state p {
        margin: 0 0 16px 0;
        font-size: 15px;
    }
</style>

<div class="page-header">
    <h2>Manage Users</h2>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="users-header">
    <div>
        <h2>User Management</h2>
        <p>Manage your users and their system permissions</p>
    </div>
    <a href="{{ route('reseller.users.create') }}" class="btn btn-success" style="border-radius:8px; padding:10px 20px; font-weight:600;">+ Create New User</a>
</div>

<div class="user-card">
    <div class="user-card-header">
        <h3>All Users ({{ $users->count() }})</h3>
        <input type="text" id="userSearch" class="form-control" placeholder="Search users..." oninput="filterUsers()" style="max-width:220px; margin:0; padding:8px 12px; font-size:13px; border-radius:8px;">
    </div>

    @if($users->count() > 0)
    <div style="overflow-x:auto;">
        <table class="users-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Mobile</th>
                    <th>Permissions</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                @foreach($users as $index => $user)
                <tr data-search="{{ strtolower($user->name . ' ' . $user->email . ' ' . $user->mobile) }}">
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="user-name-cell">
                            <div class="user-avatar">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</div>
                            <div>
                                <div class="uname">{{ $user->name ?? '-' }}</div>
                                <div class="uemail">{{ $user->email ?? $user->username }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->mobile ?? '-' }}</td>
                    <td>
                        <div class="perm-dots">
                            @php
                                $perms = \App\Models\UserPermission::where('user_id', $user->id)->pluck('module')->toArray();
                            @endphp
                            @forelse($perms as $p)
                                <span class="perm-dot">{{ ucfirst($p) }}</span>
                            @empty
                                <span style="color:#ccc; font-size:12px;">No permissions</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        @if($user->status == 1)
                            <span class="status-badge status-active">Active</span>
                        @else
                            <span class="status-badge status-inactive">Inactive</span>
                        @endif
                    </td>
                    <td style="font-size:12px; color:#999;">{{ $user->cdate ? date('d M Y', strtotime($user->cdate)) : '-' }}</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('reseller.users.edit', $user->id) }}" class="btn-edit">Edit</a>
                            <form method="POST" action="{{ route('reseller.users.destroy', $user->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-del">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <div class="empty-icon">&#128101;</div>
        <p>No users created yet</p>
        <a href="{{ route('reseller.users.create') }}" class="btn btn-primary" style="border-radius:8px; padding:10px 20px;">+ Create Your First User</a>
    </div>
    @endif
</div>

<script>
function filterUsers() {
    var query = document.getElementById('userSearch').value.toLowerCase().trim();
    var rows = document.querySelectorAll('#userTableBody tr');
    rows.forEach(function(row) {
        var search = row.getAttribute('data-search') || '';
        row.style.display = search.indexOf(query) !== -1 ? '' : 'none';
    });
}
</script>
@endsection
