@extends('layouts.app')
@section('title', $title ?? 'Member Chart')
@section('content')
@include('society.reports._styles')
<?php $mc = $postData['MemberChart'] ?? []; $input = $mc; ?>
<div class="page-header">
    <h2>Member Chart</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <form method="post" action="{{ route('society.reports.memberChart') }}" class="ar-form" autocomplete="off">
        @csrf
        <div class="ar-row">
            <div class="ar-field"><label>From Date</label><input type="date" name="from_date" value="{{ $mc['from_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To Date</label><input type="date" name="to_date" value="{{ $mc['to_date'] ?? '' }}"></div>
            <div class="ar-field">
                <label>Bill Type</label>
                <select name="bill_type">
                    <option value="reg" {{ !isset($mc['bill_type']) || $mc['bill_type'] == 'reg' ? 'selected' : '' }}>Regular</option>
                    <option value="sup" {{ isset($mc['bill_type']) && $mc['bill_type'] == 'sup' ? 'selected' : '' }}>Supplementary</option>
                </select>
            </div>
        </div>
        @include('society.reports._member_filter')
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                @include('society.reports._actions', ['printId' => 'print_member_chart', 'file' => 'MemberChart', 'sheet' => 'Member Chart', 'orientation' => 'landscape'])
            </div>
        </div>
    </form>
</div>

<div class="card ar-report">
    @include('society.reports._member_chart_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
