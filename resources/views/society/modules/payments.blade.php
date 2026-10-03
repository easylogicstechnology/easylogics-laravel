@extends('layouts.app')
@section('title', 'Society Payments')
@section('content')
<div class="page-header" style="margin-bottom:8px;">
    <h2>Society Payments</h2>
</div>
<div style="display:flex; justify-content:flex-end; gap:6px; margin-bottom:16px;">
    <a href="{{ route('society.downloadSampleSocietyPaymentTemplate') }}" class="btn" style="padding:3px 9px; font-size:11px; background:#17a2b8; color:#fff;" title="Download Excel sample template with your ledger heads">
        &#8681; Download Sample
    </a>
    <a href="{{ route('society.bulkPastePayments') }}" class="btn" style="padding:3px 9px; font-size:11px; background:#27ae60; color:#fff;" title="Excel se Copy-Paste bulk payment entry">
        &#128203; Bulk Paste
    </a>
    <a href="{{ route('society.addPayment') }}" class="btn" style="padding:3px 9px; font-size:11px; background:#3498db; color:#fff;">+ Add payment</a>
</div>

<div class="card" style="margin-bottom:16px;">
    <form method="POST" action="{{ route('society.payments') }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
        @csrf
        <div class="form-group" style="margin:0;">
            <label style="font-size:12px;">From Date</label>
            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" style="width:160px;">
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size:12px;">To Date</label>
            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" style="width:160px;">
        </div>
        <div>
            <button type="submit" class="btn btn-success btn-sm">Filter</button>
            <a href="{{ route('society.payments') }}" class="btn btn-sm" style="background:#999; color:#fff;">Reset</a>
        </div>
    </form>
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
                    <th style="color:#fff;">Paid To</th>
                    <th style="color:#fff;">Category</th>
                    <th style="color:#fff;">Particulars</th>
                    <th style="color:#fff;">Bill Voucher No</th>
                    <th style="color:#fff;">Payment Date</th>
                    <th style="color:#fff;">Cheque Date</th>
                    <th style="color:#fff;">Clear Date</th>
                    <th style="color:#fff; text-align:right;">Amount</th>
                    <th style="color:#fff;">Cheque No.</th>
                    <th style="color:#fff; text-align:right;">Tax Amount</th>
                    <th style="color:#fff; text-align:right;">Total Amount</th>
                    <th style="color:#fff;">Type</th>
                    <th style="color:#fff; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $p)
                <tr id="row_{{ $p->id }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->ledgerHead->title ?? '' }}</td>
                    <td>{{ $accountCategories[$p->ledgerHead->account_category_id ?? ''] ?? '' }}</td>
                    <td>{{ $p->particulars }}</td>
                    <td>{{ $p->bill_voucher_number }}</td>
                    <td>
                        <input type="date" class="form-control input-sm inline-edit" data-id="{{ $p->id }}" data-field="payment_date" value="{{ ($p->payment_date && $p->payment_date != '0000-00-00') ? $p->payment_date : '' }}" style="width:130px;">
                    </td>
                    <td>
                        <input type="date" class="form-control input-sm inline-edit" data-id="{{ $p->id }}" data-field="cheque_date" value="{{ ($p->cheque_date && $p->cheque_date != '0000-00-00') ? $p->cheque_date : '' }}" style="width:130px;">
                    </td>
                    <td>
                        <input type="date" class="form-control input-sm inline-edit" data-id="{{ $p->id }}" data-field="debited_date" value="{{ ($p->debited_date && $p->debited_date != '0000-00-00') ? $p->debited_date : '' }}" style="width:130px;">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control input-sm inline-edit text-right" data-id="{{ $p->id }}" data-field="amount" value="{{ $p->amount ?? '' }}" style="width:100px;">
                    </td>
                    <td>
                        <input type="text" class="form-control input-sm inline-edit" data-id="{{ $p->id }}" data-field="cheque_reference_number" value="{{ $p->cheque_reference_number }}" style="width:90px;">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control input-sm inline-edit text-right" data-id="{{ $p->id }}" data-field="tax_amount" id="tax_amt_{{ $p->id }}" value="{{ $p->tax_amount ?? '' }}" style="width:80px;">
                    </td>
                    <td>
                        <input type="number" step="0.01" class="form-control input-sm text-right" id="total_amt_{{ $p->id }}" value="{{ $p->total_amount ?? '' }}" style="width:100px;" readonly>
                    </td>
                    <td>{{ $p->payment_type }}</td>
                    <td style="text-align:center; white-space:nowrap;">
                        <button type="button" class="btn btn-success btn-xs" onclick="saveRow('{{ $p->id }}');" title="Save" style="padding:3px 8px; margin-right:4px;">&#10003;</button>
                        <a href="{{ route('society.addPayment', $p->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deletePayment', $p->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" style="text-align:center; color:#999;">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
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
            columnDefs: [
                { orderable: false, targets: [5, 6, 7, 8, 9, 10, 11, 13] }
            ]
        });
    }

    $(document).on('input', '.inline-edit[data-field="amount"], .inline-edit[data-field="tax_amount"]', function(){
        var id = $(this).data('id');
        var row = $('#row_' + id);
        var amt = parseFloat(row.find('.inline-edit[data-field="amount"]').val()) || 0;
        var taxAmt = parseFloat($('#tax_amt_' + id).val()) || 0;
        var totalAmt = (amt - taxAmt).toFixed(2);
        $('#total_amt_' + id).val(totalAmt);
    });

    $(document).on('change', '.inline-edit:not([data-field="amount"]):not([data-field="tax_amount"])', function(){
        var el = $(this);
        var id = el.data('id');
        var field = el.data('field');
        var value = el.val();
        updateField(el, id, field, value);
    });
});

function updateField(el, id, field, value){
    el.css('border-color','#f0ad4e');
    $.ajax({
        type: "POST",
        url: "{{ route('society.updatePaymentField') }}",
        data: {id: id, field: field, value: value, _token: '{{ csrf_token() }}'},
        dataType: 'json',
        success: function(resp){
            if(resp.error == 0){
                el.css('border-color','#5cb85c');
                setTimeout(function(){ el.css('border-color',''); }, 1500);
            } else {
                el.css('border-color','#d9534f');
                alert(resp.error_message);
            }
        },
        error: function(){
            el.css('border-color','#d9534f');
            alert('Error updating field.');
        }
    });
}

function saveRow(id){
    var row = $('#row_' + id);
    var fields = row.find('.inline-edit[data-id="' + id + '"]');
    var amt = parseFloat(row.find('.inline-edit[data-field="amount"]').val()) || 0;
    var taxAmt = parseFloat($('#tax_amt_' + id).val()) || 0;
    var totalAmt = (amt - taxAmt).toFixed(2);
    $('#total_amt_' + id).val(totalAmt);

    var saveCount = 0;
    var totalFields = fields.length + 1;
    var hasError = false;

    fields.each(function(){
        var el = $(this);
        var field = el.data('field');
        var value = el.val();
        el.css('border-color','#f0ad4e');
        $.ajax({
            type: "POST",
            url: "{{ route('society.updatePaymentField') }}",
            data: {id: id, field: field, value: value, _token: '{{ csrf_token() }}'},
            dataType: 'json',
            success: function(resp){
                saveCount++;
                if(resp.error == 0){
                    el.css('border-color','#5cb85c');
                    setTimeout(function(){ el.css('border-color',''); }, 1500);
                } else {
                    hasError = true;
                    el.css('border-color','#d9534f');
                }
                if(saveCount >= totalFields && !hasError){
                    alert('Saved successfully!');
                }
            },
            error: function(){ hasError = true; el.css('border-color','#d9534f'); saveCount++; }
        });
    });

    $.ajax({
        type: "POST",
        url: "{{ route('society.updatePaymentField') }}",
        data: {id: id, field: 'total_amount', value: totalAmt, _token: '{{ csrf_token() }}'},
        dataType: 'json',
        success: function(resp){
            saveCount++;
            if(saveCount >= totalFields && !hasError){
                alert('Saved successfully!');
            }
        },
        error: function(){ hasError = true; saveCount++; }
    });
}
</script>
@endsection
