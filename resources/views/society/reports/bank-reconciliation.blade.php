@extends('layouts.app')
@section('title', $title ?? 'Bank Reconciliation')
@section('content')
@include('society.reports._styles')
<?php
    $sample = '236,995.77';
    $line = function ($label, $value, $boxed = false) {
        $box = $boxed ? ';border-top:2px solid #000;border-bottom:2px solid #000;font-weight:600' : '';

        return '<tr><td class="text-right ar-bold" style="width:50%;border:0">' . $label . '</td>'
            . '<td class="text-right" style="width:16%;border:0' . $box . '">' . $value . '</td><td style="border:0"></td></tr>';
    };
?>
<div class="page-header">
    <h2>Bank Reconciliation</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.bankReconciliation') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field">
                <label>Report Type</label>
                <select name="report_type">
                    <option value="">select</option>
                    @foreach(['Detail', 'Summary'] as $t)
                        <option value="{{ $t }}" {{ ($input['report_type'] ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Bank Name</label>
                <select name="society_bank_id" class="ar-wide" required>
                    <option value="">Select Bank</option>
                    @foreach($banks as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($input['society_bank_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>For the Period</label>
                <input type="date" name="payment_date" value="">
            </div>
            <div class="ar-field">
                <label>To</label>
                <input type="date" name="payment_date_to" value="">
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="javascript:void(0);" class="btn btn-success" onclick="printReport('print_bank_reconciliation');">Print Friendly</a>
                <a href="{{ route('society.reports.bankReconciliation') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>

{{-- The CakePHP screen shows this fixed sample statement whatever is selected (nothing is calculated behind it). --}}
<div class="card">
    <div id="print_bank_reconciliation">
        <div class="ar-report">
            @include('society.reports._society_head')
            <div class="report-bill">Bank Reconciliation</div>
            <table style="width:80%;margin:10px auto;border:0">
                {!! $line('Opening Balance', $sample) !!}
                {!! $line('Deposits During the Period Cash', $sample) !!}
                {!! $line('Cheque', $sample) !!}
                {!! $line('Total', $sample, true) !!}
                {!! $line('Withdrawls During the Period Cash', $sample) !!}
                {!! $line('Cheque', $sample) !!}
                {!! $line('Total', $sample, true) !!}
                {!! $line('Balance As Per Bank Book', $sample) !!}
                {!! $line('Cheques Issued But Not Presented', $sample) !!}
                {!! $line('Cheques Deposited But Not Credited', $sample) !!}
                {!! $line('Simple Balance As Per Bank Statement', $sample, true) !!}
            </table>
            <div class="text-center ar-bold">(Rupees Fourteen Lac Twenty One Thousand Two Hundred Eleven And Forty Five Paise Only )</div>
            <table style="width:80%;margin:10px auto;border:0">
                {!! $line('Passbook Amount', '0.00') !!}
                {!! $line('Difference Amount', '1421211.45') !!}
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
