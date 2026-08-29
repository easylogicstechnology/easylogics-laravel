@extends('layouts.app')
@section('title', $editItem ? 'Edit Tenant' : 'Add Tenant')
@section('content')
<div class="page-header">
    <h2>{{ $editItem ? 'Edit Tenant' : 'Add Tenant' }}</h2>
    <a href="{{ route('society.tenantMemberIdentity') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addTenant', $editItem->id ?? '') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Tenant Name <span class="required">*</span></label>
                <input type="text" name="tenant_name" class="form-control" value="{{ $editItem->tenant_name ?? '' }}" required>
            </div>
            <div class="form-group">
                <label>Lease Type</label>
                <select name="lease_type" class="form-control">
                    <option value="1" {{ ($editItem && $editItem->lease_type == 1) ? 'selected' : '' }}>Leave & License</option>
                    <option value="2" {{ ($editItem && $editItem->lease_type == 2) ? 'selected' : '' }}>Rental</option>
                    <option value="3" {{ ($editItem && $editItem->lease_type == 3) ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Agreement On</label>
                <input type="date" name="agreement_on" class="form-control" value="{{ ($editItem && $editItem->agreement_on != '0000-00-00') ? $editItem->agreement_on : '' }}">
            </div>
            <div class="form-group">
                <label>Rent Per Month</label>
                <input type="number" step="0.01" name="rent_per_month" class="form-control" value="{{ $editItem->rent_per_month ?? '0' }}">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ $editItem->phone ?? '' }}">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $editItem->email ?? '' }}">
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" class="form-control" value="{{ $editItem->address ?? '' }}">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="{{ $editItem->city ?? '' }}">
            </div>
            <div class="form-group">
                <label>Building</label>
                <select name="building_id" class="form-control">
                    <option value="">Select</option>
                    @foreach($buildings as $bId => $bName)
                    <option value="{{ $bId }}" {{ ($editItem && $editItem->building_id == $bId) ? 'selected' : '' }}>{{ $bName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Flat No</label>
                <input type="text" name="flat_no" class="form-control" value="{{ $editItem->flat_no ?? '' }}">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Tenant' : 'Add Tenant' }}</button>
            <a href="{{ route('society.tenantMemberIdentity') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>
@endsection
