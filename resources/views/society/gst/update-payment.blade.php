@extends('layouts.app')
@section('title', 'Update GST Payment')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Update GST Payment - {{ $p['period_type'] }} {{ $p['period_value'] }}</h6></div>
        <div class="panel-body">
            <div class="alert alert-warning">Enter the Challan Number, CIN, BSR/bank reference and payment date only after actually paying on the GST portal. This application never generates these values.</div>
            <p>Total Payable: <b>Rs. {{ number_format($p['total_payable'], 2) }}</b> (CGST {{ number_format($p['cgst'], 2) }} + SGST {{ number_format($p['sgst'], 2) }} + IGST {{ number_format($p['igst'], 2) }} + Interest {{ number_format($p['interest'], 2) }} + Late Fee {{ number_format($p['late_fee'], 2) }})</p>
            <form method="post" autocomplete="off" action="{{ route('society.gstUpdatePayment', $p['id']) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Challan Number</label>
                            <input type="text" class="form-control" name="challan_number" value="{{ $p['challan_number'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">CIN/CPIN</label>
                            <input type="text" class="form-control" name="cin_cpin" value="{{ $p['cin_cpin'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Bank/Portal Reference</label>
                            <input type="text" class="form-control" name="bank_portal_reference" value="{{ $p['bank_portal_reference'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" value="{{ $p['payment_date'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Amount Paid</label>
                            <input type="number" step="0.01" class="form-control" name="amount_paid" value="{{ $p['amount_paid'] }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Status</label>
                            <select class="form-control" name="payment_status">
                                @foreach(['Draft', 'Prepared', 'Paid'] as $st)
                                    <option value="{{ $st }}" {{ $p['payment_status'] == $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $p['remarks'] }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.gstPayments') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
