@extends('layouts.app')
@section('title', 'Classify ITC')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Classify ITC - Bill #{{ $line->bill_no }}</h6></div>
        <div class="panel-body">
            <p>Line: HSN/SAC <b>{{ $line->hsn_sac }}</b>, Taxable Rs. {{ number_format($line->amount, 2) }}, Total GST Rs. {{ number_format($line->cgst_amount + $line->sgst_amount + $line->igst_amount, 2) }}</p>
            <form method="post" autocomplete="off" action="{{ route('society.gstClassifyItc', $line->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">ITC Eligibility</label>
                            <select class="form-control" name="itc_eligibility" id="itc_eligibility">
                                <option value="Eligible" {{ (!isset($d['itc_eligibility']) || $d['itc_eligibility'] == 'Eligible') ? 'selected' : '' }}>Eligible</option>
                                <option value="Ineligible" {{ (isset($d['itc_eligibility']) && $d['itc_eligibility'] == 'Ineligible') ? 'selected' : '' }}>Ineligible</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5 padding-1">
                        <div class="form-group">
                            <label class="control-label">Ineligible Reason</label>
                            <input type="text" class="form-control" name="ineligible_reason" value="{{ $d['ineligible_reason'] ?? '' }}" placeholder="e.g. Blocked credit u/s 17(5)">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Claim Status</label>
                            <select class="form-control" name="itc_claim_status">
                                @foreach(['Unclaimed', 'Claimed', 'Reversed'] as $opt)
                                    <option value="{{ $opt }}" {{ (isset($d['itc_claim_status']) && $d['itc_claim_status'] == $opt) ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Claim Period</label>
                            <input type="text" class="form-control" name="claim_period" value="{{ $d['claim_period'] ?? '' }}" placeholder="e.g. 2025-09">
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
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.gstInputRegister') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
