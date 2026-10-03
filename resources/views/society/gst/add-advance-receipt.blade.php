@extends('layouts.app')
@section('title', 'GST Advance Receipt')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">{{ empty($d['id']) ? 'Add' : 'Edit' }} GST Advance Receipt</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" action="{{ route('society.gstAddAdvanceReceipt') }}">
                @csrf
                @if(!empty($d['id']))<input type="hidden" name="id" value="{{ $d['id'] }}">@endif
                <div class="row">
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Party Type</label>
                            <select class="form-control" name="party_type">
                                <option value="Member" {{ (!isset($d['party_type']) || $d['party_type'] == 'Member') ? 'selected' : '' }}>Member</option>
                                <option value="Vendor" {{ (isset($d['party_type']) && $d['party_type'] == 'Vendor') ? 'selected' : '' }}>Vendor</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Member</label>
                            <select class="form-control" name="member_id">
                                <option value="">Select</option>
                                @foreach($memberList as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($d['member_id']) && $d['member_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Vendor</label>
                            <select class="form-control" name="vendor_detail_id">
                                <option value="">Select</option>
                                @foreach($vendorList as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($d['vendor_detail_id']) && $d['vendor_detail_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Party Name (if not a listed member/vendor)</label>
                            <input type="text" class="form-control" name="party_name" value="{{ $d['party_name'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">GSTIN</label>
                            <input type="text" class="form-control" name="gstin" value="{{ $d['gstin'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">PAN</label>
                            <input type="text" class="form-control" name="pan_no" value="{{ $d['pan_no'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Receipt Date<span class="required">*</span></label>
                            <input type="date" class="form-control" name="receipt_date" value="{{ $d['receipt_date'] ?? date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Amount Received (Gross, incl. GST)<span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="amount_received" value="{{ $d['amount_received'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">GST Rate %<span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="gst_rate" value="{{ $d['gst_rate'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Supply Type</label>
                            <select class="form-control" name="supply_type">
                                <option value="Intra-state" {{ (!isset($d['supply_type']) || $d['supply_type'] == 'Intra-state') ? 'selected' : '' }}>Intra-state (CGST+SGST)</option>
                                <option value="Inter-state" {{ (isset($d['supply_type']) && $d['supply_type'] == 'Inter-state') ? 'selected' : '' }}>Inter-state (IGST)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $d['remarks'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="alert alert-info">Taxable Value and CGST/SGST/IGST are calculated automatically from the Amount Received and GST Rate on save.</div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.gstAdvanceReceipts') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
