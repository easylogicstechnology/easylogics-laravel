@extends('layouts.app')
@section('title', 'General Receipt (Non-Member Bank / Cash Receipt)')
@section('content')
<style>
.hide_content.hidden-by-type { display: none !important; }
#generalReceiptRows th { background:#009688; color:#fff; font-size:11px; text-transform:uppercase; padding:6px; white-space:nowrap; }
#generalReceiptRows td { padding:2px; }
#generalReceiptRows input, #generalReceiptRows select, #generalReceiptRows textarea { width:100%; box-sizing:border-box; padding:4px 6px; font-size:12px; }
</style>
<div class="page-header">
    <h2>General Receipt (Non-Member Bank / Cash Receipt)</h2>
    <a href="{{ route('society.generalReceipt') }}" class="btn btn-primary btn-sm">Back to List</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addGeneralReceipt', $editItems->first()->id ?? '') }}" id="society_general_receipt">
        @csrf
        <div class="grid-3">
            <div class="form-group">
                <label>Type <span class="required">*</span></label>
                <select name="SocietyOtherIncomeHeader[payment_mode]" id="payment_type" class="form-control" onchange="hideChequeDetailsFields(this.value);" required>
                    <option value="">Select payment mode</option>
                    <option value="Bank" {{ (($editItems->first()->payment_mode ?? '') == 'Bank') ? 'selected' : '' }}>Bank</option>
                    <option value="Cash" {{ (($editItems->first()->payment_mode ?? '') == 'Cash') ? 'selected' : '' }}>Cash</option>
                </select>
            </div>
            <div class="form-group">
                <label>By <span class="required">*</span></label>
                <select class="form-control" id="society_payment_by_ledger" name="SocietyOtherIncomeHeader[society_bank_id]" required>
                    <option value="">Select Vendor</option>
                </select>
            </div>
            <div class="form-group" style="align-self:end;">
                <label class="checkbox-inline" style="font-weight:normal;">
                    <input type="checkbox" name="generate_one_voucher" value="Y"> Below Payment(s) To Be Created A Single Voucher
                </label>
            </div>
        </div>

        <div style="margin:10px 0;">
            <button type="button" onclick="saveSocietyGeneralReciepts();" class="btn btn-success">Save General Receipt</button>
            <a href="{{ route('society.generalReceipt') }}" class="btn" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
        </div>

        <div style="overflow-x:auto;">
            <table id="generalReceiptRows" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th style="min-width:160px;">Paid&nbsp;to</th>
                        <th style="min-width:90px;">Amount</th>
                        <th style="min-width:130px;">TDS Bank</th>
                        <th style="min-width:80px;">TDS Amount</th>
                        <th style="min-width:90px;">Net Amount</th>
                        <th style="min-width:130px;">Payment&nbsp;Date</th>
                        <th class="hide_content" style="min-width:130px;">Chq.&nbsp;Date</th>
                        <th class="hide_content" style="min-width:100px;">Chq.&nbsp;No</th>
                        <th class="hide_content" style="min-width:120px;">Bank&nbsp;Name</th>
                        <th style="min-width:140px;">Particulars</th>
                        <th style="min-width:140px;">Remark</th>
                    </tr>
                </thead>
                <tbody>
                @if($editItems->count() > 0)
                    @php $counter = 1; @endphp
                    <tr>
                    @foreach($editItems as $row)
                        <td>{{ $counter }}</td>
                        <td>
                            <input type="hidden" name="SocietyOtherIncome[{{ $counter }}][id]" value="{{ $row->id }}">
                            <select name="SocietyOtherIncome[{{ $counter }}][ledger_head_id]">
                                <option value="">Select Ledger</option>
                                @foreach($societyOtherIncomeHeadsLists as $lhId => $lhTitle)
                                <option value="{{ $lhId }}" {{ $row->ledger_head_id == $lhId ? 'selected' : '' }}>{{ $lhTitle }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="numeric" name="SocietyOtherIncome[{{ $counter }}][amount_paid]" value="{{ $row->amount_paid }}"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><input type="date" name="SocietyOtherIncome[{{ $counter }}][payment_date]" value="{{ $row->payment_date }}"></td>
                        <td class="hide_content"><input type="date" name="SocietyOtherIncome[{{ $counter }}][cheque_date]" value="{{ $row->cheque_date }}"></td>
                        <td class="hide_content"><input type="text" name="SocietyOtherIncome[{{ $counter }}][cheque_no]" value="{{ $row->cheque_no }}"></td>
                        <td class="hide_content"></td>
                        <td><input type="text" name="SocietyOtherIncome[{{ $counter }}][title]" value="{{ $row->title }}"></td>
                        <td><input type="text" name="SocietyOtherIncome[{{ $counter }}][description]" value="{{ $row->description }}"></td>
                    </tr>
                    @endforeach
                @else
                    @for($i = 1; $i <= 9; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td>
                            <select name="SocietyOtherIncome[{{ $i }}][ledger_head_id]">
                                <option value="">Select Ledger</option>
                                @foreach($societyOtherIncomeHeadsLists as $lhId => $lhTitle)
                                <option value="{{ $lhId }}">{{ $lhTitle }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="numeric" name="SocietyOtherIncome[{{ $i }}][amount_paid]" onchange="calcRowNetAmount({{ $i }})"></td>
                        <td>
                            <select name="SocietyOtherIncome[{{ $i }}][tds_bank_id]">
                                <option value="">Select Tds</option>
                                @foreach($societyOtherIncomeHeadsLists as $lhId => $lhTitle)
                                <option value="{{ $lhId }}">{{ $lhTitle }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="numeric" id="tds_amount{{ $i }}" name="SocietyOtherIncome[{{ $i }}][tds_amount]" onchange="calcRowNetAmount({{ $i }})"></td>
                        <td><input type="text" class="numeric" id="net_amount{{ $i }}" name="SocietyOtherIncome[{{ $i }}][net_amount]"></td>
                        <td><input type="date" name="SocietyOtherIncome[{{ $i }}][payment_date]"></td>
                        <td class="hide_content"><input type="date" name="SocietyOtherIncome[{{ $i }}][cheque_date]"></td>
                        <td class="hide_content"><input type="text" name="SocietyOtherIncome[{{ $i }}][cheque_no]"></td>
                        <td class="hide_content"><input type="text" name="SocietyOtherIncome[{{ $i }}][general_bank_name]"></td>
                        <td><input type="text" name="SocietyOtherIncome[{{ $i }}][title]"></td>
                        <td><textarea name="SocietyOtherIncome[{{ $i }}][description]" rows="1"></textarea></td>
                    </tr>
                    @endfor
                @endif
                </tbody>
            </table>
        </div>
    </form>
</div>

@php
    $bankOptionsJson = $societyBankBalanceHeadsLists->map(fn($t, $id) => ['id' => $id, 'title' => $t])->values();
    $cashOptionsJson = $societyCashBalanceHeadsLists->map(fn($t, $id) => ['id' => $id, 'title' => $t])->values();
@endphp
@endsection

@section('scripts')
<script>
var generalReceiptBankOptions = {!! $bankOptionsJson->toJson() !!};
var generalReceiptCashOptions = {!! $cashOptionsJson->toJson() !!};

function calcRowNetAmount(i) {
    var amt = parseFloat($('input[name="SocietyOtherIncome[' + i + '][amount_paid]"]').val()) || 0;
    var tds = parseFloat($('#tds_amount' + i).val()) || 0;
    $('#net_amount' + i).val(amt - tds);
}

function fillGeneralReceiptByDropdown(options) {
    var sel = $('#society_payment_by_ledger');
    var current = sel.val();
    sel.empty().append('<option value="">Select Vendor</option>');
    $.each(options, function (idx, opt) {
        sel.append($('<option>', { value: opt.id, text: opt.title }));
    });
    if (current) sel.val(current);
}

function hideChequeDetailsFields(paymentMode) {
    if (paymentMode == 'Bank') {
        $('.hide_content').removeClass('hidden-by-type');
        fillGeneralReceiptByDropdown(generalReceiptBankOptions);
    } else {
        $('.hide_content').addClass('hidden-by-type');
        $('.hide_content input').val('');
        fillGeneralReceiptByDropdown(generalReceiptCashOptions);
    }
}

$(document).ready(function () {
    // Matches the CakePHP page: "By" defaults to the Bank list and cheque fields
    // stay visible until the user actually switches Type to Cash.
    if ($('#payment_type').val() === 'Cash') {
        hideChequeDetailsFields('Cash');
    } else {
        fillGeneralReceiptByDropdown(generalReceiptBankOptions);
    }
});

function saveSocietyGeneralReciepts() {
    if ($('#payment_type').val() == '') {
        alert('Please select the Type (Bank/Cash).');
        return false;
    }
    if ($('#society_payment_by_ledger').val() == '') {
        alert('Please select By.');
        return false;
    }
    $('#society_general_receipt').submit();
}
</script>
@endsection
