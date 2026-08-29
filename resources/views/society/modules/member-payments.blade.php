@extends('layouts.app')
@section('title', 'Member Payments')
@section('content')
<div class="page-header">
    <h2>Member Payments</h2>
    <a href="{{ route('society.addMemberPayment') }}" class="btn btn-primary btn-sm">+ Make Payment</a>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Member Name</th>
                    <th>Flat/Shop No</th>
                    <th>Receipt Id</th>
                    <th width="10%">Date</th>
                    <th>Amount Paid</th>
                    <th>Society Bank</th>
                    <th>Payment Mode</th>
                    <th>Ref. No</th>
                    <th>Bill Type</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->member ? trim($p->member->member_prefix . ' ' . $p->member->member_name) : '' }}</td>
                    <td>{{ $p->member->flat_no ?? '' }}</td>
                    <td>{{ $p->receipt_id ?? '' }}</td>
                    <td>{{ ($p->payment_date && $p->payment_date != '0000-00-00') ? $p->payment_date : '' }}</td>
                    <td style="text-align:right;">{{ number_format($p->amount_paid ?? 0, 2) }}</td>
                    <td>{{ $p->societyBank->bank_name ?? '' }}</td>
                    <td>{{ $paymentModes[$p->payment_mode] ?? '' }}</td>
                    <td>{{ $p->cheque_reference_number ?? '' }}</td>
                    <td>{{ $p->bill_type ?? 'Regular' }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('society.addMemberPayment', $p->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteMemberPayment', $p->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center; color:#999;">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
