@extends('layouts.app')
@section('title', 'GST Credit / Debit Notes')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left"><h6 class="panel-title">GST Credit / Debit Notes</h6></div>
            <div class="pull-right"><a href="{{ route('society.gstAddCreditDebitNote') }}" class="btn btn-primary">+ Add Note</a></div>
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <form method="get" style="margin-bottom:15px;">
                <select name="financial_year_id" class="form-control" style="width:220px;display:inline-block;" onchange="this.form.submit();">
                    @foreach($financialYearsList as $id => $y)
                        <option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Type</th><th>Note No</th><th>Date</th><th>Original Invoice</th><th>Party</th><th class="text-right">Taxable</th><th class="text-right">GST</th><th class="text-right">Total</th><th>Reason</th><th>Action</th></tr></thead>
                    <tbody>
                    @foreach($rows as $n)
                        <tr>
                            <td><span class="label {{ $n->note_type == 'Credit Note' ? 'label-success' : 'label-warning' }}">{{ $n->note_type }}</span></td>
                            <td>{{ $n->note_no }}</td>
                            <td>{{ $n->note_date }}</td>
                            <td>{{ $n->original_invoice_no }} ({{ $n->original_invoice_date }})</td>
                            <td>{{ $n->party_name }}</td>
                            <td class="text-right">{{ number_format($n->taxable_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($n->total_gst, 2) }}</td>
                            <td class="text-right">{{ number_format($n->total_amount, 2) }}</td>
                            <td>{{ $n->reason }}</td>
                            <td><a href="{{ route('society.gstAddCreditDebitNote', $n->id) }}" title="Edit">&#9998;</a></td>
                        </tr>
                    @endforeach
                    @if($rows->isEmpty())
                        <tr><td colspan="10" class="text-center">No credit/debit notes found.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
