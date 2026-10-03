@extends('layouts.app')

@section('title', 'Reseller Payments - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Reseller Payments</h2>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Reseller</th>
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
                <td>{{ $p->reseller_username }}</td>
                <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d-M-Y') }}</td>
                <td class="text-right">{{ number_format($p->amount, 2) }}</td>
                <td>{{ $p->payment_mode }}</td>
                <td>{{ $p->extended_till ? \Carbon\Carbon::parse($p->extended_till)->format('d-M-Y') : '-' }}</td>
                <td>{{ $p->remarks }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-muted">No payments logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="max-width: 500px; margin-top: 16px;">
    <h3 style="margin-bottom:12px;">Log Renewal Payment</h3>
    <form method="POST" action="{{ route('admin.reports.resellerPayments') }}">
        @csrf
        <div class="form-group">
            <label>Reseller <span class="required">*</span></label>
            <select class="form-control" name="reseller_id" required>
                <option value="">-- Select active Reseller --</option>
                @foreach($resellersList as $uid => $uname)
                    <option value="{{ $uid }}">{{ $uname }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Amount <span class="required">*</span></label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
        </div>
        <div class="form-group">
            <label>Payment Date</label>
            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label>Payment Mode</label>
            <input type="text" class="form-control" name="payment_mode" placeholder="e.g. Cash, UPI, Bank Transfer">
        </div>
        <div class="form-group">
            <label>Remarks</label>
            <input type="text" class="form-control" name="remarks" maxlength="255">
        </div>
        <p class="text-muted" style="font-size:12px;">Logging a payment extends the reseller's subscription by 365 days from their current expiry (or today, if already expired).</p>
        <button type="submit" class="btn btn-primary">Log Payment &amp; Extend</button>
    </form>
</div>
@endsection
