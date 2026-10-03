@extends('layouts.app')
@section('title', $editItem ? 'Edit Member Payment' : 'Add Member Payment')
@section('content')
<div class="page-header">
    <h2>{{ $editItem ? 'Edit Member Payment' : 'Make Payment' }}</h2>
    <a href="{{ route('society.memberPayments') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap;">
<div class="card" style="flex:1 1 560px; min-width:0;">
    <form method="POST" action="{{ route('society.addMemberPayment', $editItem->id ?? '') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Society Member <span class="required">*</span></label>
                <select name="member_id" class="form-control" required>
                    <option value="">Select Member</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ ($editItem && $editItem->member_id == $m->id) ? 'selected' : '' }}>{{ trim($m->member_prefix . ' ' . $m->member_name) }} ({{ $m->flat_no }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Payment Date</label>
                <input type="date" name="payment_date" class="form-control" value="{{ ($editItem && $editItem->payment_date && $editItem->payment_date != '0000-00-00') ? $editItem->payment_date : date('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label>Amount Paid <span class="required">*</span></label>
                <input type="number" step="0.01" name="amount_paid" class="form-control" value="{{ $editItem->amount_paid ?? '0' }}" required>
            </div>
            <div class="form-group">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control">
                    @foreach($paymentModes as $mId => $mName)
                    <option value="{{ $mId }}" {{ ($editItem && $editItem->payment_mode == $mId) ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Society Bank</label>
                <select name="society_bank_id" class="form-control">
                    <option value="">Select Bank</option>
                    @foreach($societyBanks as $sb)
                    <option value="{{ $sb->id }}" {{ ($editItem && $editItem->society_bank_id == $sb->id) ? 'selected' : '' }}>{{ $sb->ledgerHead->title ?? '' }} - {{ $sb->account_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cheque / Reference No</label>
                <input type="text" name="cheque_reference_number" class="form-control" value="{{ $editItem->cheque_reference_number ?? '' }}">
            </div>
            <div class="form-group">
                <label>Cheque Date</label>
                <input type="date" name="entry_date" class="form-control" value="{{ ($editItem && $editItem->entry_date) ? substr($editItem->entry_date, 0, 10) : '' }}">
            </div>
            <div class="form-group">
                <label>Credited Date</label>
                <input type="date" name="credited_date" class="form-control" value="{{ ($editItem && $editItem->credited_date && $editItem->credited_date != '0000-00-00') ? $editItem->credited_date : '' }}">
            </div>
            <div class="form-group">
                <label>Bank Slip No</label>
                <input type="text" name="bank_slip_no" class="form-control" value="{{ $editItem->bank_slip_no ?? '' }}">
            </div>
            <div class="form-group">
                <label>Narration</label>
                <input type="text" name="narration" class="form-control" value="{{ $editItem->narration ?? '' }}">
            </div>
            <div class="form-group">
                <label>Bill Type</label>
                <select name="bill_type" class="form-control">
                    <option value="reg" {{ ($editItem && $editItem->bill_type == 'reg') ? 'selected' : '' }}>Regular</option>
                    <option value="sup" {{ ($editItem && $editItem->bill_type == 'sup') ? 'selected' : '' }}>Supplementary</option>
                </select>
            </div>
        </div>

        <div style="margin-top:10px; padding-top:14px; border-top:1px dashed #ddd; font-weight:600; font-size:13px; color:#555;">Member Bank Details</div>
        <div class="grid-2">
            <div class="form-group">
                <label>Bank Name</label>
                <select name="member_bank_id" class="form-control">
                    <option value="">Select Bank</option>
                    @foreach($banks as $b)
                    <option value="{{ $b->id }}" {{ ($editItem && $editItem->member_bank_id == $b->id) ? 'selected' : '' }}>{{ $b->bank_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>IFSC Code</label>
                <input type="text" name="member_bank_ifsc" class="form-control" value="{{ $editItem->member_bank_ifsc ?? '' }}">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Branch</label>
                <textarea name="member_bank_branch" class="form-control" rows="2">{{ $editItem->member_bank_branch ?? '' }}</textarea>
            </div>
        </div>

        @if($editItem && $editItem->payment_mode == 3)
        <div class="grid-2" style="margin-top:10px; padding-top:14px; border-top:1px dashed #ddd;">
            <div class="form-group">
                <label>Cheque Return Date</label>
                <input type="date" name="cheque_return_date" class="form-control">
            </div>
            <div class="form-group">
                <label>Cheque Return Reason</label>
                <input type="text" name="cheque_return_reason" class="form-control">
            </div>
        </div>
        <p style="font-size:12px; color:#888; margin-top:-6px;">Filling in a Cheque Return Date and submitting marks this cheque as bounced and reverts it from the member's balance - it does not update the fields above.</p>
        @endif

        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Payment' : 'Save Payment' }}</button>
            <a href="{{ route('society.memberPayments') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>

{{-- Outstanding of the selected member (latest bill summary, port of Cake's getSocietyMembersOpBalance): Total Outstanding = principal + interest + tax --}}
<div class="card" style="flex:0 0 300px;">
    <div class="form-group">
        <label for="member_outstanding_payment">Total Outstanding</label>
        <input type="text" id="member_outstanding_payment" class="form-control" style="text-align:right;" readonly>
    </div>
    <div class="form-group">
        <label for="member_principle">Principle</label>
        <input type="text" id="member_principle" class="form-control" style="text-align:right;" readonly>
    </div>
    <div class="form-group">
        <label for="member_interest">Interest</label>
        <input type="text" id="member_interest" class="form-control" style="text-align:right;" readonly>
    </div>
    <div class="form-group" style="margin-bottom:0;">
        <label for="member_tax">Tax</label>
        <input type="text" id="member_tax" class="form-control" style="text-align:right;" readonly>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var member = document.querySelector('select[name="member_id"]');
    var ids = ['member_outstanding_payment', 'member_principle', 'member_interest', 'member_tax'];
    var url = '{{ url('society/get-member-op-balance') }}/';

    function clearPanel() { ids.forEach(function (id) { document.getElementById(id).value = ''; }); }

    function loadBalance() {
        clearPanel();
        if (!member.value) return;
        fetch(url + encodeURIComponent(member.value), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var b = d.MemberBillSummary;
                if (!b) return;
                var principal = parseFloat(b.principal_balance || 0), interest = parseFloat(b.interest_balance || 0), tax = parseFloat(b.tax_balance || 0);
                document.getElementById('member_outstanding_payment').value = (principal + interest + tax).toFixed(2);
                document.getElementById('member_principle').value = b.principal_balance;
                document.getElementById('member_interest').value = b.interest_balance;
                document.getElementById('member_tax').value = b.tax_balance;
            });
    }

    member.addEventListener('change', loadBalance);
    loadBalance();
})();
</script>
@endsection
