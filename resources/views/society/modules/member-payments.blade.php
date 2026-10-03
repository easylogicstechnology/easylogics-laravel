@extends('layouts.app')
@section('title', 'Member Payments')
@section('content')
<div class="page-header">
    <h2>Member Payments</h2>
    <div>
        <a href="{{ route('society.downloadSampleMemberPaymentTemplate') }}" class="btn btn-sm" style="margin-right:6px; background:#17a2b8; color:#fff;" title="Download Excel sample template with member flat details">
            &#8681; Download Sample
        </a>
        <a href="{{ route('society.importMemberPayments') }}" class="btn btn-sm" style="margin-right:6px; background:#5bc0de; color:#fff;" title="Upload a filled Excel file to import payments">
            &#128228; Import from Excel
        </a>
        <a href="{{ route('society.memberReceiptBulkPaste') }}" class="btn btn-success btn-sm" style="margin-right:6px;" title="Excel se Copy-Paste bulk payment entry">
            &#128203; Bulk Paste
        </a>
        <a href="{{ route('society.addMemberPayment') }}" class="btn btn-primary btn-sm">+ Make Payment</a>
    </div>
</div>

@if($paymentsLocked)
<div class="alert" style="margin-bottom:12px; background:#fff3cd; color:#856404; border:1px solid #ffeeba;">&#128274; Payments are <b>locked</b>: Current Bill Update is set to <b>Yes</b> in Society Parameters, so a payment cannot be added, edited or deleted. Change that setting to unlock them.</div>
@endif

<div class="card">
    <style>
        #datable_2 thead th { text-transform: uppercase; background: #009688 !important; color: #fff !important; font-size: 11px; }
    </style>
    <div style="overflow-x:auto;">
        <table id="datable_2" class="display" style="width:100%;">
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
                @foreach($items as $i => $p)
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
                    <td style="text-align:center; white-space:nowrap;">
                        @if($paymentsLocked)
                        <span title="Locked - Current Bill Update is Yes in Society Parameters" style="color:#999; margin-right:6px; font-size:16px;">&#128274;</span>
                        @else
                        <a href="{{ route('society.addMemberPayment', $p->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteMemberPayment', $p->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px; margin-right:6px;">&#10006;</button>
                        </form>
                        @endif
                        <a href="{{ route('society.memberPaymentVoucherPdf', $p->id) }}" target="_blank" title="Download PDF" style="color:#c0392b; margin-right:6px; text-decoration:none; font-weight:700; font-size:11px;">PDF</a>
                        <a href="{{ route('society.memberPaymentVoucher', $p->id) }}" target="_blank" title="View / Print Voucher" style="color:#2980b9; text-decoration:none; font-weight:700; font-size:11px;">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function(){
    if ($.fn.DataTable) {
        $('#datable_2').DataTable({
            pageLength: 100,
            lengthMenu: [10, 25, 50, 100, 250],
            order: [],
            autoWidth: false,
            language: { emptyTable: 'No payments found.' },
            columnDefs: [
                { orderable: false, targets: [10] }
            ]
        });
    }
});
</script>
@endsection
