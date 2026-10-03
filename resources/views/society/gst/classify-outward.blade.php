@extends('layouts.app')
@section('title', 'Classify Bill')
@section('content')
@include('society.gst._styles')
<div class="gst-page">
    <div class="panel">
        <div class="panel-heading"><h6 class="panel-title">Classify Bill #{{ $bill->bill_no }}</h6></div>
        <div class="panel-body">
            <p>Taxable Value (approx): <b>Rs. {{ number_format($bill->tax_total, 2) }}</b> total GST on bill dated {{ $bill->bill_generated_date }}</p>
            <form method="post" autocomplete="off" action="{{ route('society.gstClassifyOutward', $bill->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">HSN/SAC</label>
                            <select class="form-control" name="hsn_sac_id">
                                <option value="">Select</option>
                                @foreach($hsnList as $id => $code)
                                    <option value="{{ $id }}" {{ (isset($d['hsn_sac_id']) && $d['hsn_sac_id'] == $id) ? 'selected' : '' }}>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 padding-1">
                        <div class="form-group">
                            <label class="control-label">Place of Supply</label>
                            <input type="text" class="form-control" name="place_of_supply" value="{{ $d['place_of_supply'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Supply Type</label>
                            <select class="form-control" name="supply_type">
                                <option value="B2C" {{ (isset($d['supply_type']) && $d['supply_type'] == 'B2C') ? 'selected' : '' }}>B2C</option>
                                <option value="B2B" {{ (isset($d['supply_type']) && $d['supply_type'] == 'B2B') ? 'selected' : '' }}>B2B</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 padding-1">
                        <div class="form-group">
                            <label class="control-label">Reverse Charge</label><br>
                            <input type="checkbox" name="reverse_charge" value="1" {{ !empty($d['reverse_charge']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-md-12 padding-1">
                        <div class="form-group">
                            <label class="control-label">Remarks</label>
                            <input type="text" class="form-control" name="remarks" value="{{ $d['remarks'] ?? '' }}">
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('society.gstOutwardRegister') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
