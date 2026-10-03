@extends('layouts.app')

@section('title', 'Societies List - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Society Listing</h2>
    <a href="{{ route('admin.societies.create') }}" class="btn btn-primary">Add Society</a>
</div>

<div class="card">
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User Name</th>
                    <th>Society Name</th>
                    <th>Society Code</th>
                    <th>Role</th>
                    <th>Created Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($societyData as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->societies->first()->society_name ?? '' }}</td>
                    <td>{{ $user->societies->first()->society_code ?? '' }}</td>
                    <td>{{ $user->role }}</td>
                    <td>{{ $user->cdate ? date('d/m/Y H:i:s', strtotime($user->cdate)) : '' }}</td>
                    <td>
                        <a href="{{ route('admin.societies.create', $user->id) }}" class="btn btn-primary btn-sm" title="Edit">Edit</a>
                        <form method="POST" action="{{ route('admin.societies.destroy', $user->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete #{{ $user->id }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#999;">No societies found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
