@extends('layouts.app')
@section('title', 'Journal Voucher')
@section('content')
<style>
#datable_1 thead th { text-transform: uppercase; background: #009688 !important; color: #fff !important; font-size: 11px; }
.jv-entry-row { display:flex; gap:10px; align-items:flex-end; background:#fafbfc; border:1px solid #eee; border-radius:8px; padding:10px; margin-bottom:8px; }
.jv-entry-row .form-group { margin:0; flex:1; min-width:0; }
.jv-entry-row .jv-remove { flex:0 0 auto; }
#jv-totals { display:flex; gap:20px; align-items:center; margin-top:8px; font-weight:bold; }
</style>
<div class="page-header">
    <h2>Journal Voucher</h2>
</div>

<div class="card" style="margin-bottom:16px;">
    <form method="POST" action="{{ route('society.journalVoucher', $voucherNo ?? '') }}" id="jvForm">
        @csrf
        <input type="hidden" name="JournalVoucher[voucher_no]" value="{{ $voucherNo ?? '' }}">
        <div class="grid-3">
            <div class="form-group">
                <label>Voucher Date</label>
                <input type="date" class="form-control" name="JournalVoucher[voucher_date]" value="{{ $editRows->first()->voucher_date ?? old('voucher_date', date('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label>Arrears Adjustment</label>
                <div>
                    <label style="font-weight:normal; margin-right:10px;"><input type="radio" name="payment[type][]" value="Principal Arrears"> Principal Arrears</label>
                    <label style="font-weight:normal; margin-right:10px;"><input type="radio" name="payment[type][]" value="Interest Arrears"> Interest Arrears</label>
                    <label style="font-weight:normal;"><input type="radio" name="payment[type][]" value="Tax Arrears"> Tax Arrears</label>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea class="form-control" name="JournalVoucher[note]" rows="1">{{ $editRows->first()->note ?? '' }}</textarea>
            </div>
        </div>

        @if($editRows->isEmpty())
        <div id="jv-grid-header" style="display:flex; gap:10px; background:#eef2f9; border-radius:8px; padding:8px 10px; margin-bottom:8px; font-size:12px; text-transform:uppercase; color:#2F4B7C; font-weight:600;">
            <div style="flex:1;">Type</div>
            <div style="flex:1;">Account Name</div>
            <div style="flex:1;">Dr. Amount</div>
            <div style="flex:1;">Cr. Amount</div>
            <div style="flex:0 0 30px;"></div>
        </div>
        <div id="jv-rows"></div>
        <div id="jv-totals">
            <button type="button" class="btn btn-success btn-sm" onclick="addJvRow();">+ Add More</button>
            <span>Total Dr: <span id="jv-total-debit">0.00</span></span>
            <span>Total Cr: <span id="jv-total-credit">0.00</span></span>
            <span id="jv-balance-note"></span>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#eef2f9;">
                        <th style="padding:6px;">Sr</th>
                        <th style="padding:6px;">Type</th>
                        <th style="padding:6px;">Member</th>
                        <th style="padding:6px;">Ledger</th>
                        <th style="padding:6px;">Dr Amount</th>
                        <th style="padding:6px;">Cr Amount</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($editRows as $i => $row)
                    @php
                        $jvMemberId = $row->jv_type == 'Credit' ? $row->jv_credit_member_head_id : $row->jv_debit_member_head_id;
                        $jvLedgerId = $row->jv_type == 'Credit' ? $row->jv_credit_ledger_head_id : $row->jv_debit_ledger_head_id;
                    @endphp
                    <tr>
                        <td style="padding:6px;">{{ $i + 1 }}</td>
                        <td style="padding:6px;">
                            <select class="form-control" name="{{ $row->id }}[JournalVoucher][jv_type]" onchange="jvToggleEditRow(this)" style="width:110px;">
                                <option value="Credit" {{ $row->jv_type == 'Credit' ? 'selected' : '' }}>Credit</option>
                                <option value="Debit" {{ $row->jv_type == 'Debit' ? 'selected' : '' }}>Debit</option>
                            </select>
                        </td>
                        <td style="padding:6px;">
                            <select class="form-control" name="{{ $row->id }}[JournalVoucher][jv_member_head_id]" style="min-width:200px;">
                                <option value="">Select Member</option>
                                <optgroup label="Society Members List">
                                    @foreach($memberList as $mId => $mName)
                                    <option value="{{ $mId }}" {{ $jvMemberId == $mId ? 'selected' : '' }}>{{ $mName }} -- {{ $flatNoList[$mId] ?? '' }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Society Old Members List">
                                    @foreach($oldMemberList as $om)
                                    <option value="member-{{ $om->member_id }}-old">{{ $om->third_member }} -- {{ $om->flat_no }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </td>
                        <td style="padding:6px;">
                            <select class="form-control" name="{{ $row->id }}[JournalVoucher][jv_ledger_head_id]" style="min-width:180px;">
                                <option value="">Select Ledger</option>
                                @foreach($ledgerHeadList as $lId => $lName)
                                <option value="{{ $lId }}" {{ $jvLedgerId == $lId ? 'selected' : '' }}>{{ $lName }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="padding:6px;">
                            <input type="text" class="form-control jv-edit-debit" name="{{ $row->id }}[JournalVoucher][jv_amount_debited]" value="{{ $row->jv_amount_debited }}" {{ $row->jv_type == 'Credit' ? 'disabled' : '' }} style="width:110px;">
                        </td>
                        <td style="padding:6px;">
                            <input type="text" class="form-control jv-edit-credit" name="{{ $row->id }}[JournalVoucher][jv_amount_credited]" value="{{ $row->jv_amount_credited }}" {{ $row->jv_type == 'Debit' ? 'disabled' : '' }} style="width:110px;">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div style="margin-top:14px;">
            <button type="button" class="btn btn-success" onclick="jvValidateAndSubmit();">Submit</button>
            <a href="{{ route('society.journalVoucher') }}" class="btn" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table id="datable_1" class="display" style="width:100%;">
            <thead>
                <tr style="background:#009688; color:#fff;">
                    <th style="color:#fff;">#</th>
                    <th style="color:#fff;">Voucher No.</th>
                    <th style="color:#fff;">Voucher Date</th>
                    <th style="color:#fff;">Type</th>
                    <th style="color:#fff;">Dr. Account Name</th>
                    <th style="color:#fff; text-align:right;">Dr. Amount</th>
                    <th style="color:#fff;">Cr. Account Name</th>
                    <th style="color:#fff; text-align:right;">Cr. Amount</th>
                    <th style="color:#fff;">Notes</th>
                    <th style="color:#fff; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $jv)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $jv->voucher_no }}</td>
                    <td>{{ $jv->voucher_date && $jv->voucher_date != '0000-00-00' ? \Illuminate\Support\Carbon::parse($jv->voucher_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ $jv->jv_type }}</td>
                    <td>{{ $jv->debit_title ?? '' }}</td>
                    <td style="text-align:right;">{{ $jv->jv_amount_debited }}</td>
                    <td>{{ $jv->creadit_title ?? '' }}</td>
                    <td style="text-align:right;">{{ $jv->jv_amount_credited }}</td>
                    <td>{{ $jv->note }}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <a href="{{ route('society.journalVoucher', $jv->voucher_no) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteJournalVoucher', $jv->voucher_no) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete # {{ $jv->voucher_no }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; color:#999;">No journal vouchers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@php
    $jvMemberOptions = collect($memberList)->map(fn($name, $id) => ['value' => (string) $id, 'label' => $name . ' -- ' . ($flatNoList[$id] ?? '')])->values();
    $jvOldMemberOptions = collect($oldMemberList)->map(fn($om) => ['value' => 'member-' . $om->member_id . '-old', 'label' => $om->third_member . ' -- ' . $om->flat_no])->values();
    $jvLedgerOptions = collect($ledgerHeadList)->map(fn($name, $id) => ['value' => 'ledger-' . $id, 'label' => $name])->values();
@endphp
@endsection

@section('scripts')
<script>
var jvMemberOptions = {!! $jvMemberOptions->toJson() !!};
var jvOldMemberOptions = {!! $jvOldMemberOptions->toJson() !!};
var jvLedgerOptions = {!! $jvLedgerOptions->toJson() !!};
var jvRowCounter = 0;

if ($.fn.DataTable) {
    $('#datable_1').DataTable({ pageLength: 100, lengthMenu: [10, 25, 50, 100, 250], order: [], autoWidth: false, columnDefs: [{ orderable: false, targets: [9] }] });
}

function jvBuildAccountSelect(name) {
    var html = '<select class="form-control jv-account" name="' + name + '" style="width:100%;"><option value="">Select</option>';
    html += '<optgroup label="Society Members List">';
    jvMemberOptions.forEach(function (o) { html += '<option value="member-' + o.value + '">' + jvEsc(o.label) + '</option>'; });
    html += '</optgroup><optgroup label="Society Old Members List">';
    jvOldMemberOptions.forEach(function (o) { html += '<option value="' + o.value + '">' + jvEsc(o.label) + '</option>'; });
    html += '</optgroup><optgroup label="Society Ledger List">';
    jvLedgerOptions.forEach(function (o) { html += '<option value="' + o.value + '">' + jvEsc(o.label) + '</option>'; });
    html += '</optgroup></select>';
    return html;
}

function jvEsc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function addJvRow() {
    jvRowCounter++;
    var row = document.createElement('div');
    row.className = 'jv-entry-row';
    row.innerHTML =
        '<div class="form-group">' +
            '<select class="form-control jv-type" name="JournalVoucher[type][]" onchange="jvDisableInputs(this)">' +
                '<option value="Credit">Credit</option><option value="Debit">Debit</option>' +
            '</select>' +
        '</div>' +
        '<div class="form-group">' + jvBuildAccountSelect('JournalVoucher[jv_member_ledger_head_id][]') + '</div>' +
        '<div class="form-group"><input type="text" class="form-control jv-debit" name="JournalVoucher[jv_amount_debit][]" placeholder="0.00" value="0" readonly></div>' +
        '<div class="form-group"><input type="text" class="form-control jv-credit" name="JournalVoucher[jv_amount_credit][]" placeholder="0.00" value="0"></div>' +
        '<div class="jv-remove"><button type="button" class="btn" style="background:#dc3545; color:#fff;" onclick="this.closest(\'.jv-entry-row\').remove(); jvUpdateTotals();">&times;</button></div>';
    document.getElementById('jv-rows').appendChild(row);
    jvUpdateTotals();
}

function jvDisableInputs(select) {
    var row = select.closest('.jv-entry-row');
    var debit = row.querySelector('.jv-debit');
    var credit = row.querySelector('.jv-credit');
    if (select.value === 'Credit') {
        debit.readOnly = true; debit.value = 0;
        credit.readOnly = false;
    } else {
        credit.readOnly = true; credit.value = 0;
        debit.readOnly = false;
    }
    jvUpdateTotals();
}

function jvToggleEditRow(select) {
    var row = select.closest('tr');
    var debit = row.querySelector('.jv-edit-debit');
    var credit = row.querySelector('.jv-edit-credit');
    if (select.value === 'Credit') {
        debit.disabled = true;
        credit.disabled = false;
    } else {
        credit.disabled = true;
        debit.disabled = false;
    }
}

function jvSums() {
    var sum = function (selector) {
        var total = 0;
        document.querySelectorAll(selector).forEach(function (el) {
            var v = parseFloat(el.value);
            if (!isNaN(v)) total += v;
        });
        return total;
    };
    return { debit: sum('.jv-debit'), credit: sum('.jv-credit') };
}

function jvUpdateTotals() {
    var sums = jvSums();
    var difference = Math.round((sums.debit - sums.credit) * 100) / 100;
    var debitEl = document.getElementById('jv-total-debit');
    var creditEl = document.getElementById('jv-total-credit');
    if (debitEl) debitEl.textContent = sums.debit.toFixed(2);
    if (creditEl) creditEl.textContent = sums.credit.toFixed(2);
    var note = document.getElementById('jv-balance-note');
    if (!note) return;
    if (sums.debit === 0 && sums.credit === 0) {
        note.textContent = ''; note.style.color = '';
    } else if (difference === 0) {
        note.textContent = 'Debit and credit match.'; note.style.color = '#3c763d';
    } else {
        note.textContent = 'Out of balance by ' + Math.abs(difference).toFixed(2) + ' - ' + (difference > 0 ? 'credit' : 'debit') + ' is short.';
        note.style.color = '#a94442';
    }
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('jv-debit') || e.target.classList.contains('jv-credit')) jvUpdateTotals();
});

function jvValidateAndSubmit() {
    var sums = jvSums();
    var difference = Math.round((sums.debit - sums.credit) * 100) / 100;
    jvUpdateTotals();
    if (sums.debit !== 0 && sums.credit !== 0 && difference === 0) {
        document.getElementById('jvForm').submit();
    } else {
        alert('Please check the credit and debit amount and then submit the form');
    }
}

@if($editRows->isEmpty())
addJvRow();
@endif
</script>
@endsection
