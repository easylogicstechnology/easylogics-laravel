@extends('layouts.app')
@section('title', 'Wing Identity')
@section('content')
<div class="page-header">
    <h2>Wing Identity</h2>
</div>

<div class="card">
    <h3>Add Wing</h3>
    <form method="POST" action="{{ route('society.wingIdentity') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Building <span class="required">*</span></label>
                <select name="building_id" class="form-control" required>
                    <option value="">-- Select Building --</option>
                    @foreach($buildings as $b)
                        <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Wing Name <span class="required">*</span></label>
                <input type="text" name="wing_name" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-success">Add Wing</button>
    </form>
</div>

<div class="card" style="margin-top: 16px;">
    <h3>Wings List</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Wing Name</th>
                <th>Building</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($wings as $i => $w)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $w->wing_name }}</td>
                <td>{{ $w->building->building_name ?? '-' }}</td>
                <td>{{ $w->status == 1 ? 'Active' : 'Inactive' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; color:#999;">No wings found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
