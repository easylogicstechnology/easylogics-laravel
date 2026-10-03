@extends('layouts.app')
@section('title', 'TDS Section')
@section('content')
@include('society.tds._styles')
@php $v = fn ($f, $default = '') => old($f, $d[$f] ?? $default); @endphp
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">{{ empty($d['id']) ? 'Add' : 'Edit' }} TDS Section</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" action="{{ route('society.tdsAddSection') }}">
                @csrf
                @if(!empty($d['id']))<input type="hidden" name="id" value="{{ $d['id'] }}">@endif
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Section Code<span class="required">*</span></label>
                            <input type="text" class="form-control" name="section_code" value="{{ $v('section_code') }}" placeholder="e.g. 194C" required>
                        </div>
                    </div>
                    <div class="col-md-5 padding-1">
                        <div class="form-group">
                            <label class="control-label">Nature of Payment<span class="required">*</span></label>
                            <input type="text" class="form-control" name="nature_of_payment" value="{{ $v('nature_of_payment') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Description</label>
                            <input type="text" class="form-control" name="description" value="{{ $v('description') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Deductee Type</label>
                            <select class="form-control" name="deductee_type">
                                @foreach(['All', 'Individual_HUF', 'Company', 'Firm'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('deductee_type', null) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Resident Type</label>
                            <select class="form-control" name="resident_type">
                                @foreach(['Resident', 'Non-Resident', 'Both'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('resident_type', null) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Rate %<span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="rate_percent" value="{{ $v('rate_percent') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Threshold Limit</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="threshold_limit" value="{{ $v('threshold_limit', '0') }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Threshold Basis</label>
                            <select class="form-control" name="threshold_basis">
                                @foreach(['Aggregate in FY', 'Single Transaction'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('threshold_basis', null) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Effective From<span class="required">*</span></label>
                            <input type="date" class="form-control" name="effective_from" value="{{ $v('effective_from') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Effective To</label>
                            <input type="date" class="form-control" name="effective_to" value="{{ $v('effective_to') }}">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">TDS Payable Ledger Head</label>
                            <select class="form-control" name="tds_payable_ledger_head_id">
                                <option value="">Select Ledger</option>
                                @foreach($tdsPayableLedgerHeadsList as $lhId => $title)
                                    <option value="{{ $lhId }}" {{ $v('tds_payable_ledger_head_id', null) == $lhId ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="1" {{ (!isset($d['status']) || $d['status'] == 1) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ (isset($d['status']) && $d['status'] == 0) ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.tdsSections') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
