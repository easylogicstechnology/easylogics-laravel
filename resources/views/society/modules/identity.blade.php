@extends('layouts.app')
@section('title', 'Society Identity')
@section('content')
<div class="page-header">
    <h2>Society Identity</h2>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.identity') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Society Name <span class="required">*</span></label>
                <input type="text" name="society_name" class="form-control" value="{{ old('society_name', $society->society_name ?? '') }}">
            </div>
            <div class="form-group">
                <label>Society Code</label>
                <input type="text" name="society_code" class="form-control" value="{{ old('society_code', $society->society_code ?? '') }}">
            </div>
            <div class="form-group">
                <label>Registration No</label>
                <input type="text" name="registration_no" class="form-control" value="{{ old('registration_no', $society->registration_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Registration Date</label>
                <input type="date" name="registration_date" class="form-control" value="{{ old('registration_date', $society->registration_date ?? '') }}">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $society->address ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>Telephone No</label>
                <input type="text" name="telephone_no" class="form-control" value="{{ old('telephone_no', $society->telephone_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Fax No</label>
                <input type="text" name="fax_no" class="form-control" value="{{ old('fax_no', $society->fax_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email_id" class="form-control" value="{{ old('email_id', $society->email_id ?? '') }}">
            </div>
            <div class="form-group">
                <label>Website URL</label>
                <input type="text" name="url" class="form-control" value="{{ old('url', $society->url ?? '') }}">
            </div>
            <div class="form-group">
                <label>TAN No</label>
                <input type="text" name="tan_no" class="form-control" value="{{ old('tan_no', $society->tan_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>PAN No</label>
                <input type="text" name="pan_no" class="form-control" value="{{ old('pan_no', $society->pan_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Circle</label>
                <input type="text" name="circle" class="form-control" value="{{ old('circle', $society->circle ?? '') }}">
            </div>
            <div class="form-group">
                <label>Service Tax No</label>
                <input type="text" name="service_tax_no" class="form-control" value="{{ old('service_tax_no', $society->service_tax_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>GSTIN No</label>
                <input type="text" name="gstin_no" class="form-control" value="{{ old('gstin_no', $society->gstin_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>CGST No</label>
                <input type="text" name="cgst_no" class="form-control" value="{{ old('cgst_no', $society->cgst_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>IGST No</label>
                <input type="text" name="igst_no" class="form-control" value="{{ old('igst_no', $society->igst_no ?? '') }}">
            </div>
            <div class="form-group">
                <label>Is Conveyance</label>
                <select name="is_conveyance" class="form-control">
                    <option value="N" {{ ($society->is_conveyance ?? '') == 'N' ? 'selected' : '' }}>No</option>
                    <option value="Y" {{ ($society->is_conveyance ?? '') == 'Y' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Conveyance Date</label>
                <input type="date" name="conveynace_date" class="form-control" value="{{ old('conveynace_date', $society->conveynace_date ?? '') }}">
            </div>
            <div class="form-group">
                <label>Authorised Person</label>
                <input type="text" name="authorised_person" class="form-control" value="{{ old('authorised_person', $society->authorised_person ?? '') }}">
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
