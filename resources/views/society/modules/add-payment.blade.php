@extends('layouts.app')
@section('title', $editItem ? 'Edit Payment' : 'Add Payment')
@section('content')
<div class="page-header">
    <h2>{{ $editItem ? 'Edit Payment' : 'Add Payment' }}</h2>
    <a href="{{ route('society.payments') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addPayment', $editItem->id ?? '') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Paid To (Ledger Head) <span class="required">*</span></label>
                <select name="ledger_head_id" class="form-control" required>
                    <option value="">Select</option>
                    @foreach($ledgerHeads as $lhId => $lhTitle)
                    <option value="{{ $lhId }}" {{ ($editItem && $editItem->ledger_head_id == $lhId) ? 'selected' : '' }}>{{ $lhTitle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Particulars</label>
                <input type="text" name="particulars" class="form-control" value="{{ $editItem->particulars ?? '' }}">
            </div>
            <div class="form-group">
                <label>Amount <span class="required">*</span></label>
                <input type="number" step="0.01" name="amount" class="form-control" id="amountField" value="{{ $editItem->amount ?? '0' }}" required oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Tax Amount</label>
                <input type="number" step="0.01" name="tax_amount" class="form-control" id="taxField" value="{{ $editItem->tax_amount ?? '0' }}" oninput="calcTotal()">
            </div>
            <div class="form-group">
                <label>Total Amount</label>
                <input type="number" step="0.01" name="total_amount" class="form-control" id="totalField" value="{{ $editItem->total_amount ?? '0' }}" readonly>
            </div>
            <div class="form-group">
                <label>Bill Voucher Number</label>
                <input type="text" name="bill_voucher_number" class="form-control" value="{{ $editItem->bill_voucher_number ?? '' }}">
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" class="form-control" value="{{ ($editItem && $editItem->payment_date != '0000-00-00') ? $editItem->payment_date : '' }}">
            </div>
            <div class="form-group">
                <label>Payment Type</label>
                <select name="payment_type" class="form-control">
                    <option value="Cash" {{ ($editItem && $editItem->payment_type == 'Cash') ? 'selected' : '' }}>Cash</option>
                    <option value="Cheque" {{ ($editItem && $editItem->payment_type == 'Cheque') ? 'selected' : '' }}>Cheque</option>
                    <option value="Online" {{ ($editItem && $editItem->payment_type == 'Online') ? 'selected' : '' }}>Online</option>
                    <option value="NEFT" {{ ($editItem && $editItem->payment_type == 'NEFT') ? 'selected' : '' }}>NEFT</option>
                    <option value="RTGS" {{ ($editItem && $editItem->payment_type == 'RTGS') ? 'selected' : '' }}>RTGS</option>
                </select>
            </div>
            <div class="form-group">
                <label>Cheque Reference No</label>
                <input type="text" name="cheque_reference_number" class="form-control" value="{{ $editItem->cheque_reference_number ?? '' }}">
            </div>
            <div class="form-group">
                <label>Cheque Date</label>
                <input type="date" name="cheque_date" class="form-control" value="{{ ($editItem && $editItem->cheque_date != '0000-00-00') ? $editItem->cheque_date : '' }}">
            </div>
            <div class="form-group">
                <label>Clear/Debited Date</label>
                <input type="date" name="debited_date" class="form-control" value="{{ ($editItem && $editItem->debited_date != '0000-00-00') ? $editItem->debited_date : '' }}">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ $editItem->notes ?? '' }}">
            </div>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Payment' : 'Add Payment' }}</button>
            <a href="{{ route('society.payments') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>

<script>
function calcTotal() {
    var amt = parseFloat(document.getElementById('amountField').value) || 0;
    var tax = parseFloat(document.getElementById('taxField').value) || 0;
    document.getElementById('totalField').value = (amt - tax).toFixed(2);
}
</script>
@endsection
