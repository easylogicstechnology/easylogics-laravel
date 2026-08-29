@extends('layouts.app')
@section('title', 'Member Receipt')
@section('content')
<div class="page-header">
    <h2>Member Receipt</h2>
</div>

<div class="card">
    <form method="post" id="member_receipt_payments" name="memberPaymentForm" autocomplete="off">
        @csrf
        <div style="margin-bottom:10px;">
            <button type="button" onclick="saveSocietyMemberPaymentReciepts();" class="btn btn-success btn-sm">Save Member Payment</button>
        </div>
        <div id="society_member_payment_reciepts">
        </div>
    </form>
</div>

<script>
loadSocietyMemberPaymentReciepts();

function loadSocietyMemberPaymentReciepts() {
    var postStr = $('#member_receipt_payments').serialize();
    $.ajax({
        type: "POST",
        url: "{{ route('society.loadMemberPaymentReceipts') }}",
        data: postStr,
        dataType: 'html',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function (resp) {
            $('#society_member_payment_reciepts').empty();
            $('#society_member_payment_reciepts').html(resp);
            if ($.fn.DataTable) {
                $('#datable_4').DataTable();
            }
        }
    });
}

function saveSocietyMemberPaymentReciepts() {
    var table = null;
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#datable_4')) {
        table = $('#datable_4').DataTable();
    }
    var postStr;
    if (table) {
        postStr = table.$('input,select,textarea').serialize();
    } else {
        postStr = $('#member_receipt_payments').find('input,select,textarea').serialize();
    }
    postStr += '&_token={{ csrf_token() }}';
    $.ajax({
        type: "POST",
        url: "{{ route('society.saveMemberPaymentReceipts') }}",
        data: postStr,
        dataType: 'json',
        success: function (jsonData) {
            if (jsonData.error == 0) {
                alert(jsonData.error_message);
                window.location.href = "{{ route('society.memberPayments') }}";
            } else {
                alert(jsonData.error_message);
            }
        },
        error: function() {
            alert('Error saving payment data. Please try again.');
        }
    });
}

function setSocietyBankDataInBulkPay(index, value) {
    var selectedPayment = $('#payment_method_' + index + ' option:selected').text();
    var id = "society_bank_" + index;
}

function removeErrorMsg(currentIdText, index) {
    var amountPaid = $('#amount_paid_' + index).val();
    if (amountPaid != '') {
        $('#bank_selection_error_' + index).html('');
    }
}

function setRemarkText(selectedValue, index) {
    if (selectedValue == 'add') {
        $('#textremarks' + index).show();
        $('#textremarks' + index).val('');
        $('#selectremarks' + index).hide();
    } else if (selectedValue != '-1') {
        $('#textremarks' + index).show();
        $('#textremarks' + index).val(selectedValue);
        $('#selectremarks' + index).hide();
    }
}

function checkAndResetToList(index) {
    var remarkTextVal = $('#textremarks' + index).val();
    if (remarkTextVal == '') {
        $('#selectremarks' + index).show();
        $('#textremarks' + index).hide();
        $('#selectremarks' + index).val('-1');
    }
}
</script>
@endsection
