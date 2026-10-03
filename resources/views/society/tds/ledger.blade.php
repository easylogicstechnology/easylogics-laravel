@extends('layouts.app')
@section('title', 'TDS Ledger')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">TDS Ledger</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-3">
                    <select name="financial_year_id" class="form-control" onchange="this.form.submit();">
                        @foreach($financialYearsList as $id => $y)
                            <option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <div class="clearfix"></div>

            <h6>Deductee-wise TDS Ledger</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Deductee</th><th>PAN</th><th class="text-right">Opening</th><th class="text-right">TDS Deducted</th><th class="text-right">TDS Paid</th><th class="text-right">Adjustment</th><th class="text-right">Closing</th></tr></thead>
                    <tbody>
                    @forelse($deducteeWise as $row)
                        @php
                            $deducted = isset($row->deducted) ? $row->deducted : 0;
                            $paid = isset($row->paid) ? $row->paid : 0;
                            $closing = 0 + $deducted - $paid - 0;
                        @endphp
                        <tr>
                            <td>{{ $row->contact_person_name }}</td>
                            <td>{{ $row->pan_no }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($deducted, 2) }}</td>
                            <td class="text-right">{{ number_format($paid, 2) }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($closing, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <h6 style="margin-top:20px;">Section-wise TDS Ledger</h6>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Section</th><th>Nature of Payment</th><th class="text-right">Opening</th><th class="text-right">TDS Deducted</th><th class="text-right">TDS Paid</th><th class="text-right">Adjustment</th><th class="text-right">Closing</th></tr></thead>
                    <tbody>
                    @forelse($sectionWise as $row)
                        @php
                            $deducted = isset($row->deducted) ? $row->deducted : 0;
                            $paid = isset($row->paid) ? $row->paid : 0;
                            $closing = 0 + $deducted - $paid - 0;
                        @endphp
                        <tr>
                            <td>{{ $row->section_code }}</td>
                            <td>{{ $row->nature_of_payment }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($deducted, 2) }}</td>
                            <td class="text-right">{{ number_format($paid, 2) }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($closing, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
