@extends('layouts.app')
@section('title', 'Journal Voucher')
@section('content')
<div class="page-header">
    <h2>Journal Voucher</h2>
</div>

@if($fyId)
<div class="fy-info">Financial Year ID: {{ $fyId }}</div>
@endif

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Voucher No</th>
                <th>Voucher Date</th>
                <th>JV Type</th>
                <th>Amount Debited</th>
                <th>Amount Credited</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $jv)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $jv->voucher_no }}</td>
                <td>{{ $jv->voucher_date }}</td>
                <td>{{ $jv->jv_type }}</td>
                <td>{{ number_format($jv->jv_amount_debited ?? 0, 2) }}</td>
                <td>{{ number_format($jv->jv_amount_credited ?? 0, 2) }}</td>
                <td>{{ $jv->note }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center; color:#999;">No journal vouchers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
