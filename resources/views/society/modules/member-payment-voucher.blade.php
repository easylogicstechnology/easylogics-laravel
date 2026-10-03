@php
    $member = $payment->member;
    $memberName = trim(($member->member_prefix ?? '') . ' ' . ($member->member_name ?? ''));
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $payment->receipt_id }}</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; background: #eee; margin: 0; padding: 24px; }
    .toolbar { max-width: 620px; margin: 0 auto 14px; display: flex; gap: 8px; justify-content: flex-end; }
    .toolbar a, .toolbar button { display: inline-block; padding: 7px 14px; border-radius: 4px; font-size: 13px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
    .btn-print { background: #27ae60; color: #fff; }
    .btn-pdf { background: #c0392b; color: #fff; }
    .btn-back { background: #7f8c8d; color: #fff; }

    .receipt {
        max-width: 620px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #999;
        box-shadow: 0 6px 18px rgba(0,0,0,0.18), 0 1px 0 rgba(255,255,255,0.6) inset;
        padding: 18px 22px;
    }
    .receipt h1 { font-size: 15px; text-align: center; margin: 0 0 4px; font-weight: bold; }
    .receipt .sub { text-align: center; font-size: 11px; margin: 2px 0; color: #333; }
    .receipt .title { text-align: center; font-size: 14px; margin: 10px 0; }
    .row-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
    .row-table td { padding: 0; vertical-align: top; }
    .row-table td.left { width: 68%; }
    .row-table td.right { width: 32%; text-align: right; white-space: nowrap; }
    .amount-line { margin: 10px 0; }
    .field-line { margin: 4px 0; }
    .narration-label { margin-top: 8px; font-weight: bold; }
    .narration-value { min-height: 16px; border-bottom: 1px dotted #999; margin-bottom: 6px; }
    .sign-block { margin-top: 30px; text-align: right; }
    .sign-block .for-line { }
    .sign-block .sign-title { margin-top: 34px; font-weight: bold; }
    .validity { margin-top: 14px; font-size: 11px; }

    @media print {
        body { background: #fff; padding: 0; }
        .toolbar { display: none; }
        .receipt { box-shadow: none; border: 1px solid #000; }
    }
</style>
</head>
<body>

@unless($isPdf)
<div class="toolbar">
    <button class="btn-print" onclick="window.print()">&#128424; Print</button>
    <a class="btn-pdf" href="{{ route('society.memberPaymentVoucherPdf', $payment->id) }}" target="_blank">&#8681; PDF</a>
    <a class="btn-back" href="{{ route('society.memberPayments') }}">&larr; Back</a>
</div>
@endunless

<div class="receipt">
    <h1>{{ $society->society_name ?? '' }}</h1>
    @if(!empty($society->registration_no))
    <div class="sub">{{ $society->registration_no }}</div>
    @endif
    @if(!empty($society->address))
    <div class="sub">{{ $society->address }}</div>
    @endif

    <div class="title">Receipt</div>

    <table class="row-table">
        <tr>
            <td class="left">Receipt No : {{ $payment->receipt_id }}</td>
            <td class="right">Date : {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '' }}</td>
        </tr>
        <tr>
            <td class="left">Received with Thanks From : {{ $memberName }}</td>
            <td class="right">Unit No : {{ $member->flat_no ?? '' }}</td>
        </tr>
    </table>

    <div class="amount-line">
        Rs. {{ number_format($payment->amount_paid ?? 0, 2) }} ({{ $amountWords }})
    </div>

    @if($billInfo)
    <div class="field-line">
        Towards: Bill No:- {{ $billInfo['bill_no'] }}, Bill Date:- {{ $billInfo['bill_date'] ? \Carbon\Carbon::parse($billInfo['bill_date'])->format('d/m/Y') : '' }}
    </div>
    @endif

    <div class="field-line">
        By Cheque No: {{ $payment->cheque_reference_number }}&nbsp;&nbsp;&nbsp;Dated on : {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '' }}
    </div>
    <div class="field-line">
        Drawn On : {{ $bankName }}
    </div>

    <div class="narration-label">NARRATION</div>
    <div class="narration-value">{{ $payment->narration }}</div>

    <div class="sign-block">
        <div class="for-line">For {{ $society->society_name ?? '' }}</div>
        <div class="sign-title">CHAIRMAN / SECRETARY / TREASURER</div>
    </div>

    <div class="validity">This Receipt is Valid Subject to realisation of cheque.</div>
</div>

</body>
</html>
