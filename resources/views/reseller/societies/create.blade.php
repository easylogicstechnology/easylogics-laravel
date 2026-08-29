@extends('layouts.app')

@section('title', 'Create Society - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Create Society</h2>
    <a href="{{ route('reseller.societies.assigned') }}" class="btn btn-primary">My Societies</a>
</div>

@if($creditInfo['credit'] > 0)
<div class="card" style="background:#e8f4fd; border:1px solid #b8daff; margin-bottom:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <strong style="color:#1a5276;">Member Credit:</strong>
            <span style="font-size:18px; font-weight:700; color:#2c3e50; margin-left:8px;">{{ $creditInfo['used'] }} / {{ $creditInfo['credit'] }}</span>
            <span style="color:#666; font-size:13px; margin-left:4px;">used</span>
        </div>
        <div>
            <span style="font-size:13px; color:#666;">Remaining:</span>
            <span style="font-size:18px; font-weight:700; color:{{ $creditInfo['remaining'] > 0 ? '#27ae60' : '#e74c3c' }}; margin-left:4px;">{{ $creditInfo['remaining'] }}</span>
        </div>
    </div>
</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('reseller.societies.create') }}" autocomplete="off">
        @csrf

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="form-group">
                <label>Society Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="society_name" placeholder="Society Name" maxlength="100" required onblur="generateShortCode(this.value)" value="{{ old('society_name') }}">
            </div>
            <div class="form-group">
                <label>ShortCode</label>
                <input type="text" class="form-control" id="society_code" name="society_code" placeholder="Society ShortCode" readonly value="{{ old('society_code') }}">
            </div>

            <div class="form-group">
                <label>Registration No.</label>
                <input type="text" class="form-control" name="registration_no" placeholder="Registration No." value="{{ old('registration_no') }}">
            </div>
            <div class="form-group">
                <label>Registration Date</label>
                <input type="date" class="form-control" name="registration_date" value="{{ old('registration_date') }}">
            </div>

            <div class="form-group">
                <label>Service Tax No.</label>
                <input type="text" class="form-control" name="service_tax_no" placeholder="Service Tax No." value="{{ old('service_tax_no') }}">
            </div>
            <div class="form-group">
                <label>Address <span class="required">*</span></label>
                <input type="text" class="form-control" name="address" placeholder="Address" required value="{{ old('address') }}">
            </div>

            <div class="form-group">
                <label>Telephone No</label>
                <input type="text" class="form-control" name="telephone_no" placeholder="Telephone No" value="{{ old('telephone_no') }}">
            </div>
            <div class="form-group">
                <label>Fax No.</label>
                <input type="text" class="form-control" name="fax_no" placeholder="Fax No." value="{{ old('fax_no') }}">
            </div>

            <div class="form-group">
                <label>Email Id</label>
                <input type="text" class="form-control" name="email_id" placeholder="Email Id" value="{{ old('email_id') }}">
            </div>
            <div class="form-group">
                <label>Society URL</label>
                <input type="text" class="form-control" name="url" placeholder="Society URL" maxlength="50" value="{{ old('url') }}">
            </div>

            <div class="form-group">
                <label>TAN No.</label>
                <input type="text" class="form-control" name="tan_no" placeholder="TAN No." value="{{ old('tan_no') }}">
            </div>
            <div class="form-group">
                <label>PAN No.</label>
                <input type="text" class="form-control" name="pan_no" placeholder="PAN No." maxlength="20" value="{{ old('pan_no') }}">
            </div>

            <div class="form-group">
                <label>Circle</label>
                <input type="text" class="form-control" name="circle" placeholder="Circle" value="{{ old('circle') }}">
            </div>
            <div class="form-group">
                <label>GSTIN No.</label>
                <input type="text" class="form-control" name="gstin_no" placeholder="GSTIN No." maxlength="20" value="{{ old('gstin_no') }}">
            </div>

            <div class="form-group">
                <label>CGST No.</label>
                <input type="text" class="form-control" name="cgst_no" placeholder="CGST No." value="{{ old('cgst_no') }}">
            </div>
            <div class="form-group">
                <label>IGST No.</label>
                <input type="text" class="form-control" name="igst_no" placeholder="IGST No." value="{{ old('igst_no') }}">
            </div>

            <div class="form-group">
                <label>Is Conveyance</label>
                <select class="form-control" name="is_conveyance">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Conveyance Date</label>
                <input type="date" class="form-control" name="conveynace_date" value="{{ old('conveynace_date') }}">
            </div>

            <div class="form-group">
                <label>Authorised Person</label>
                <input type="text" class="form-control" name="authorised_person" placeholder="Name of Authorised Person" value="{{ old('authorised_person') }}">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select class="form-control" name="status">
                    <option value="1" selected>Active</option>
                    <option value="0">InActive</option>
                </select>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-success">Save</button>
        </div>
    </form>
</div>

<p style="margin-top:12px; font-size:13px; color:#999;">Default login for new society: Username = Society Name, Password = 12345</p>

<script>
function generateShortCode(name) {
    if (name) {
        var words = name.trim().split(/\s+/);
        var code = words.map(function(w) { return w.charAt(0).toUpperCase(); }).join('');
        document.getElementById('society_code').value = code;
    }
}
</script>
@endsection
