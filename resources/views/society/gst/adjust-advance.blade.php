@extends('layouts.app')
@section('title', 'Adjust Advance Receipt')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Adjust Advance Receipt #{{ $r['receipt_no'] }}</h6></div>
        <div class="panel-body">
            <p>Amount Received: <b>Rs. {{ number_format($r['amount_received'], 2) }}</b> &nbsp; Remaining Balance: <b>Rs. {{ number_format($balance, 2) }}</b> &nbsp; Status: <span class="label label-info">{{ $r['adjustment_status'] }}</span></p>

            @if($balance > 0)
            <form method="post" autocomplete="off" action="{{ route('society.gstAdjustAdvance', $r['id']) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Adjust Against</label>
                            <select class="form-control" name="adjusted_against_type">
                                <option value="MemberBill">Member Bill</option>
                                <option value="VendorBill">Vendor Bill</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Bill/Invoice No</label>
                            <input type="text" class="form-control" name="adjusted_against_invoice_no" placeholder="Invoice No (reference)">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Bill/Invoice ID</label>
                            <input type="number" class="form-control" name="adjusted_against_id" placeholder="Internal ID" required>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Adjustment Date</label>
                            <input type="date" class="form-control" name="adjustment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Adjusted Amount (max Rs. {{ number_format($balance, 2) }})</label>
                            <input type="number" step="0.01" min="0" max="{{ $balance }}" class="form-control" name="adjusted_amount" required>
                        </div>
                    </div>
                    <div class="col-md-8 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Adjust</button>
            </form>
            @else
                <div class="alert alert-success">This advance has been fully adjusted.</div>
            @endif

            <h6 style="margin-top:20px;">Adjustment History</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Date</th><th>Against</th><th>Invoice No</th><th class="text-right">Adjusted Amount</th><th class="text-right">Taxable</th><th class="text-right">GST</th></tr></thead>
                    <tbody>
                    @foreach($adjustments as $a)
                        <tr>
                            <td>{{ $a->adjustment_date }}</td>
                            <td>{{ $a->adjusted_against_type }}</td>
                            <td>{{ $a->adjusted_against_invoice_no }}</td>
                            <td class="text-right">{{ number_format($a->adjusted_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($a->adjusted_taxable_value, 2) }}</td>
                            <td class="text-right">{{ number_format($a->adjusted_gst_amount, 2) }}</td>
                        </tr>
                    @endforeach
                    @if($adjustments->isEmpty())
                        <tr><td colspan="6" class="text-center">No adjustments yet.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
            <a href="{{ route('society.gstAdvanceReceipts') }}" class="btn btn-default">Back</a>
        </div>
    </div>
</div>
@endsection
