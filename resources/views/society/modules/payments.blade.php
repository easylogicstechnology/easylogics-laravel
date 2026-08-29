@extends('layouts.app')
@section('title', 'Society Payments')
@section('content')
<div class="page-header">
    <h2>Society Payments</h2>
    <a href="{{ route('society.addPayment') }}" class="btn btn-primary btn-sm">+ Add Payment</a>
</div>

<div class="card" style="margin-bottom:16px;">
    <form method="POST" action="{{ route('society.payments') }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        @csrf
        <div class="form-group" style="margin:0;">
            <label style="font-size:12px;">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" style="width:160px;">
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size:12px;">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" style="width:160px;">
        </div>
        <div>
            <button type="submit" class="btn btn-success btn-sm">Filter</button>
            <a href="{{ route('society.payments') }}" class="btn btn-sm" style="background:#999; color:#fff;">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr style="background:#009688; color:#fff;">
                    <th style="color:#fff;">#</th>
                    <th style="color:#fff;">Paid To</th>
                    <th style="color:#fff;">Category</th>
                    <th style="color:#fff;">Particulars</th>
                    <th style="color:#fff;">Bill Voucher No</th>
                    <th style="color:#fff;">Payment Date</th>
                    <th style="color:#fff;">Cheque Date</th>
                    <th style="color:#fff;">Clear Date</th>
                    <th style="color:#fff; text-align:right;">Amount</th>
                    <th style="color:#fff;">Cheque No.</th>
                    <th style="color:#fff; text-align:right;">Tax Amount</th>
                    <th style="color:#fff; text-align:right;">Total Amount</th>
                    <th style="color:#fff;">Type</th>
                    <th style="color:#fff; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->ledgerHead->title ?? '' }}</td>
                    <td>{{ $accountCategories[$p->ledgerHead->account_category_id ?? ''] ?? '' }}</td>
                    <td>{{ $p->particulars }}</td>
                    <td>{{ $p->bill_voucher_number }}</td>
                    <td>{{ ($p->payment_date && $p->payment_date != '0000-00-00') ? $p->payment_date : '' }}</td>
                    <td>{{ ($p->cheque_date && $p->cheque_date != '0000-00-00') ? $p->cheque_date : '' }}</td>
                    <td>{{ ($p->debited_date && $p->debited_date != '0000-00-00') ? $p->debited_date : '' }}</td>
                    <td style="text-align:right;">{{ number_format($p->amount ?? 0, 2) }}</td>
                    <td>{{ $p->cheque_reference_number }}</td>
                    <td style="text-align:right;">{{ number_format($p->tax_amount ?? 0, 2) }}</td>
                    <td style="text-align:right;">{{ number_format($p->total_amount ?? 0, 2) }}</td>
                    <td>{{ $p->payment_type }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('society.addPayment', $p->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deletePayment', $p->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" style="text-align:center; color:#999;">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
