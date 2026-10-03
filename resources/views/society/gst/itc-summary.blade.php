@extends('layouts.app')
@section('title', 'ITC Summary')
@section('content')
@include('society.gst._styles')
@php
    $cg = isset($summary->cgst_itc) ? $summary->cgst_itc : 0;
    $sg = isset($summary->sgst_itc) ? $summary->sgst_itc : 0;
    $ig = isset($summary->igst_itc) ? $summary->igst_itc : 0;
    $claimed = isset($summary->itc_claimed) ? $summary->itc_claimed : 0;
    $reversed = isset($summary->itc_reversed) ? $summary->itc_reversed : 0;
@endphp
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Input Tax Credit (ITC) Summary</h6></div>
        <div class="panel-body">
            <form method="get" style="margin-bottom:15px;">
                <select name="financial_year_id" class="form-control" style="width:220px;display:inline-block;" onchange="this.form.submit();">
                    @foreach($financialYearsList as $id => $y)
                        <option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th></th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                    <tr>
                        <td>Opening ITC</td>
                        <td class="text-right">0.00</td><td class="text-right">0.00</td><td class="text-right">0.00</td><td class="text-right">0.00</td>
                    </tr>
                    <tr>
                        <td>Current Period ITC (Eligible)</td>
                        <td class="text-right">{{ number_format($cg, 2) }}</td>
                        <td class="text-right">{{ number_format($sg, 2) }}</td>
                        <td class="text-right">{{ number_format($ig, 2) }}</td>
                        <td class="text-right">{{ number_format($cg + $sg + $ig, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ITC Claimed</td>
                        <td colspan="3"></td>
                        <td class="text-right">{{ number_format($claimed, 2) }}</td>
                    </tr>
                    <tr>
                        <td>ITC Reversed</td>
                        <td colspan="3"></td>
                        <td class="text-right">{{ number_format($reversed, 2) }}</td>
                    </tr>
                    <tr class="text-bold">
                        <td>ITC Remaining</td>
                        <td colspan="3"></td>
                        <td class="text-right">{{ number_format(($cg + $sg + $ig) - $claimed, 2) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h6 style="margin-top:24px;">Vendor-wise ITC Reconciliation</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Vendor</th><th>GSTIN</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Ineligible</th></tr></thead>
                    <tbody>
                    @foreach($vendorWise as $row)
                        <tr>
                            <td>{{ $row->title ?? '' }}</td>
                            <td>{{ $row->gst_no ?? '' }}</td>
                            <td class="text-right">{{ number_format($row->cgst, 2) }}</td>
                            <td class="text-right">{{ number_format($row->sgst, 2) }}</td>
                            <td class="text-right">{{ number_format($row->igst, 2) }}</td>
                            <td class="text-right">{{ number_format($row->ineligible, 2) }}</td>
                        </tr>
                    @endforeach
                    @if($vendorWise->isEmpty())
                        <tr><td colspan="6" class="text-center">No data.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
