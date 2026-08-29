@extends('layouts.app')

@section('title', 'Member Dashboard - EasyLogics')

@section('content')
<h2 style="font-size: 20px; margin-bottom: 20px; color: #2c3e50;">Member Dashboard</h2>

<div class="grid-2">
    <div class="card">
        <h3>Member Details</h3>
        <table>
            <tbody>
                <tr>
                    <td><strong>Name</strong></td>
                    <td>{{ $societyMemberDetails['member_name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Flat No</strong></td>
                    <td>{{ $societyMemberDetails['flat_no'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Email</strong></td>
                    <td>{{ $societyMemberDetails['member_email'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Phone</strong></td>
                    <td>{{ $societyMemberDetails['member_phone'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Unit Type</strong></td>
                    <td>{{ $societyMemberDetails['unit_type'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Area</strong></td>
                    <td>{{ $societyMemberDetails['area'] ?? '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Payment History</h3>
        <table>
            <thead>
                <tr><th>Date</th><th>Amount</th><th>Mode</th></tr>
            </thead>
            <tbody>
                @forelse($societyMemberBillPaymentData as $payment)
                <tr>
                    <td>{{ $payment['payment_date'] ?? '-' }}</td>
                    <td>{{ number_format($payment['amount_paid'] ?? 0, 2) }}</td>
                    <td>{{ $payment['payment_mode'] ?? '-' }}</td>
                </tr>
                @empty
                    <tr><td colspan="3">No payment history found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
