@extends('layouts.app')

@section('title', ($society->society_name ?? 'Society') . ' Dashboard - EasyLogics')

@section('content')
<style>
    .summary-card { border-radius: 6px; padding: 16px; color: #fff; min-height: 130px; margin-bottom: 16px; }
    .summary-card .count { font-size: 32px; font-weight: 700; }
    .summary-card .label-text { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
    .summary-card .sub-row { display: flex; gap: 16px; margin-top: 8px; }
    .summary-card .sub-item { text-align: center; flex: 1; }
    .summary-card .sub-label { font-size: 13px; opacity: 0.85; }
    .summary-card .sub-value { font-size: 18px; font-weight: 600; }
    .summary-card .icon { font-size: 40px; opacity: 0.6; }
    .bg-red { background: #e74c3c; }
    .bg-yellow { background: #f39c12; }
    .bg-green { background: #27ae60; }
    .bg-blue { background: #3498db; }
    .link-more { color: #0f4fa8; font-size: 13px; text-decoration: none; }
    .link-more:hover { text-decoration: underline; }
    .society-heading-ticker { overflow: hidden; white-space: nowrap; max-width: 100%; }
    .society-heading-ticker .ticker-inner { display: inline-block; font-size: 16px; animation: society-heading-scroll 14s linear infinite; }
    .society-heading-ticker:hover .ticker-inner { animation-play-state: paused; }
    @keyframes society-heading-scroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
</style>

<div class="page-header">
    <h2 class="society-heading-ticker"><span class="ticker-inner">{{ $society->society_name ?? 'Society' }} Dashboard</span></h2>
</div>

@if(!session('fy.year_id'))
<div class="alert alert-error">
    No financial year is mapped to this society. Please contact Admin to assign a financial year.
</div>
@endif

<div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:16px; margin-bottom:20px;">
    <div class="summary-card bg-red">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="count">{{ number_format($societyFlatsSummary['flatCount']) }}</div>
                <div class="label-text">Total Flats</div>
            </div>
            <div class="icon">&#127970;</div>
        </div>
        <div class="sub-row">
            <div class="sub-item">
                <div class="sub-label">Commercial</div>
                <div class="sub-value">{{ $societyFlatsSummary['commercialFlats'] }}</div>
            </div>
            <div class="sub-item">
                <div class="sub-label">Residential</div>
                <div class="sub-value">{{ $societyFlatsSummary['residentialCount'] }}</div>
            </div>
        </div>
    </div>

    <div class="summary-card bg-yellow">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="count" style="font-size:22px;">{{ number_format($totalCollection ?? 0, 2) }}</div>
                <div class="label-text">Collection This Year</div>
            </div>
            <div class="icon">&#8377;</div>
        </div>
        <div class="sub-row">
            <div class="sub-item">
                <div class="sub-label">Cash</div>
                <div class="sub-value" style="font-size:14px;">{{ number_format($societyCollectionSummary['cashPayment'], 2) }}</div>
            </div>
            <div class="sub-item">
                <div class="sub-label">Bank</div>
                <div class="sub-value" style="font-size:14px;">{{ number_format($societyCollectionSummary['bankPayment'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="summary-card bg-green">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="count" style="font-size:22px;">{{ number_format($totalOutstanding ?? 0, 2) }}</div>
                <div class="label-text">Total Outstanding This Year</div>
            </div>
            <div class="icon">&#128196;</div>
        </div>
    </div>

    <div class="summary-card bg-blue">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="count" style="font-size:22px;">{{ number_format($totalExpenses ?? 0, 2) }}</div>
                <div class="label-text">Total Expenses This Year</div>
            </div>
            <div class="icon">&#8634;</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px; margin-bottom:16px;">
    <div class="card">
        <h3>Due From Member</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Flat No</th><th>Name</th><th style="text-align:right;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($getDuesFromMemberDetails as $i => $due)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $due->flat_no }}</td>
                    <td>{{ $due->member->member_name ?? '' }}</td>
                    <td style="text-align:right;">{{ number_format(abs($due->amount_payable), 2) }} {{ $due->amount_payable >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" style="color:#999; text-align:center;">No dues found</td></tr>
                @endforelse
                @if($getDuesFromMemberDetails->count() >= 5)
                <tr><td colspan="4" style="text-align:center;"><a href="#" class="link-more">Click here to view due from members.</a></td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Expenses Summary</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Name</th><th style="text-align:right;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($getSocietyExpensesDetails as $i => $expense)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $expense->particulars }}</td>
                    <td style="text-align:right;">{{ number_format(abs($expense->amount), 2) }} {{ $expense->amount >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @empty
                    <tr><td colspan="3" style="color:#999; text-align:center;">No expenses found</td></tr>
                @endforelse
                @if($getSocietyExpensesDetails->count() >= 5)
                <tr><td colspan="3" style="text-align:center;"><a href="#" class="link-more">Click here to view all payments.</a></td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Bill Summary</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Month</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Collection</th><th style="text-align:right;">Dues</th></tr>
            </thead>
            <tbody>
                @forelse($dashboardBillSummaryDetails as $month => $data)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $data['monthName'] }}</td>
                    <td style="text-align:right;">{{ number_format($data['amount'], 2) }}</td>
                    <td style="text-align:right;">{{ number_format($data['collectionAmount'], 2) }}</td>
                    <td style="text-align:right;">{{ number_format($data['dueAmount'], 2) }}</td>
                </tr>
                @empty
                    <tr><td colspan="5" style="color:#999; text-align:center;">No bills found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:16px;">
    <div class="card">
        <h3>Latest Receipts</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Flat</th><th>Name</th><th>Receipt</th><th style="text-align:right;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($getReceiptDetails as $i => $receipt)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $receipt->member->flat_no ?? '' }}</td>
                    <td>{{ $receipt->member->member_name ?? '' }}</td>
                    <td>{{ $receipt->bill_generated_id }}</td>
                    <td style="text-align:right;">{{ number_format(abs($receipt->amount_paid), 2) }} {{ $receipt->amount_paid >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @empty
                    <tr><td colspan="5" style="color:#999; text-align:center;">No receipts found</td></tr>
                @endforelse
                @if($getReceiptDetails->count() >= 5)
                <tr><td colspan="5" style="text-align:center;"><a href="#" class="link-more">Click here to view all latest receipts.</a></td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Latest Payments</h3>
        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Vendor</th><th style="text-align:right;">Amount</th></tr>
            </thead>
            <tbody>
                @forelse($getSocietyLatestPaymentDetails as $i => $payment)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $payment->payment_date ? date('d/m/Y', strtotime($payment->payment_date)) : '' }}</td>
                    <td>{{ $payment->particulars ?? '' }}</td>
                    <td style="text-align:right;">{{ number_format(abs($payment->amount), 2) }} {{ $payment->amount >= 0 ? 'Dr' : 'Cr' }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" style="color:#999; text-align:center;">No payments found</td></tr>
                @endforelse
                @if($getSocietyLatestPaymentDetails->count() >= 5)
                <tr><td colspan="4" style="text-align:center;"><a href="#" class="link-more">Click here to view all latest payments.</a></td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
