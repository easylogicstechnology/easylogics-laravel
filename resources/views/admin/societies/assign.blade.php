@extends('layouts.app')

@section('title', 'Assign Societies to Reseller - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Assign Societies to Reseller</h2>
</div>

<div class="card" style="max-width: 700px;">
    <div id="show_assign_error"></div>

    <form method="POST" action="{{ route('admin.societies.assign') }}" onsubmit="return validateAssign()">
        @csrf
        <div class="form-group">
            <label>Select Reseller <span class="required">*</span></label>
            <select class="form-control" id="reseller_id" name="reseller_id" required onchange="loadAssignedSocieties(this.value)">
                <option value="">Select</option>
                @foreach($resellerList as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label>Select Societies <span class="required">*</span></label>
            <select class="form-control" id="societie_id" name="societie_id[]" multiple style="min-height: 200px;">
                @foreach($societyList as $society)
                    <option value="{{ $society->id }}">{{ $society->society_name }}</option>
                @endforeach
            </select>
            <small style="color: #999; font-size: 12px;">Hold Ctrl to select multiple societies</small>
        </div>

        <button type="submit" class="btn btn-success">Assign Society</button>
    </form>
</div>

<script>
function validateAssign() {
    var reseller = document.getElementById('reseller_id').value;
    var societies = document.getElementById('societie_id');
    var selected = Array.from(societies.selectedOptions);

    if (!reseller) {
        document.getElementById('show_assign_error').innerHTML = '<div class="alert alert-error">Please select a reseller.</div>';
        return false;
    }
    if (selected.length === 0) {
        document.getElementById('show_assign_error').innerHTML = '<div class="alert alert-error">Please select at least one society.</div>';
        return false;
    }
    return true;
}

function loadAssignedSocieties(resellerId) {
    if (!resellerId) return;

    fetch('{{ route("admin.societies.getAssigned") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ resellerId: resellerId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var select = document.getElementById('societie_id');
        var assignedIds = data.assignedSocietyList || [];

        Array.from(select.options).forEach(function(opt) {
            opt.selected = assignedIds.indexOf(parseInt(opt.value)) !== -1;
        });
    });
}
</script>
@endsection
