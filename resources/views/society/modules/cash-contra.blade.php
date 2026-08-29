@extends('layouts.app')
@section('title', 'Society Cash Contra')
@section('content')
<div class="page-header">
    <h2>Society Cash Contra</h2>
</div>

@if($fyId)
<div class="fy-info">Financial Year ID: {{ $fyId }}</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Payment Date</th>
                <th>Transaction Type</th>
                <th>Bank Ledger Head</th>
                <th>Amount</th>
                <th>Cheque No</th>
                <th>Particulars</th>
                <th>Narration</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->payment_date }}</td>
                <td>{{ $item->txn_type }}</td>
                <td>{{ $item->bankLedgerHead->title ?? '-' }}</td>
                <td>{{ number_format($item->amount ?? 0, 2) }}</td>
                <td>{{ $item->cheque_no }}</td>
                <td>{{ $item->particulars }}</td>
                <td>{{ $item->narration }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; color:#999;">No cash contra entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
