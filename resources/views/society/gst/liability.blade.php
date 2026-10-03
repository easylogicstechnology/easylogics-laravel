@extends('layouts.app')
@section('title', 'GST Liability Statement')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Liability Statement</h6></div>
        <div class="panel-body">
            <form method="get" class="row" style="margin-bottom:15px;">
                <div class="col-md-3"><select name="financial_year_id" class="form-control" onchange="this.form.submit();">@foreach($financialYearsList as $id => $y)<option value="{{ $id }}" {{ $financialYearId == $id ? 'selected' : '' }}>{{ $y }}</option>@endforeach</select></div>
                <div class="col-md-3">
                    <select name="period_type" class="form-control" onchange="this.form.submit();">
                        <option value="Month" {{ $periodType == 'Month' ? 'selected' : '' }}>Month</option>
                        <option value="Quarter" {{ $periodType == 'Quarter' ? 'selected' : '' }}>Quarter</option>
                    </select>
                </div>
                <div class="col-md-3"><input type="text" name="period_value" class="form-control" value="{{ $periodValue }}" placeholder="e.g. September or Q2"></div>
                <div class="col-md-3"><button type="submit" class="btn btn-info">Show</button></div>
            </form>
            <div class="clearfix"></div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th></th><th class="text-right">CGST</th><th class="text-right">SGST</th><th class="text-right">IGST</th></tr></thead>
                    <tbody>
                    <tr><td>Output GST</td><td class="text-right">{{ number_format($outputCgst, 2) }}</td><td class="text-right">{{ number_format($outputSgst, 2) }}</td><td class="text-right">{{ number_format($outputIgst, 2) }}</td></tr>
                    <tr><td>Less: Eligible ITC</td><td class="text-right">{{ number_format($eligibleCgst, 2) }}</td><td class="text-right">{{ number_format($eligibleSgst, 2) }}</td><td class="text-right">{{ number_format($eligibleIgst, 2) }}</td></tr>
                    <tr class="text-bold"><td>Net Payable</td><td class="text-right">{{ number_format($netCgst, 2) }}</td><td class="text-right">{{ number_format($netSgst, 2) }}</td><td class="text-right">{{ number_format($netIgst, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <h6 style="margin-top:20px;">Period Adjustments</h6>
            <form method="post">
                @csrf
                <input type="hidden" name="period_type" value="{{ $periodType }}">
                <input type="hidden" name="period_value" value="{{ $periodValue }}">
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Interest</label>
                            <input type="number" step="0.01" class="form-control" name="interest" value="{{ $interest }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Late Fee</label>
                            <input type="number" step="0.01" class="form-control" name="late_fee" value="{{ $lateFee }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Other Adjustment</label>
                            <input type="number" step="0.01" class="form-control" name="other_adjustment" value="{{ $otherAdjustment }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Previous Period Adjustment</label>
                            <input type="number" step="0.01" class="form-control" name="previous_period_adjustment" value="{{ $previousPeriodAdjustment }}">
                        </div>
                    </div>
                    <div class="col-md-12 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $adjustment->remarks ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-default btn-sm">Save Adjustments</button>
            </form>

            <div class="alert alert-info" style="margin-top:20px;">
                <h4 style="margin-top:0;">Net GST Payable: Rs. {{ number_format($netGstPayable, 2) }}</h4>
                (Net CGST + Net SGST + Net IGST + Interest + Late Fee + Other Adjustment + Previous Period Adjustment)
            </div>
        </div>
    </div>
</div>
@endsection
