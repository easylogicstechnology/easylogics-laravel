@extends('layouts.app')
@section('title', 'GST Payment / Challan')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Payment / Challan</h6></div>
        <div class="panel-body">
            <form method="post" action="{{ route('society.gstPreparePayment') }}" class="row" style="margin-bottom:15px;">
                @csrf
                <div class="col-md-3">
                    <select name="period_type" class="form-control">
                        <option value="Month">Month</option>
                        <option value="Quarter">Quarter</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="text" name="period_value" class="form-control" placeholder="e.g. September or Q2" required></div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary">Prepare GST Payment</button></div>
            </form>
            <div class="clearfix"></div>
            <p class="text-muted">Prepares a draft using the exact same figures as the GST Liability statement for the period. Government challan number/CIN/BSR are never generated here - enter them after actually paying on the GST portal.</p>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Period</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Interest</th><th class="text-right">Late Fee</th><th class="text-right">Total Payable</th><th>Challan No</th><th>CIN</th><th>Payment Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($rows as $p)
                        <tr>
                            <td>{{ $p->period_type }}: {{ $p->period_value }}</td>
                            <td class="text-right">{{ number_format($p->cgst, 2) }}</td>
                            <td class="text-right">{{ number_format($p->sgst, 2) }}</td>
                            <td class="text-right">{{ number_format($p->igst, 2) }}</td>
                            <td class="text-right">{{ number_format($p->interest, 2) }}</td>
                            <td class="text-right">{{ number_format($p->late_fee, 2) }}</td>
                            <td class="text-right"><b>{{ number_format($p->total_payable, 2) }}</b></td>
                            <td>{{ $p->challan_number }}</td>
                            <td>{{ $p->cin_cpin }}</td>
                            <td>{{ $p->payment_date }}</td>
                            <td><span class="label {{ $p->payment_status == 'Paid' ? 'label-success' : 'label-info' }}">{{ $p->payment_status }}</span></td>
                            <td><a href="{{ route('society.gstUpdatePayment', $p->id) }}" class="btn btn-xs btn-default">Update</a></td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())
                        <tr><td colspan="12" class="text-center">No GST payments prepared yet.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
