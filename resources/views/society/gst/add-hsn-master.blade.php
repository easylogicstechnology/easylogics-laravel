@extends('layouts.app')
@section('title', 'HSN/SAC')
@section('content')
@include('society.gst._styles')
@php $v = fn ($f, $default = '') => $d[$f] ?? $default; @endphp
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">{{ empty($d['id']) ? 'Add' : 'Edit' }} HSN/SAC</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" action="{{ route('society.gstAddHsnMaster') }}">
                @csrf
                @if(!empty($d['id']))<input type="hidden" name="id" value="{{ $d['id'] }}">@endif
                <div class="row">
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Code<span class="required">*</span></label>
                            <input type="text" class="form-control" name="code" value="{{ $v('code') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Type</label>
                            <select class="form-control" name="code_type">
                                @foreach(['SAC', 'HSN'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('code_type', null) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Description<span class="required">*</span></label>
                            <input type="text" class="form-control" name="description" value="{{ $v('description') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Taxability</label>
                            <select class="form-control" name="taxability_type">
                                @foreach(['Taxable', 'Exempt', 'Nil Rated', 'Non-GST'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('taxability_type', null) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">GST Rate %<span class="required">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="gst_rate" value="{{ $v('gst_rate') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">CGST %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="cgst_rate" value="{{ $v('cgst_rate') }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">SGST %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="sgst_rate" value="{{ $v('sgst_rate') }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">IGST %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="igst_rate" value="{{ $v('igst_rate') }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Cess %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="cess_rate" value="{{ $v('cess_rate', '0') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Reverse Charge Applicable</label><br>
                            <input type="checkbox" name="reverse_charge_applicable" value="1" {{ !empty($d['reverse_charge_applicable']) ? 'checked' : '' }}>
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
                    <div class="col-md-3 padding-1">
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
                <a href="{{ route('society.gstHsnMaster') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
