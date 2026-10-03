@extends('layouts.app')

@section('title', 'Manage Users - EasyLogics')

@section('content')
<style>
    .label { display:inline-block; padding:2px 8px; border-radius:3px; font-size:11px; font-weight:600; color:#fff; }
    .label-success { background:#27ae60; }
    .label-default { background:#999; }
    .btn-xs { padding:3px 8px; font-size:11px; }
    .btn-default { background:#e7e7e7; color:#333; }
    .text-center { text-align:center; }
</style>

<div class="card">
    <div class="page-header">
        <h2><i class="fa fa-users"></i> User List</h2>
        <a href="{{ route('reseller.users.create') }}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add New User</a>
    </div>

    <div class="table-responsive">
        <table id="datable_1" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($subUsers as $sub)
                @php
                    $u = $userMap[$sub->user_id] ?? null;
                    $uName = $u && $u->full_name ? $u->full_name : ($u && isset($u->username) ? $u->username : 'N/A');
                    $uMobile = $u && isset($u->mobile) ? $u->mobile : '-';
                    $uEmail = $u && $u->email ? $u->email : '-';
                    $uStatus = $u && isset($u->status) ? $u->status : 0;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $uName }}</td>
                    <td>{{ $uMobile }}</td>
                    <td>{{ $uEmail }}</td>
                    <td>
                        @if($uStatus)
                            <span class="label label-success">Active</span>
                        @else
                            <span class="label label-default">Inactive</span>
                        @endif
                    </td>
                    <td>{{ date('d M Y', strtotime((string) $sub->created)) }}</td>
                    <td>
                        <a href="{{ route('reseller.users.edit', $sub->id) }}" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                        @if($uStatus)
                        <form method="POST" action="{{ route('reseller.users.deactivate', $sub->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-xs" title="Deactivate"><i class="fa fa-ban"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No users found. Click "Add New User" to create one.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
@if($subUsers->isNotEmpty())
<script>
    $(function () { $('#datable_1').DataTable(); });
</script>
@endif
@endsection
