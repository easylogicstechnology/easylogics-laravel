@extends('layouts.app')
@section('title', 'GST Year-End Report')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Year-End Report</h6></div>
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
                    <thead>
                    <tr><th>Month</th><th class="text-right">Taxable Turnover</th><th class="text-right">Output CGST</th><th class="text-right">Output SGST</th><th class="text-right">Output IGST</th><th class="text-right">Input CGST</th><th class="text-right">Input SGST</th><th class="text-right">Input IGST</th><th class="text-right">Net Liability</th><th class="text-right">GST Paid</th><th class="text-right">Closing Liability</th></tr>
                    </thead>
                    <tbody>
                    @foreach($monthlyData as $row)
                        <tr>
                            <td>{{ $row['month'] }}</td>
                            <td class="text-right">{{ number_format($row['turnover'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['output_cgst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['output_sgst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['output_igst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['input_cgst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['input_sgst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['input_igst'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['net_liability'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['paid'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['closing'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="text-bold">
                        <th>Annual Total</th>
                        <th class="text-right">{{ number_format($grandTotals['turnover'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['output_cgst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['output_sgst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['output_igst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['input_cgst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['input_sgst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['input_igst'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['net_liability'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['paid'], 2) }}</th>
                        <th class="text-right">{{ number_format($grandTotals['net_liability'] - $grandTotals['paid'], 2) }}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
