@extends('layouts.app')
@section('title', $title ?? "Members' Closing Balance")
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>Members' Closing Balance</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card">
    <div class="ar-actions">
        @include('society.reports._actions', ['printId' => 'print_member_list', 'file' => 'MembersClosingBalance', 'sheet' => 'Closing Balance'])
    </div>
</div>

<div class="card ar-report">
    @include('society.reports._opening_balance_body')
</div>
@endsection

@section('scripts')
@include('society.reports._scripts')
@endsection
