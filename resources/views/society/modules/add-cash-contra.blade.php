@extends('layouts.app')
@section('title', $editItem ? 'Edit Deposit/Contra/Withdraw' : 'Bank Deposit/Contra/Withdraw')
@section('content')
<div class="page-header">
    <h2>Bank Deposit/Contra/Withdraw</h2>
    <a href="{{ route('society.cashContra') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addCashContra', $editItem->id ?? '') }}">
        @csrf
        <div class="grid-3">
            <div class="form-group">
                <label>Type <span class="required">*</span></label>
                <select name="txn_type" id="txn_type" class="form-control" onchange="chkTxnType();" required>
                    <option value="">Select Type</option>
                    <option value="contra" {{ ($editItem->txn_type ?? '') == 'contra' ? 'selected' : '' }}>Contra</option>
                    <option value="deposit" {{ ($editItem->txn_type ?? '') == 'deposit' ? 'selected' : '' }}>Deposit</option>
                    <option value="withdraw" {{ ($editItem->txn_type ?? '') == 'withdraw' ? 'selected' : '' }}>Withdraw</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date <span class="required">*</span></label>
                <input type="date" class="form-control" name="payment_date" value="{{ ($editItem && $editItem->payment_date != '0000-00-00') ? $editItem->payment_date : '' }}" required>
            </div>
            <div class="form-group">
                <label>Bank <span class="required">*</span></label>
                <select name="bank_ledger_head_id" class="form-control" required>
                    <option value="">Select Bank</option>
                    @foreach($societyBankBalanceHeadsLists ?? [] as $lhId => $lhTitle)
                    <option value="{{ $lhId }}" {{ ($editItem->bank_ledger_head_id ?? '') == $lhId ? 'selected' : '' }}>{{ $lhTitle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="divContra" style="display:{{ ($editItem->txn_type ?? '') == 'contra' ? 'block' : 'none' }};">
                <label>Transfer to Bank <span class="required">*</span></label>
                <select name="bank_to_ledger_head_id" class="form-control">
                    <option value="">Select Bank</option>
                    @foreach($societyBankBalanceHeadsLists ?? [] as $lhId => $lhTitle)
                    <option value="{{ $lhId }}" {{ ($editItem->bank_to_ledger_head_id ?? '') == $lhId ? 'selected' : '' }}>{{ $lhTitle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" step="0.01" class="form-control" name="amount" value="{{ $editItem->amount ?? '' }}" placeholder="Enter Amount" required>
            </div>
            <div class="form-group">
                <label>Cheque No</label>
                <input type="text" class="form-control" name="cheque_no" value="{{ $editItem->cheque_no ?? '' }}" placeholder="Enter Cheque No.">
            </div>
            <div class="form-group">
                <label>Particulars</label>
                <input type="text" class="form-control" name="particulars" value="{{ $editItem->particulars ?? '' }}" placeholder="Enter particulars">
            </div>
            <div class="form-group">
                <label>Narration</label>
                <input type="text" class="form-control" name="narration" value="{{ $editItem->narration ?? '' }}" placeholder="Enter narration">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">Submit</button>
            <a href="{{ route('society.cashContra') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function chkTxnType() {
    var el = document.getElementById('divContra');
    el.style.display = document.getElementById('txn_type').value === 'contra' ? 'block' : 'none';
}
</script>
@endsection
