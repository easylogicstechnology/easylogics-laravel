@extends('layouts.app')
@section('title', 'Bill With Receipt Tabular')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Bill</h2>
</div>

<div class="card">
    {{-- CakePHP reports/bill_with_receipt_tabular: the form of the member bill print (society_bills/print_member_bills) --}}
    <form method="get" action="{{ route('society.printMemberBills') }}" class="ar-form" autocomplete="off">
        <div class="ar-row">
            <div class="ar-field">
                <label>Bill For</label>
                <select name="month">
                    <option value="">Select Month</option>
                    @foreach($months as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field"><label>Unit/Flat/Shop Number</label><input type="text" name="flat_no" style="width:140px"></div>
            <div class="ar-field"><label>Bill Number. From</label><input type="text" name="bill_from" style="width:110px"></div>
            <div class="ar-field"><label>Bill Number. To</label><input type="text" name="bill_to" style="width:110px"></div>
            <div class="ar-field"><label>Receipt From</label><input type="date" name="receipt_from"></div>
            <div class="ar-field"><label>Receipt To</label><input type="date" name="receipt_to"></div>
        </div>
        <div class="ar-row">
            <div class="ar-field">
                <label>Show Unit</label>
                <select name="unit_type">
                    <option value="Unit No">Unit Number</option>
                    <option value="Flat-Shop">Flat-Shop</option>
                    <option value="Flat No">Flat Number</option>
                    <option value="Shop No">Shop Number</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Remove details</label>
                <select name="remove_details[]" multiple size="4">
                    <option value="unit-area">Unit Area</option>
                    <option value="unit-type">Unit type</option>
                    <option value="wing">Wing</option>
                    <option value="floor-number">Floor Number</option>
                </select>
            </div>
            <div class="ar-field">
                <label>Words Language</label>
                <select name="words_type"><option value="English">English</option><option value="Marathi">Marathi</option></select>
            </div>
            <div class="ar-field">
                <label>Bill Type</label>
                <select name="bill_type"><option value="reg">Regular</option><option value="sup">Supplementary</option></select>
            </div>
            <div class="ar-field">
                <label>Member Record</label>
                <select name="member_record"><option value="Current">Current Member</option><option value="Old">Old Member</option></select>
            </div>
            <div class="ar-field">
                <label>Bill Format Type</label>
                <select name="bill_format_type"><option value="-1">Select Bill Format Type</option><option value="1">Without Interest</option></select>
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Print Bill</button>
                <a href="{{ route('society.dashboard') }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
