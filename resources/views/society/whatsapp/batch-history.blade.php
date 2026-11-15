{{-- CakePHP app/View/SocietyBills/whatsapp_batch_history.ctp --}}
@extends('layouts.app')
@section('title', 'WhatsApp Batch History - EasyLogics')
@section('content')
<style>
.chip { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.chip-IN_PROGRESS { background:#dbeafe; color:#2563eb; }
.chip-COMPLETED { background:#dcfce7; color:#16a34a; }
.chip-PARTIAL { background:#fef3c7; color:#d97706; }
.chip-STOPPED { background:#fee2e2; color:#dc2626; }
</style>
<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h2>&#128338; WhatsApp Batch History</h2>
    <div>
        <a href="{{ route('society.whatsappBill') }}" class="btn btn-success btn-sm">&#128172; Send WhatsApp Bill</a>
        <a href="{{ route('society.dashboard') }}" class="btn btn-default btn-sm">Dashboard</a>
    </div>
</div>
<div class="card">
    @if ($batches->isEmpty())
        <div class="alert alert-info">No WhatsApp batches found. Send your first batch from the <a href="{{ route('society.whatsappBill') }}">Send WhatsApp Bill</a> page.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>Sr</th><th>Batch ID</th><th>Month</th><th>Total</th><th>Sent</th><th>Delivered</th><th>Read</th>
                        <th>Failed</th><th>Pending</th><th>API Limit</th><th>Invalid Phone</th><th>PDF Failed</th>
                        <th>Status</th><th>Stopped At</th><th>Created</th><th>Completed</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $i => $b)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-family:monospace;font-size:11px;">{{ $b->batch_id }}</td>
                        <td>{{ $b->bill_month }}</td>
                        <td><strong>{{ $b->total_bills }}</strong></td>
                        <td style="color:#16a34a;font-weight:600;">{{ $b->sent_count }}</td>
                        <td style="color:#2563eb;font-weight:600;">{{ $b->delivered_count ?? 0 }}</td>
                        <td style="color:#4f46e5;font-weight:600;">{{ $b->read_count ?? 0 }}</td>
                        <td style="color:#dc2626;font-weight:600;">{{ $b->failed_count }}</td>
                        <td style="color:#d97706;font-weight:600;">{{ $b->pending_count }}</td>
                        <td style="color:#7c3aed;font-weight:600;">{{ $b->limit_count }}</td>
                        <td style="color:#db2777;font-weight:600;">{{ $b->invalid_phone_count }}</td>
                        <td style="color:#4b5563;font-weight:600;">{{ $b->pdf_failed_count }}</td>
                        <td><span class="chip chip-{{ $b->status }}">{{ $b->status }}</span></td>
                        <td style="font-size:11px;">{{ $b->stopped_at_bill ?: '—' }}</td>
                        <td style="font-size:11px;">{{ $b->created_at }}</td>
                        <td style="font-size:11px;">{{ $b->completed_at ?: '—' }}</td>
                        <td><a href="{{ route('society.whatsappBill', ['batch' => $b->batch_id]) }}" class="btn btn-xs btn-info">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
