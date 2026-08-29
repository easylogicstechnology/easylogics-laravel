@extends('layouts.app')
@section('title', 'Society Parameters')
@section('content')
<div class="page-header">
    <h2>Society Parameters</h2>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.parameters') }}">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label>Billing Frequency</label>
                <select name="billing_frequency_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($billingFrequencies as $bf)
                        <option value="{{ $bf->id }}" {{ ($params->billing_frequency_id ?? '') == $bf->id ? 'selected' : '' }}>{{ $bf->frequency_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Interest Type</label>
                <select name="interest_type_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($interestTypes as $it)
                        <option value="{{ $it->id }}" {{ ($params->interest_type_id ?? '') == $it->id ? 'selected' : '' }}>{{ $it->interest_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Interest Rate (%)</label>
                <input type="number" step="0.01" name="interest_rate" class="form-control" value="{{ old('interest_rate', $params->interest_rate ?? '') }}">
            </div>
            <div class="form-group">
                <label>Interest Method</label>
                <select name="method_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($interestMethods as $im)
                        <option value="{{ $im->id }}" {{ ($params->method_id ?? '') == $im->id ? 'selected' : '' }}>{{ $im->method_title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Tariff Type</label>
                <select name="tariff_id" class="form-control">
                    <option value="">-- Select --</option>
                    @foreach($tariffTypes as $tt)
                        <option value="{{ $tt->id }}" {{ ($params->tariff_id ?? '') == $tt->id ? 'selected' : '' }}>{{ $tt->tariff_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>CGST Tax %</label>
                <input type="number" step="0.01" name="cgst_tax_per" class="form-control" value="{{ old('cgst_tax_per', $params->cgst_tax_per ?? '') }}">
            </div>
            <div class="form-group">
                <label>IGST Tax %</label>
                <input type="number" step="0.01" name="igst_tax_per" class="form-control" value="{{ old('igst_tax_per', $params->igst_tax_per ?? '') }}">
            </div>
            <div class="form-group">
                <label>SGST Tax %</label>
                <input type="number" step="0.01" name="sgst_tax_per" class="form-control" value="{{ old('sgst_tax_per', $params->sgst_tax_per ?? '') }}">
            </div>
            <div class="form-group">
                <label>Is Tariff Monthly</label>
                <select name="is_tariff_mothly" class="form-control">
                    <option value="0" {{ ($params->is_tariff_mothly ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->is_tariff_mothly ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Show All Tariff Name</label>
                <select name="show_all_tariff_name" class="form-control">
                    <option value="0" {{ ($params->show_all_tariff_name ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->show_all_tariff_name ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Show Bills In Receipt</label>
                <select name="show_bills_in_receipt" class="form-control">
                    <option value="0" {{ ($params->show_bills_in_receipt ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->show_bills_in_receipt ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>GST on Interest</label>
                <select name="gst_interest" class="form-control">
                    <option value="0" {{ ($params->gst_interest ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->gst_interest ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>GST on Interest Arrears</label>
                <select name="gst_interest_arreas" class="form-control">
                    <option value="0" {{ ($params->gst_interest_arreas ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->gst_interest_arreas ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>Settlement</label>
                <select name="settlement" class="form-control">
                    <option value="0" {{ ($params->settlement ?? 0) == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ ($params->settlement ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label>GST Limit</label>
                <input type="number" step="0.01" name="gst_limit" class="form-control" value="{{ old('gst_limit', $params->gst_limit ?? '') }}">
            </div>
            <div class="form-group">
                <label>Special Field</label>
                <input type="text" name="special_field" class="form-control" value="{{ old('special_field', $params->special_field ?? '') }}">
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Bill Note</label>
                <textarea name="bill_note" class="form-control" rows="3">{{ old('bill_note', $params->bill_note ?? '') }}</textarea>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Parameters</button>
        </div>
    </form>
</div>
@endsection
