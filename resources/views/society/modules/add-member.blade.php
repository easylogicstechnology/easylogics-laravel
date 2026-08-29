@extends('layouts.app')
@section('title', $member ? 'Edit Member' : 'Add Member')
@section('content')
<div class="page-header">
    <h2>{{ $member ? 'Edit Member' : 'Add Member' }}</h2>
    <a href="{{ route('society.memberIdentity') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addMember', $member->id ?? '') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Prefix</label>
                <select name="member_prefix" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach(['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Shri', 'Smt'] as $p)
                    <option value="{{ $p }}" {{ old('member_prefix', $member->member_prefix ?? '') == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Member Name <span class="required">*</span></label>
                <input type="text" name="member_name" class="form-control" value="{{ old('member_name', $member->member_name ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Flat/Shop No <span class="required">*</span></label>
                <input type="text" name="flat_no" class="form-control" value="{{ old('flat_no', $member->flat_no ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Building</label>
                <select name="building_id" class="form-control" id="buildingSelect">
                    <option value="">-- Select --</option>
                    @foreach($buildings as $bId => $bName)
                    <option value="{{ $bId }}" {{ old('building_id', $member->building_id ?? '') == $bId ? 'selected' : '' }}>{{ $bName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Wing</label>
                <select name="wing_id" class="form-control" id="wingSelect">
                    <option value="">-- Select --</option>
                    @foreach($wings as $w)
                    <option value="{{ $w->id }}" data-building="{{ $w->building_id }}" {{ old('wing_id', $member->wing_id ?? '') == $w->id ? 'selected' : '' }}>{{ $w->wing_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Floor No</label>
                <input type="text" name="floor_no" class="form-control" value="{{ old('floor_no', $member->floor_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Unit Type</label>
                <select name="unit_type" class="form-control">
                    <option value="R" {{ old('unit_type', $member->unit_type ?? '') == 'R' ? 'selected' : '' }}>Residential</option>
                    <option value="C" {{ old('unit_type', $member->unit_type ?? '') == 'C' ? 'selected' : '' }}>Commercial</option>
                </select>
            </div>
            <div class="form-group">
                <label>Area</label>
                <input type="number" step="0.01" name="area" class="form-control" value="{{ old('area', $member->area ?? '') }}">
            </div>
            <div class="form-group">
                <label>Carpet</label>
                <input type="number" step="0.01" name="carpet" class="form-control" value="{{ old('carpet', $member->carpet ?? '') }}">
            </div>
            <div class="form-group">
                <label>Commercial Area</label>
                <input type="number" step="0.01" name="commercial" class="form-control" value="{{ old('commercial', $member->commercial ?? '') }}">
            </div>
            <div class="form-group">
                <label>Residential Area</label>
                <input type="number" step="0.01" name="residential" class="form-control" value="{{ old('residential', $member->residential ?? '') }}">
            </div>
            <div class="form-group">
                <label>Terrace</label>
                <input type="number" step="0.01" name="terrace" class="form-control" value="{{ old('terrace', $member->terrace ?? '') }}">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="member_email" class="form-control" value="{{ old('member_email', $member->member_email ?? '') }}">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="member_phone" class="form-control" value="{{ old('member_phone', $member->member_phone ?? '') }}">
            </div>
            <div class="form-group">
                <label>Parking No</label>
                <input type="text" name="member_parking_no" class="form-control" value="{{ old('member_parking_no', $member->member_parking_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>GSTIN No</label>
                <input type="text" name="gstin_no" class="form-control" value="{{ old('gstin_no', $member->gstin_no ?? '') }}">
            </div>
        </div>

        <h3 style="margin:20px 0 10px; font-size:16px; color:#2c3e50;">Opening Balance</h3>
        <div class="grid-3">
            <div class="form-group">
                <label>Opening Principal</label>
                <input type="number" step="0.01" name="op_principal" class="form-control" value="{{ old('op_principal', $member->op_principal ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Opening Interest</label>
                <input type="number" step="0.01" name="op_interest" class="form-control" value="{{ old('op_interest', $member->op_interest ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Opening Tax</label>
                <input type="number" step="0.01" name="op_tax" class="form-control" value="{{ old('op_tax', $member->op_tax ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Penalty</label>
                <input type="number" step="0.01" name="penality" class="form-control" value="{{ old('penality', $member->penality ?? '0') }}">
            </div>
            <div class="form-group">
                <label>OP Bill Due Date</label>
                <input type="date" name="op_bill_due_date" class="form-control" value="{{ old('op_bill_due_date', $member->op_bill_due_date ?? '') }}">
            </div>
            <div class="form-group">
                <label>OP Bill Date</label>
                <input type="date" name="op_bill_date" class="form-control" value="{{ old('op_bill_date', $member->op_bill_date ?? '') }}">
            </div>
        </div>

        <h3 style="margin:20px 0 10px; font-size:16px; color:#2c3e50;">Supplementary</h3>
        <div class="grid-4">
            <div class="form-group">
                <label>Supp. Principal</label>
                <input type="number" step="0.01" name="supplementary_principal" class="form-control" value="{{ old('supplementary_principal', $member->supplementary_principal ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Supp. Interest</label>
                <input type="number" step="0.01" name="supplementary_interest" class="form-control" value="{{ old('supplementary_interest', $member->supplementary_interest ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Supp. Tax</label>
                <input type="number" step="0.01" name="supplementary_tax" class="form-control" value="{{ old('supplementary_tax', $member->supplementary_tax ?? '0') }}">
            </div>
            <div class="form-group">
                <label>Supp. Penalty</label>
                <input type="number" step="0.01" name="supplementary_penality" class="form-control" value="{{ old('supplementary_penality', $member->supplementary_penality ?? '0') }}">
            </div>
        </div>

        <div class="form-group">
            <label>Joint Member Name</label>
            <input type="text" name="joint_member_name" class="form-control" value="{{ old('joint_member_name', $member->joint_member_name ?? '') }}">
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">{{ $member ? 'Update Member' : 'Add Member' }}</button>
            <a href="{{ route('society.memberIdentity') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('buildingSelect').addEventListener('change', function() {
    var buildingId = this.value;
    var wingSelect = document.getElementById('wingSelect');
    var options = wingSelect.querySelectorAll('option');
    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (!opt.value) continue;
        opt.style.display = (!buildingId || opt.getAttribute('data-building') === buildingId) ? '' : 'none';
    }
});
</script>
@endsection
