@extends('layouts.app')
@section('title', 'Generate TDS Challan')
@section('content')
@include('society.tds._styles')
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Generate TDS Challan</h6></div>
        <div class="panel-body">
            @if(empty($society->tan_no))
                <div class="alert alert-warning">TAN is not set for this Society. Please update it under Society &gt; Society Identity before generating a challan.</div>
            @endif
            <p>TAN: <b>{{ $society->tan_no ?? '' }}</b></p>
            @if($pendingTxns->isEmpty())
                <div class="alert alert-info">No pending TDS transactions available to group into a challan for the current financial year.</div>
            @else
            <form method="post" action="{{ route('society.tdsGenerateChallan') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Month<span class="required">*</span></label>
                            <select name="month" class="form-control" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Major Head</label>
                            <input type="text" class="form-control" name="major_head" placeholder="e.g. 0021">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Minor Head</label>
                            <input type="text" class="form-control" name="minor_head" placeholder="e.g. 200">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.txnChk').forEach(function (c) { c.checked = this.checked; }, this);"></th><th>Deduction Date</th><th>Deductee</th><th>PAN</th><th>Section</th><th class="text-right">TDS Amount</th></tr></thead>
                        <tbody>
                        @foreach($pendingTxns as $t)
                            <tr>
                                <td><input type="checkbox" class="txnChk" name="txn_ids[]" value="{{ $t->id }}"></td>
                                <td>{{ $t->deduction_date }}</td>
                                <td>{{ $t->contact_person_name }}</td>
                                <td>{{ $t->pan_no }}</td>
                                <td>{{ $t->section_code }}</td>
                                <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" {{ empty($society->tan_no) ? 'disabled' : '' }}>Prepare Challan Draft</button>
                <a href="{{ route('society.tdsChallans') }}" class="btn btn-default">Cancel</a>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
