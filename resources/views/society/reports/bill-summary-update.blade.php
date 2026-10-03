@extends('layouts.app')
@section('title', $title ?? 'Bill Summary Update')
@section('content')
@include('society.reports._styles')
<style>
.mismatch-green-checkbox { accent-color: #2e7d32; width: 16px; height: 16px; cursor: pointer; }
</style>
<div class="page-header">
    <h2>Bill Summary Update</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card ar-report">
    @include('society.reports._bill_summary_update_body')
</div>
@endsection

@section('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } });
var webrootUrl = '/';
</script>
@include('society.reports._bill_summary_update_js')
@endsection
