@extends('layouts.app')
@section('title', $title ?? 'Collection Sheet')
@section('content')
@include('society.reports._styles')
<?php use App\Support\ReportUtil; ?>
<div class="page-header">
    <h2>Collection Sheet</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.collectionSheet') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>For the Period</label><input type="date" name="payment_date" value="{{ $input['payment_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="payment_date_to" value="{{ $input['payment_date_to'] ?? '' }}"></div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_collection_sheet', 'file' => 'CollectionSheet', 'sheet' => 'Collection Sheet', 'orientation' => 'landscape'])
                <a href="{{ route('society.reports.collectionSheet') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div id="print_collection_sheet">
        <div class="print-collection-sheet ar-report">
            <div id="print_collection_header">
                <div class="society-report-form-box-label" style="font-size:18px;font-weight:600">Society : {{ ReportUtil::plain($society->society_name ?? "") }}</div>
                <div class="society-report-form-box-label">Date : {{ !empty($input['payment_date']) ? date('d-m-Y', strtotime($input['payment_date'])) . '   -    ' . date('d-m-Y', strtotime($input['payment_date_to'] ?? '')) : '' }}</div>
                <div class="report-bill">Collection Sheet</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Bill No.</th>
                        <th rowspan="2">Unit No.</th>
                        <th rowspan="2">Member</th>
                        <th rowspan="2">Bill Amt</th>
                        <th rowspan="2">Int.</th>
                        @if($showGst)<th rowspan="2">GST</th>@endif
                        <th rowspan="2">Arrears</th>
                        <th rowspan="2">Total Amt</th>
                        <th rowspan="2">Amt. Rec.</th>
                        <th colspan="3">Payment Received Details</th>
                        <th rowspan="2">Balance Amount</th>
                        <th rowspan="2">Interest/<br>Penalty</th>
                    </tr>
                    <tr>
                        <th>Ch.No.</th>
                        <th>Date</th>
                        <th>Bank's Name / Cash</th>
                    </tr>
                </thead>
                <tbody>
                @if(count($rows) > 0)
                    <?php $idCount = 1; $totalBillAmount = 0; $totalInt = 0; $totalTax = 0; $totalArrears = 0; $totalAmount = 0; ?>
                    @foreach($rows as $r)
                        <?php
                            $billTotal = $r['t_amount_payable'];
                            $arrears = $r['t_op_due_amount'];
                            $monthlyBillAmount = $r['t_monthly_amount'] - $r['t_tax_total'];
                            $totalBillAmount += $monthlyBillAmount;
                            $totalInt += $r['t_interest'];
                            $totalTax += $r['t_tax_total'];
                            $totalArrears += $arrears;
                            $totalAmount += $billTotal;
                        ?>
                        <tr>
                            <td class="text-center">{{ $r['bill_no'] }}</td>
                            <td class="text-center">{{ $r['member_flat_no'] }}</td>
                            <td>{{ $r['member_prefix'] }} {{ $r['member_name'] }}</td>
                            <td class="text-right">{{ $monthlyBillAmount }}</td>
                            <td class="text-right">{{ $r['t_interest'] }}</td>
                            @if($showGst)<td class="text-right">{{ $r['t_tax_total'] }}</td>@endif
                            <td class="text-right">{{ $arrears }}</td>
                            <td class="text-right">{{ $billTotal }}</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        <?php $idCount++; ?>
                    @endforeach
                    <?php $f = fn ($v) => ReportUtil::decimal2CreditDebit($v); ?>
                    <tr class="ar-total">
                        <td colspan="3" class="text-right" style="font-weight:bold;"> Total</td>
                        <td class="text-right" style="font-weight:bold;">{!! $f($totalBillAmount) !!}</td>
                        <td class="text-right" style="font-weight:bold;">{!! $f($totalInt) !!}</td>
                        @if($showGst)<td class="text-right" style="font-weight:bold;">{!! $f($totalTax) !!}</td>@endif
                        <td class="text-right" style="font-weight:bold;">{!! $f($totalArrears) !!}</td>
                        <td class="text-right" style="font-weight:bold;">{!! $f($totalAmount) !!}</td>
                        <td colspan="5" class="text-left" style="font-weight:bold;"></td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
