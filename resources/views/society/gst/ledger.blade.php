@extends('layouts.app')
@section('title', 'GST Ledger')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Ledger</h6></div>
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
                    <thead><tr><th>Ledger</th><th class="text-right">Opening</th><th class="text-right">Accrued</th><th class="text-right">Utilized/Claimed</th><th class="text-right">Closing</th><th>Note</th></tr></thead>
                    <tbody>
                    @foreach($ledgerRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-right">{{ number_format($row['opening'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['accrued'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['utilized'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['closing'], 2) }}</td>
                            <td class="text-muted"><small>{{ $row['note'] }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted">"Utilized" on the Output ledgers reflects GST actually marked Paid via GST Payment/Challan for the financial year.</p>
        </div>
    </div>
</div>
@endsection
