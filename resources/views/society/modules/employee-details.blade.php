@extends('layouts.app')
@section('title', 'Employee Details')
@section('content')
<div class="page-header">
    <h2>Employee Details</h2>
    <a href="{{ route('society.addEmployee') }}" class="btn btn-success" style="margin-left:auto;">+ Add Employee</a>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Employee Code</th>
                    <th>Joining Date</th>
                    <th>Date Of Leaving</th>
                    <th style="width:100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->category->emp_category_name ?? '' }}</td>
                    <td>{{ $item->emp_name }}</td>
                    <td>{{ $item->emp_code }}</td>
                    <td>{{ $item->joining_date }}</td>
                    <td>{{ $item->date_of_leaving }}</td>
                    <td>
                        <a href="{{ route('society.addEmployee', $item->id) }}" title="Edit" style="margin-right:10px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteEmployee', $item->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this employee?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none; border:none; color:#d9534f; cursor:pointer;" title="Delete">&#10005;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; color:#999;">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
