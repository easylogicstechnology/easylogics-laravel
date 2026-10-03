@extends('layouts.app')
@section('title', 'GST Master')
@section('content')
@include('society.gst._styles')
@php $v = fn ($f) => $d[$f] ?? ''; @endphp
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">GST Master</h6></div>
        <div class="panel-body">
            <form method="post" autocomplete="off" action="{{ route('society.gstMasterSetup') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">GSTIN</label>
                            <input type="text" class="form-control" name="gstin" value="{{ $v('gstin') }}" placeholder="22AAAAA0000A1Z5">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Legal Name</label>
                            <input type="text" class="form-control" name="legal_name" value="{{ $v('legal_name') }}">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Trade Name</label>
                            <input type="text" class="form-control" name="trade_name" value="{{ $v('trade_name') }}">
                        </div>
                    </div>
                    <div class="col-md-12 padding-1">
                        <div class="form-group">
                            <label class="control-label">Registered Address</label>
                            <textarea class="form-control" name="registered_address">{{ $v('registered_address') }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">State</label>
                            <input type="text" class="form-control" name="state" value="{{ $v('state') }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">State Code</label>
                            <input type="text" class="form-control" name="state_code" value="{{ $v('state_code') }}" placeholder="e.g. 27">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Registration Type</label>
                            <input type="text" class="form-control" name="registration_type" value="{{ $v('registration_type') }}" placeholder="e.g. Regular">
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Registration Date</label>
                            <input type="date" class="form-control" name="registration_date" value="{{ $v('registration_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">GST Return Frequency</label>
                            <select class="form-control" name="gst_return_frequency">
                                @foreach(['Monthly', 'Quarterly'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('gst_return_frequency') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 padding-1">
                        <div class="form-group">
                            <label class="control-label">Default Tax Type</label>
                            <select class="form-control" name="default_tax_type">
                                @foreach(['Taxable', 'Exempt', 'Nil Rated', 'Non-GST'] as $opt)
                                    <option value="{{ $opt }}" {{ $v('default_tax_type') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 padding-1">
                        <div class="form-group">
                            <label class="control-label">Default Place of Supply</label>
                            <input type="text" class="form-control" name="default_place_of_supply" value="{{ $v('default_place_of_supply') }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>
@endsection
