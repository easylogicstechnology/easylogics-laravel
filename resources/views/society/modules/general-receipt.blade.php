@extends('layouts.app')
@section('title', 'General Receipts Payments')
@section('content')
<div class="page-header">
    <h2>General Receipts Payments</h2>
    <div>
        <a href="{{ route('society.bulkPasteGeneralReceipt') }}" class="btn btn-default btn-sm" style="margin-right:6px;" title="Paste rows copied from Excel">
            &#128203; Bulk Paste
        </a>
        <a href="{{ route('society.addGeneralReceipt') }}" class="btn btn-primary btn-sm">+ Add General Receipts Payments</a>
    </div>
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
                    <th style="color:#fff;">Received By</th>
                    <th style="color:#fff;">Particulars</th>
                    <th style="color:#fff;">Remark</th>
                    <th style="color:#fff;">General Receipt No</th>
                    <th style="color:#fff;">Payment Date</th>
                    <th style="color:#fff;">Cheque Date</th>
                    <th style="color:#fff;">Cheque No</th>
                    <th style="color:#fff; text-align:right;">Amount</th>
                    <th style="color:#fff;">Type</th>
                    <th style="color:#fff; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr id="row_{{ $item->id }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->ledgerHead->title ?? '' }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->general_receipt_number }}</td>
                    <td>{{ ($item->payment_date && $item->payment_date != '0000-00-00') ? \Illuminate\Support\Carbon::parse($item->payment_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ ($item->cheque_date && $item->cheque_date != '0000-00-00') ? \Illuminate\Support\Carbon::parse($item->cheque_date)->format('d/m/Y') : '' }}</td>
                    <td>{{ $item->cheque_no }}</td>
                    <td style="text-align:right;">{{ $item->amount_paid }}</td>
                    <td>{{ $item->payment_mode }}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <a href="javascript:void(0);" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;" onclick="editGeneralReceipt('{{ $item->id }}');">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteGeneralReceipt', $item->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete # {{ $item->id }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center; color:#999;">No general receipts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for editing a general receipt -->
<div id="generalReceiptEditModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:900px;">
    <div class="modal-header-bar">Edit General Receipt <span id="general_receipt_edit_number"></span> <span class="modal-close" onclick="closeModal('generalReceiptEditModal')">&times;</span></div>
    <div class="modal-body-content">
        <div id="general_receipt_edit_error" style="display:none; color:#dc3545; margin-bottom:10px;"></div>
        <form id="general_receipt_edit_form" autocomplete="off">
            <input type="hidden" id="general_receipt_edit_id">
            <div class="grid-3">
                <div class="form-group">
                    <label>Type <span class="required">*</span></label>
                    <select class="form-control" id="general_receipt_edit_payment_mode" onchange="toggleGeneralReceiptChequeFields(this.value);">
                        <option value="">Select payment mode</option>
                        <option value="Bank">Bank</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div class="form-group general_receipt_bank_field">
                    <label>By</label>
                    <select class="form-control" id="general_receipt_edit_society_bank_id">
                        <option value="">Select Bank</option>
                        @foreach($societyBankBalanceHeadsLists ?? [] as $lhId => $lhTitle)
                        <option value="{{ $lhId }}">{{ $lhTitle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Received By <span class="required">*</span></label>
                    <select class="form-control" id="general_receipt_edit_ledger_head_id">
                        <option value="">Select Ledger</option>
                        @foreach($societyOtherIncomeHeadsLists ?? [] as $lhId => $lhTitle)
                        <option value="{{ $lhId }}">{{ $lhTitle }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid-3">
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" class="form-control" id="general_receipt_edit_amount_paid" onkeyup="calculateGeneralReceiptNetAmount();" onchange="calculateGeneralReceiptNetAmount();">
                </div>
                <div class="form-group">
                    <label>TDS Bank</label>
                    <select class="form-control" id="general_receipt_edit_tds_bank_id">
                        <option value="">Select Tds</option>
                        @foreach($societyOtherIncomeHeadsLists ?? [] as $lhId => $lhTitle)
                        <option value="{{ $lhId }}">{{ $lhTitle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>TDS Amount</label>
                    <input type="text" class="form-control" id="general_receipt_edit_tds_amount" onkeyup="calculateGeneralReceiptNetAmount();" onchange="calculateGeneralReceiptNetAmount();">
                </div>
            </div>
            <div class="grid-3">
                <div class="form-group">
                    <label>Net Amount</label>
                    <input type="text" class="form-control" id="general_receipt_edit_net_amount">
                </div>
                <div class="form-group">
                    <label>Payment Date <span class="required">*</span></label>
                    <input type="date" class="form-control" id="general_receipt_edit_payment_date">
                </div>
                <div class="form-group general_receipt_bank_field">
                    <label>Cheque Date</label>
                    <input type="date" class="form-control" id="general_receipt_edit_cheque_date">
                </div>
            </div>
            <div class="grid-3">
                <div class="form-group general_receipt_bank_field">
                    <label>Cheque No</label>
                    <input type="text" class="form-control" id="general_receipt_edit_cheque_no">
                </div>
                <div class="form-group general_receipt_bank_field">
                    <label>Bank Name</label>
                    <input type="text" class="form-control" id="general_receipt_edit_general_bank_name">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Particulars</label>
                    <input type="text" class="form-control" id="general_receipt_edit_title">
                </div>
                <div class="form-group">
                    <label>Remark</label>
                    <input type="text" class="form-control" id="general_receipt_edit_description">
                </div>
            </div>
        </form>
        <div style="margin-top:16px; display:flex; gap:8px;">
            <button type="button" class="btn btn-primary" onclick="updateGeneralReceipt();">Update General Receipt</button>
            <button type="button" class="btn" style="background:#6c757d; color:#fff;" onclick="closeModal('generalReceiptEditModal')">Cancel</button>
        </div>
    </div>
</div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function(){
    if ($.fn.DataTable) {
        $('#datable_1').DataTable({
            pageLength: 100,
            lengthMenu: [10, 25, 50, 100, 250],
            order: [],
            autoWidth: false,
            columnDefs: [ { orderable: false, targets: [10] } ]
        });
    }
});

function toggleGeneralReceiptChequeFields(paymentMode) {
    if (paymentMode == 'Bank') {
        $('.general_receipt_bank_field').show();
    } else {
        $('.general_receipt_bank_field').hide();
        $('#general_receipt_edit_society_bank_id').val('');
        $('#general_receipt_edit_cheque_date').val('');
        $('#general_receipt_edit_cheque_no').val('');
        $('#general_receipt_edit_general_bank_name').val('');
    }
}

function calculateGeneralReceiptNetAmount() {
    var amountPaid = parseFloat($('#general_receipt_edit_amount_paid').val());
    var tdsAmount = parseFloat($('#general_receipt_edit_tds_amount').val());
    amountPaid = isNaN(amountPaid) ? 0 : amountPaid;
    tdsAmount = isNaN(tdsAmount) ? 0 : tdsAmount;
    $('#general_receipt_edit_net_amount').val(amountPaid - tdsAmount);
}

function setGeneralReceiptDropdown(dropdownId, selectedValue) {
    $('#' + dropdownId).val(selectedValue);
    if ($('#' + dropdownId).val() === null) {
        $('#' + dropdownId).val('');
    }
}

function editGeneralReceipt(generalReceiptId) {
    $.ajax({
        type: 'POST',
        url: "{{ route('society.getGeneralReceiptDetails') }}",
        data: {general_receipt_id: generalReceiptId, _token: '{{ csrf_token() }}'},
        dataType: 'json',
        success: function (jsonData) {
            if (jsonData.error == 1) {
                alert(jsonData.error_message);
                return false;
            }
            var receiptData = jsonData.data;
            $('#general_receipt_edit_error').hide().html('');
            $('#general_receipt_edit_id').val(receiptData.id);
            $('#general_receipt_edit_number').text(receiptData.general_receipt_number ? '#' + receiptData.general_receipt_number : '');
            setGeneralReceiptDropdown('general_receipt_edit_payment_mode', receiptData.payment_mode);
            setGeneralReceiptDropdown('general_receipt_edit_society_bank_id', receiptData.society_bank_id);
            setGeneralReceiptDropdown('general_receipt_edit_ledger_head_id', receiptData.ledger_head_id);
            $('#general_receipt_edit_amount_paid').val(receiptData.amount_paid);
            setGeneralReceiptDropdown('general_receipt_edit_tds_bank_id', receiptData.tds_bank_id);
            $('#general_receipt_edit_tds_amount').val(receiptData.tds_amount);
            $('#general_receipt_edit_net_amount').val(receiptData.net_amount);
            $('#general_receipt_edit_payment_date').val(receiptData.payment_date);
            $('#general_receipt_edit_cheque_date').val(receiptData.cheque_date);
            $('#general_receipt_edit_cheque_no').val(receiptData.cheque_no);
            $('#general_receipt_edit_general_bank_name').val(receiptData.general_bank_name);
            $('#general_receipt_edit_title').val(receiptData.title);
            $('#general_receipt_edit_description').val(receiptData.description);
            toggleGeneralReceiptChequeFields(receiptData.payment_mode);
            openModal('generalReceiptEditModal');
        },
        error: function () {
            alert('General receipt details could not be loaded. Please, try again.');
        }
    });
}

function updateGeneralReceipt() {
    if ($('#general_receipt_edit_ledger_head_id').val() == '') {
        alert('Please select the head in Received By.');
        return false;
    }
    if ($('#general_receipt_edit_payment_date').val() == '') {
        alert('Please select the payment date.');
        return false;
    }
    $.ajax({
        type: 'POST',
        url: "{{ route('society.updateGeneralReceipt') }}",
        data: {
            _token: '{{ csrf_token() }}',
            'SocietyOtherIncome[id]': $('#general_receipt_edit_id').val(),
            'SocietyOtherIncome[payment_mode]': $('#general_receipt_edit_payment_mode').val(),
            'SocietyOtherIncome[society_bank_id]': $('#general_receipt_edit_society_bank_id').val(),
            'SocietyOtherIncome[ledger_head_id]': $('#general_receipt_edit_ledger_head_id').val(),
            'SocietyOtherIncome[amount_paid]': $('#general_receipt_edit_amount_paid').val(),
            'SocietyOtherIncome[tds_bank_id]': $('#general_receipt_edit_tds_bank_id').val(),
            'SocietyOtherIncome[tds_amount]': $('#general_receipt_edit_tds_amount').val(),
            'SocietyOtherIncome[net_amount]': $('#general_receipt_edit_net_amount').val(),
            'SocietyOtherIncome[payment_date]': $('#general_receipt_edit_payment_date').val(),
            'SocietyOtherIncome[cheque_date]': $('#general_receipt_edit_cheque_date').val(),
            'SocietyOtherIncome[cheque_no]': $('#general_receipt_edit_cheque_no').val(),
            'SocietyOtherIncome[general_bank_name]': $('#general_receipt_edit_general_bank_name').val(),
            'SocietyOtherIncome[title]': $('#general_receipt_edit_title').val(),
            'SocietyOtherIncome[description]': $('#general_receipt_edit_description').val()
        },
        dataType: 'json',
        success: function (jsonData) {
            if (jsonData.error == 1) {
                $('#general_receipt_edit_error').show().html(jsonData.error_message);
                return false;
            }
            closeModal('generalReceiptEditModal');
            window.location.reload();
        },
        error: function () {
            alert('The general receipt could not be updated. Please, try again.');
        }
    });
}
</script>
@endsection
