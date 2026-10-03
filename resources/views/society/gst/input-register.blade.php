@extends('layouts.app')
@section('title', 'GST Input / Purchase Register')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Input / Purchase Register</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-2"><select name="financial_year_id" class="form-control"><option value="">Current FY</option>@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ (!empty($qs['financial_year_id']) && $qs['financial_year_id'] == $id) ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="{{ $qs['date_from'] ?? '' }}"></div>
                <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="{{ $qs['date_to'] ?? '' }}"></div>
                <div class="col-md-2">
                    <select name="itc_eligibility" class="form-control">
                        <option value="">ITC Eligibility</option>
                        <option value="Eligible" {{ (!empty($qs['itc_eligibility']) && $qs['itc_eligibility'] == 'Eligible') ? 'selected' : '' }}>Eligible</option>
                        <option value="Ineligible" {{ (!empty($qs['itc_eligibility']) && $qs['itc_eligibility'] == 'Ineligible') ? 'selected' : '' }}>Ineligible</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-info btn-sm">Show</button>
                    <a class="btn btn-danger btn-sm" target="_blank" href="{{ route('society.gstInputRegisterPdf') }}?{{ http_build_query($qs) }}">Export PDF</a>
                    <a class="btn btn-success btn-sm" href="{{ route('society.gstInputRegisterExcel') }}?{{ http_build_query($qs) }}">Export Excel</a>
                </div>
            </form>
            <div class="clearfix"></div>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr><th>Sr</th><th>Supplier</th><th>Supplier GSTIN</th><th>Invoice No</th><th>Invoice Date</th><th>HSN/SAC</th><th>Taxable Amt</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total Invoice</th><th>ITC Status</th><th>Claim Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $i => $v)
                        @php
                            $totalInvoice = $v->amount + $v->sgst_amount + $v->cgst_amount + $v->igst_amount;
                            $eligibility = isset($v->itc_eligibility) ? $v->itc_eligibility : 'Eligible';
                            $claimStatus = isset($v->itc_claim_status) ? $v->itc_claim_status : 'Unclaimed';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $v->title ?? '' }}</td>
                            <td>{{ $v->gst_no ?? '' }}</td>
                            <td>{{ $v->bill_no }}</td>
                            <td>{{ $v->bill_date }}</td>
                            <td>{{ $v->hsn_sac }}</td>
                            <td class="text-right">{{ number_format($v->amount, 2) }}</td>
                            <td class="text-right">{{ number_format($v->cgst_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($v->sgst_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($v->igst_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($totalInvoice, 2) }}</td>
                            <td><span class="label {{ $eligibility == 'Eligible' ? 'label-success' : 'label-danger' }}">{{ $eligibility }}</span></td>
                            <td>{{ $claimStatus }}</td>
                            <td><a href="{{ route('society.gstClassifyItc', $v->id) }}" class="btn btn-xs btn-default">Classify</a></td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())
                        <tr><td colspan="14" class="text-center">No records found.</td></tr>
                    @endif
                    </tbody>
                    <tfoot>
                    <tr class="text-bold">
                        <th colspan="6" class="text-right">Total</th>
                        <th class="text-right">{{ number_format($totals->total_taxable ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_cgst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_sgst ?? 0, 2) }}</th>
                        <th class="text-right">{{ number_format($totals->total_igst ?? 0, 2) }}</th>
                        <th colspan="4"></th>
                    </tr>
                    <tr>
                        <th colspan="10" class="text-right">Eligible ITC / Ineligible</th>
                        <th colspan="4">{{ number_format($totals->total_eligible ?? 0, 2) }} / {{ number_format($totals->total_ineligible ?? 0, 2) }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
