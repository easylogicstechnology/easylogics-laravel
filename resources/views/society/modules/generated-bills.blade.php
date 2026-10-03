@extends('layouts.app')
@section('title', 'All Generated Bills')
@section('content')
<div class="page-header" style="margin-bottom:8px;">
    <h2>All Generated Bills</h2>
</div>

<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
        <form method="POST" action="{{ route('society.allGeneratedBills') }}" style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;">From</label>
                <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}" style="width:160px;">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;">To</label>
                <input type="date" name="to_date" class="form-control" value="{{ $toDate }}" style="width:160px;">
            </div>
            <div>
                <button type="submit" class="btn btn-success btn-sm">Submit</button>
            </div>
        </form>

        <form method="POST" action="{{ route('society.deleteAllBillsAndPayments') }}" id="deleteAllBillsForm" style="display:flex; gap:8px; align-items:end;">
            @csrf
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;">Delete</label>
                <select name="delete_type" class="form-control" style="width:150px;">
                    <option value="both">Both</option>
                    <option value="bill">Only Bill</option>
                    <option value="receipt">Receipt</option>
                </select>
            </div>
            <button type="submit" class="btn btn-danger btn-sm" onclick="var t=this.form.delete_type.options[this.form.delete_type.selectedIndex].text; return confirm('Delete ' + t + ' from {{ $fromDate ?: '(no date)' }} to {{ $toDate ?: '(no date)' }}?\n\nThis cannot be undone.');">
                &#128465; Delete
            </button>
        </form>
    </div>
</div>

<div class="card">
    <style>
        #datable_1 thead th { text-transform: uppercase; background: #009688 !important; color: #fff !important; font-size: 11px; white-space: nowrap; }
    </style>
    <div style="overflow-x:auto;">
        <table id="datable_1" class="display" style="width:100%;">
            <thead>
                <tr style="background:#009688; color:#fff;">
                    <th style="color:#fff;">#</th>
                    <th style="color:#fff;">Bill No</th>
                    <th style="color:#fff;">Bill Generate Date</th>
                    <th style="color:#fff;">Bill Type</th>
                    <th style="color:#fff;">Month</th>
                    <th style="color:#fff;">Member Name</th>
                    <th style="color:#fff; text-align:right;">Monthly Amount</th>
                    <th style="color:#fff; text-align:right;">Opening Principal Arrears</th>
                    <th style="color:#fff; text-align:right;">Opening Int. Arrears</th>
                    <th style="color:#fff; text-align:right;">Total Tax</th>
                    <th style="color:#fff; text-align:right;">Monthly Principal Amount</th>
                    <th style="color:#fff; text-align:right;">Int. On Due</th>
                    <th style="color:#fff; text-align:right;">Amt. Payable</th>
                    <th style="color:#fff; text-align:right;">Principal Paid</th>
                    <th style="color:#fff; text-align:right;">Int. Paid</th>
                    <th style="color:#fff; text-align:right;">Principal Bal.</th>
                    <th style="color:#fff; text-align:right;">Int Bal</th>
                    <th style="color:#fff; text-align:right;">Bal. Amt</th>
                    <th style="color:#fff;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->bill_no }}</td>
                    <td>{{ $row->bill_generated_date }}</td>
                    <td>{{ $row->bill_type }}</td>
                    <td>{{ $monthNames[(int) $row->month] ?? $row->month }}</td>
                    <td>{{ $row->member_name }}({{ $row->flat_no }})</td>
                    <td style="text-align:right;">{{ $row->monthly_amount }}</td>
                    <td style="text-align:right;">{{ $row->op_principal_arrears }}</td>
                    <td style="text-align:right;">{{ $row->op_interest_arrears }}</td>
                    <td style="text-align:right;">{{ $row->tax_total }}</td>
                    <td style="text-align:right;">{{ $row->monthly_principal_amount }}</td>
                    <td style="text-align:right;">{{ $row->interest_on_due_amount ?? '' }}</td>
                    <td style="text-align:right;">{{ $row->amount_payable }}</td>
                    <td style="text-align:right;">{{ $row->principal_paid }}</td>
                    <td style="text-align:right;">{{ $row->interest_paid ?? '' }}</td>
                    <td style="text-align:right;">{{ $row->principal_balance }}</td>
                    <td style="text-align:right;">{{ $row->interest_balance }}</td>
                    <td style="text-align:right;">{{ $row->balance_amount }}</td>
                    <td></td>
                </tr>
                @endforeach
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
            language: { emptyTable: 'No data available in table' },
            columnDefs: [
                { orderable: false, targets: [18] }
            ]
        });
    }
});
</script>
@endsection
