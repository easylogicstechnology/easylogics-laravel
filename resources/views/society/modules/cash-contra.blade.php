@extends('layouts.app')
@section('title', 'Society Cash Contra')
@section('content')
<div class="page-header">
    <h2>Bank Deposit / Contra / Withdraw</h2>
    <a href="{{ route('society.addCashContra') }}" class="btn btn-primary btn-sm">+ Add Deposit/Contra/Withdraw</a>
</div>

<div class="card">
    <style>
        #datable_1 thead th { text-transform: uppercase; background: #009688 !important; color: #fff !important; font-size: 11px; }
    </style>
    <div style="overflow-x:auto;">
        <table id="datable_1" class="display" style="width:100%;">
            <thead>
                <tr style="background:#009688; color:#fff;">
                    <th style="color:#fff;">#</th>
                    <th style="color:#fff;">Date</th>
                    <th style="color:#fff;">Type</th>
                    <th style="color:#fff;">Bank</th>
                    <th style="color:#fff; text-align:right;">Amount</th>
                    <th style="color:#fff;">Cheque No</th>
                    <th style="color:#fff;">Particulars</th>
                    <th style="color:#fff;">Narration</th>
                    <th style="color:#fff; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->payment_date && $item->payment_date != '0000-00-00' ? \Illuminate\Support\Carbon::parse($item->payment_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ ucfirst($item->txn_type) }}</td>
                    <td>
                        {{ $item->bankLedgerHead->title ?? '-' }}
                        @if($item->txn_type == 'contra' && $item->bank_to_ledger_head_id)
                            &rarr; {{ \App\Models\SocietyLedgerHead::find($item->bank_to_ledger_head_id)->title ?? '-' }}
                        @endif
                    </td>
                    <td style="text-align:right;">{{ number_format($item->amount ?? 0, 2) }}</td>
                    <td>{{ $item->cheque_no }}</td>
                    <td>{{ $item->particulars }}</td>
                    <td>{{ $item->narration }}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <a href="{{ route('society.addCashContra', $item->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteCashContra', $item->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" style="text-align:center; color:#999;">No cash contra entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
if ($.fn.DataTable) {
    $('#datable_1').DataTable({ pageLength: 100, lengthMenu: [10, 25, 50, 100, 250], order: [], autoWidth: false, columnDefs: [{ orderable: false, targets: [8] }] });
}
</script>
@endsection
