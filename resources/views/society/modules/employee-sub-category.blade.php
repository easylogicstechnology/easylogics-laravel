@extends('layouts.app')
@section('title', 'Employee Sub Categories')
@section('content')
<div class="page-header">
    <h2>Employee Sub Categories</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="POST" action="{{ route('society.employeeSubCategory') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $editItem->id ?? '' }}">
        <div class="grid-4" style="align-items:end;">
            <div class="form-group">
                <label>Employee Category <span class="required">*</span></label>
                <select name="emp_category_id" class="form-control" required>
                    <option value="">Select</option>
                    @foreach($categories as $catId => $catName)
                    <option value="{{ $catId }}" {{ ($editItem && $editItem->emp_category_id == $catId) ? 'selected' : '' }}>{{ $catName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Employee Sub Category <span class="required">*</span></label>
                <input type="text" name="emp_sub_category_name" class="form-control" value="{{ $editItem->emp_sub_category_name ?? old('emp_sub_category_name') }}" placeholder="Enter Sub Category" required>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Sub Category' : 'Add Sub Category' }}</button>
                @if($editItem)
                <a href="{{ route('society.employeeSubCategory') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
                @endif
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Category</th>
                    <th>Employee Sub Category</th>
                    <th style="width:100px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->category->emp_category_name ?? '' }}</td>
                    <td>{{ $item->emp_sub_category_name }}</td>
                    <td>
                        <a href="{{ route('society.employeeSubCategory', $item->id) }}" title="Edit" style="margin-right:10px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteEmployeeSubCategory', $item->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this sub category?')">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:none; border:none; color:#d9534f; cursor:pointer;" title="Delete">&#10005;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center; color:#999;">No employee sub categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
