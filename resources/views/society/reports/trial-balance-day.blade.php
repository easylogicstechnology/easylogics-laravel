@extends('layouts.app')
@section('title', 'Din ki Transactions')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Din ki Transactions - {{ date('d/m/Y', strtotime($date)) }}</h2>
</div>

<div class="card ar-report">
    <p style="color:#666;">
        <a href="{{ route('society.reports.trialBalanceDiff') }}">&laquo; Trial Balance Diff par wapas jayein</a>
        &mdash; is din ki entry edit/delete karke Trial Balance mismatch theek karein.
    </p>

    <h6 style="margin-top:20px;font-weight:600;">Society Payments (Expenses)</h6>
    <table>
        <thead><tr><th>Ledger Head</th><th>Paid From (Bank/Cash)</th><th>Particulars</th><th class="text-right">Amount</th><th class="text-center">Action</th></tr></thead>
        <tbody>
            @forelse($societyPayments as $p)
                <tr @if(empty($p->payment_by_ledger_id)) style="background-color:#fff6f6;" @endif>
                    <td>{{ $p->ledger_title ?? $p->ledger_head_id }}</td>
                    <td>
                        @if(empty($p->payment_by_ledger_id))
                            <span style="color:#d9534f;font-weight:bold;" title="Pay-From ledger set nahi hai - isi wajah se Trial Balance mismatch hota hai">&#9888; Set nahi hai</span>
                        @else
                            {{ $ledgerHeadTitles[$p->payment_by_ledger_id] ?? $p->payment_by_ledger_id }}
                        @endif
                    </td>
                    <td>{{ $p->particulars }}</td>
                    <td class="text-right">{{ number_format((float) $p->total_amount, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('society.addPayment', $p->id) }}" target="_blank" title="Edit">Edit</a>
                        &nbsp;
                        <form method="post" action="{{ route('society.deletePayment', $p->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete payment # {{ $p->id }}?');">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete" style="border:0;background:none;color:#d9534f;cursor:pointer">&times;</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">Is din koi Society Payment nahi mila.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h6 style="margin-top:20px;font-weight:600;">General Receipts (Other Income)</h6>
    <table>
        <thead><tr><th>Ledger Head</th><th>Title / Description</th><th class="text-right">Amount</th><th class="text-center">Action</th></tr></thead>
        <tbody>
            @forelse($societyOtherIncomes as $o)
                <tr>
                    <td>{{ $o->ledger_title ?? $o->ledger_head_id }}</td>
                    <td>{{ $o->title }}@if(!empty($o->description)) - {{ $o->description }}@endif</td>
                    <td class="text-right">{{ number_format((float) $o->amount_paid, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('society.generalReceipt') }}" target="_blank" title="General Receipts list mein dhoond kar edit karein">Edit list mein &rarr;</a>
                        &nbsp;
                        <form method="post" action="{{ route('society.deleteGeneralReceipt', $o->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete receipt # {{ $o->id }}?');">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete" style="border:0;background:none;color:#d9534f;cursor:pointer">&times;</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Is din koi General Receipt nahi mila.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h6 style="margin-top:20px;font-weight:600;">Journal Vouchers</h6>
    <table>
        <thead><tr><th>Debit Ledger</th><th>Credit Ledger</th><th class="text-right">Amount</th><th>Note</th><th class="text-center">Action</th></tr></thead>
        <tbody>
            @forelse($journalVouchers as $j)
                <tr @if($j->jv_amount_debited != $j->jv_amount_credited) style="background-color:#fff6f6;" @endif>
                    <td>{{ $ledgerHeadTitles[$j->jv_debit_ledger_head_id] ?? $j->jv_debit_ledger_head_id }} ({{ number_format((float) $j->jv_amount_debited, 2) }})</td>
                    <td>{{ $ledgerHeadTitles[$j->jv_credit_ledger_head_id] ?? $j->jv_credit_ledger_head_id }} ({{ number_format((float) $j->jv_amount_credited, 2) }})</td>
                    <td class="text-right">{{ number_format((float) $j->jv_amount_debited, 2) }}</td>
                    <td>{{ $j->note }}</td>
                    <td class="text-center">
                        <a href="{{ route('society.reports.journalVoucherRegister') }}" target="_blank" title="Journal Voucher Register mein dhoond kar edit karein">Register mein &rarr;</a>
                        &nbsp;
                        <form method="post" action="{{ route('society.deleteJournalVoucher', $j->voucher_no) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete voucher # {{ $j->voucher_no }}?');">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete" style="border:0;background:none;color:#d9534f;cursor:pointer">&times;</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">Is din koi Journal Voucher nahi mila.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
