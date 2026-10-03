@extends('layouts.app')
@section('title', 'TDS Deductee')
@section('content')
@include('society.tds._styles')
@php $v = fn ($f) => old($f, $d[$f] ?? ''); @endphp
<div class="tds-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">TDS Details - {{ $vendor->contact_person_name }} (PAN: {{ $vendor->pan_no }})</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" action="{{ route('society.tdsAddDeductee', $vendor->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Deductee Type</label>
                            <select class="form-control" name="deductee_type">
                                @foreach(['Individual_HUF', 'Company', 'Firm', 'Others'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('deductee_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Resident Status</label>
                            <select class="form-control" name="resident_status">
                                @foreach(['Resident', 'Non-Resident'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('resident_status') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Lower/Nil Deduction Cert No</label>
                            <input type="text" class="form-control" name="lower_deduction_cert_no" value="{{ $v('lower_deduction_cert_no') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Lower Deduction Rate %</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" name="lower_deduction_rate" value="{{ $v('lower_deduction_rate') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Certificate Valid From</label>
                            <input type="date" class="form-control" name="lower_deduction_valid_from" value="{{ $v('lower_deduction_valid_from') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Certificate Valid Upto</label>
                            <input type="date" class="form-control" name="lower_deduction_valid_upto" value="{{ $v('lower_deduction_valid_upto') }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.tdsDeductees') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
