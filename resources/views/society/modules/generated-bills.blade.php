@extends('layouts.app')
@section('title', 'All Generated Bills')
@section('content')
<div class="page-header">
    <h2>All Generated Bills</h2>
</div>

@if($fyId)
<div class="fy-info">Financial Year ID: {{ $fyId }}</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Flat No</th>
                <th>Member Name</th>
                <th>Bill No</th>
                <th>Bill Type</th>
                <th>Month</th>
                <th>Bill Date</th>
                <th>Due Date</th>
                <th>Amount Payable</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $bill)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $bill->member->flat_no ?? '-' }}</td>
                <td>{{ $bill->member->member_name ?? '-' }}</td>
                <td>{{ $bill->bill_no }}</td>
                <td>{{ $bill->bill_type }}</td>
                <td>{{ $bill->month }}</td>
                <td>{{ $bill->bill_generated_date }}</td>
                <td>{{ $bill->bill_due_date }}</td>
                <td>{{ number_format($bill->amount_payable ?? 0, 2) }}</td>
                <td>{{ number_format($bill->balance_amount ?? 0, 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="10" style="text-align:center; color:#999;">No generated bills found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
