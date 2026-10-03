@extends('layouts.app')
@section('title', 'GST Reconciliation')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Reconciliation - Books vs Return</h6></div>
        <div class="panel-body">
            <p class="text-muted">Enter the totals shown on the GST portal / your filed return for a period. Books-side figures are computed automatically from this application's own records for comparison. This compares period totals, not individual invoices.</p>
            <form method="post" autocomplete="off">
                @csrf
                <div class="row">
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Period Type</label>
                            <select class="form-control" name="period_type">
                                <option value="Month" {{ $periodType == 'Month' ? 'selected' : '' }}>Month</option>
                                <option value="Quarter" {{ $periodType == 'Quarter' ? 'selected' : '' }}>Quarter</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Period Value</label>
                            <input type="text" class="form-control" name="period_value" value="{{ $periodValue }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Return Taxable Value</label>
                            <input type="number" step="0.01" class="form-control" name="return_taxable_value" value="{{ $e['return_taxable_value'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Return CGST</label>
                            <input type="number" step="0.01" class="form-control" name="return_cgst" value="{{ $e['return_cgst'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Return SGST</label>
                            <input type="number" step="0.01" class="form-control" name="return_sgst" value="{{ $e['return_sgst'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Return IGST</label>
                            <input type="number" step="0.01" class="form-control" name="return_igst" value="{{ $e['return_igst'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Return Invoice Count</label>
                            <input type="number" class="form-control" name="return_invoice_count" value="{{ $e['return_invoice_count'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $e['remarks'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Compare &amp; Save</button>
            </form>

            @if(!empty($e))
            <h6 style="margin-top:20px;">Comparison for {{ $periodType }}: {{ $periodValue }}</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th></th><th class="text-right">Taxable Value</th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th><th class="text-right">Invoice Count</th></tr></thead>
                    <tbody>
                    <tr><td>Return (Portal)</td><td class="text-right">{{ number_format($e['return_taxable_value'], 2) }}</td><td class="text-right">{{ number_format($e['return_cgst'], 2) }}</td><td class="text-right">{{ number_format($e['return_sgst'], 2) }}</td><td class="text-right">{{ number_format($e['return_igst'], 2) }}</td><td class="text-right">{{ $e['return_invoice_count'] }}</td></tr>
                    <tr><td>Books</td><td class="text-right">{{ number_format($e['books_taxable_value'], 2) }}</td><td class="text-right">{{ number_format($e['books_cgst'], 2) }}</td><td class="text-right">{{ number_format($e['books_sgst'], 2) }}</td><td class="text-right">{{ number_format($e['books_igst'], 2) }}</td><td class="text-right">{{ $e['books_invoice_count'] }}</td></tr>
                    <tr class="text-bold"><td>Difference</td><td class="text-right">{{ number_format($e['return_taxable_value'] - $e['books_taxable_value'], 2) }}</td><td class="text-right">{{ number_format($e['return_cgst'] - $e['books_cgst'], 2) }}</td><td class="text-right">{{ number_format($e['return_sgst'] - $e['books_sgst'], 2) }}</td><td class="text-right">{{ number_format($e['return_igst'] - $e['books_igst'], 2) }}</td><td class="text-right">{{ $e['return_invoice_count'] - $e['books_invoice_count'] }}</td></tr>
                    </tbody>
                </table>
            </div>
            <span class="label {{ $e['status'] == 'Matched' ? 'label-success' : 'label-danger' }}" style="font-size:14px;">Status: {{ $e['status'] }}</span>
            @endif

            <h6 style="margin-top:24px;">Reconciliation History</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Period</th><th class="text-right">Return CGST</th><th class="text-right">Books CGST</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($history as $h)
                        <tr>
                            <td>{{ $h->period_type }}: {{ $h->period_value }}</td>
                            <td class="text-right">{{ number_format($h->return_cgst, 2) }}</td>
                            <td class="text-right">{{ number_format($h->books_cgst, 2) }}</td>
                            <td><span class="label {{ $h->status == 'Matched' ? 'label-success' : 'label-danger' }}">{{ $h->status }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
