@extends('layouts.app')
@section('title', ($editItem ? 'Edit' : 'Add') . ' Employee')
@section('content')
<div class="page-header">
    <h2>{{ $editItem ? 'Edit' : 'Add' }} Employee</h2>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addEmployee', $editItem->id ?? null) }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Employee Name <span class="required">*</span></label>
                <input type="text" name="emp_name" class="form-control" value="{{ $editItem->emp_name ?? old('emp_name') }}" placeholder="Enter Employee Name" required>
            </div>
            <div class="form-group">
                <label>Employee Code</label>
                <input type="text" name="emp_code" class="form-control" value="{{ $editItem->emp_code ?? old('emp_code') }}" placeholder="Employee Code">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Employee Category <span class="required">*</span></label>
                <select name="emp_category_id" class="form-control" id="empCategorySelect" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $catId => $catName)
                    <option value="{{ $catId }}" {{ ($editItem && $editItem->emp_category_id == $catId) ? 'selected' : '' }}>{{ $catName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Employee Sub Category</label>
                <select name="emp_sub_category_id" class="form-control" id="empSubCategorySelect">
                    <option value="">Select Sub Category</option>
                    @foreach($subCategories as $subId => $subName)
                    <option value="{{ $subId }}" {{ ($editItem && $editItem->emp_sub_category_id == $subId) ? 'selected' : '' }}>{{ $subName }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Joining Date <span class="required">*</span></label>
                <input type="date" name="joining_date" class="form-control" value="{{ $editItem->joining_date ?? old('joining_date') }}" required>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select</option>
                    <option value="Male" {{ ($editItem && $editItem->gender == 'Male') ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ ($editItem && $editItem->gender == 'Female') ? 'selected' : '' }}>Female</option>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="{{ $editItem->date_of_birth ?? old('date_of_birth') }}">
            </div>
            <div class="form-group">
                <label>Marital Status</label>
                <select name="marrital_status" class="form-control">
                    <option value="">Select</option>
                    <option value="married" {{ ($editItem && $editItem->marrital_status == 'married') ? 'selected' : '' }}>Married</option>
                    <option value="unmarrired" {{ ($editItem && $editItem->marrital_status == 'unmarrired') ? 'selected' : '' }}>Unmarried</option>
                    <option value="divorcee" {{ ($editItem && $editItem->marrital_status == 'divorcee') ? 'selected' : '' }}>Divorcee</option>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Date of Leaving</label>
                <input type="date" name="date_of_leaving" class="form-control" value="{{ $editItem->date_of_leaving ?? old('date_of_leaving') }}">
            </div>
            <div class="form-group">
                <label>Religion</label>
                <select name="religion" class="form-control">
                    <option value="">Select</option>
                    <option value="hindu" {{ ($editItem && $editItem->religion == 'hindu') ? 'selected' : '' }}>Hindu</option>
                    <option value="muslim" {{ ($editItem && $editItem->religion == 'muslim') ? 'selected' : '' }}>Muslim</option>
                    <option value="cathelic" {{ ($editItem && $editItem->religion == 'cathelic') ? 'selected' : '' }}>Catholic</option>
                </select>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>Qualification</label>
                <input type="text" name="qualification" class="form-control" value="{{ $editItem->qualification ?? old('qualification') }}" placeholder="Enter Qualification">
            </div>
            <div class="form-group">
                <label>PAN No.</label>
                <input type="text" name="pan_no" class="form-control" value="{{ $editItem->pan_no ?? old('pan_no') }}" placeholder="Enter PAN No.">
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>GSTIN No.</label>
                <input type="text" name="gstin_no" class="form-control" value="{{ $editItem->gstin_no ?? old('gstin_no') }}" placeholder="Enter GSTIN No.">
            </div>
        </div>
        <div style="margin-top:12px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Employee' : 'Add Employee' }}</button>
            <a href="{{ route('society.employeeDetails') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('empCategorySelect').addEventListener('change', function() {
    var categoryId = this.value;
    var subSelect = document.getElementById('empSubCategorySelect');
    subSelect.innerHTML = '<option value="">Select Sub Category</option>';
    if (!categoryId) return;
    fetch('{{ route("society.getEmployeeSubCategories") }}?category_id=' + categoryId)
        .then(r => r.json())
        .then(data => {
            for (var id in data) {
                var opt = document.createElement('option');
                opt.value = id;
                opt.textContent = data[id];
                subSelect.appendChild(opt);
            }
        });
});
</script>
@endsection
