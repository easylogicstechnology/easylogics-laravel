@extends('layouts.app')
@section('title', 'Building Identity')
@section('content')
<div class="page-header">
    <h2>Building Identity</h2>
</div>

<div class="card">
    <h3>Add Building</h3>
    <form method="POST" action="{{ route('society.buildingIdentity') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Building Name <span class="required">*</span></label>
                <input type="text" name="building_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Number of Flats</label>
                <input type="number" name="num_flats" class="form-control" value="0">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Add Building</button>
    </form>
</div>

<div class="card" style="margin-top: 16px;">
    <h3>Buildings List</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Building Name</th>
                <th>No. of Flats</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buildings as $i => $b)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $b->building_name }}</td>
                <td>{{ $b->num_flats }}</td>
                <td>{{ $b->status == 1 ? 'Active' : 'Inactive' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; color:#999;">No buildings found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
