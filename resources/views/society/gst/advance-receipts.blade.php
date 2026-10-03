@extends('layouts.app')
@section('title', 'GST Advance Receipts')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">GST Advance Receipts</h6></div>
            <div class="pull-right"><a href="{{ route('society.gstAddAdvanceReceipt') }}" class="btn btn-primary">+ Add Advance Receipt</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <form method="get" style="margin-bottom:15px;">
                <select name="financial_year_id" class="form-control" style="width:220px;display:inline-block;" onchange="this.form.submit();">
                    @foreach($financialYearsList as $id => $y)
                        <option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Receipt No</th><th>Date</th><th>Party</th><th>GSTIN</th><th class="text-right">Amount Received</th><th class="text-right">Taxable</th><th class="text-right">Total GST</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr>
                            <td>{{ $r->receipt_no }}</td>
                            <td>{{ $r->receipt_date }}</td>
                            <td>{{ $r->party_name }} <span class="label label-default">{{ $r->party_type }}</span></td>
                            <td>{{ $r->gstin }}</td>
                            <td class="text-right">{{ number_format($r->amount_received, 2) }}</td>
                            <td class="text-right">{{ number_format($r->taxable_value, 2) }}</td>
                            <td class="text-right">{{ number_format($r->total_gst, 2) }}</td>
                            <td><span class="label {{ $r->adjustment_status == 'Fully Adjusted' ? 'label-success' : 'label-info' }}">{{ $r->adjustment_status }}</span></td>
                            <td>
                                <a href="{{ route('society.gstAddAdvanceReceipt', $r->id) }}" title="Edit">&#9998;</a>
                                &nbsp;<a href="{{ route('society.gstAdjustAdvance', $r->id) }}" class="btn btn-xs btn-default">Adjust</a>
                            </td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())
                        <tr><td colspan="9" class="text-center">No advance receipts found.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
