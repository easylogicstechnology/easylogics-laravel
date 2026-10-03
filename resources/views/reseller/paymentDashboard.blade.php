@extends('layouts.app')

@section('title', 'Payment Dashboard - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Payment Dashboard</h2>
</div>

<div class="grid-2" style="margin-bottom:16px;">
    <div class="card">
        <h3>Software Purchase Date</h3>
        <div class="value" style="font-size:20px;">
            {{ (!empty($purchaseDate) && $purchaseDate !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($purchaseDate)->format('d-M-Y') : 'Not available' }}
        </div>
    </div>
    <div class="card">
        <h3>Current Subscription Expiry</h3>
        <div class="value" style="font-size:20px;">
            {{ !empty($subscriptionExpiry) ? \Carbon\Carbon::parse($subscriptionExpiry)->format('d-M-Y') : 'Not available' }}
        </div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:12px;">Payment History</h3>
    <table>
        <thead>
            <tr>
                <th>Payment Date</th>
                <th class="text-right">Amount</th>
                <th>Mode</th>
                <th>Extended Till</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
            <tr>
                <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d-M-Y') }}</td>
                <td class="text-right">{{ number_format($p->amount, 2) }}</td>
                <td>{{ $p->payment_mode }}</td>
                <td>{{ $p->extended_till ? \Carbon\Carbon::parse($p->extended_till)->format('d-M-Y') : '-' }}</td>
                <td>{{ $p->remarks }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
