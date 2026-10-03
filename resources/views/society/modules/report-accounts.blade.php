@extends('layouts.app')
@section('title', $title ?? 'Report - Accounts')
@section('content')
<style>
    .report-buttons { display: flex; flex-wrap: wrap; gap: 10px; }
    .report-buttons .btn-report { background: #e6e6e6; color: #333; border: 1px solid #d0d0d0; padding: 8px 18px; }
    .report-buttons .btn-report:hover { background: #d4d4d4; color: #111; }
</style>

<div class="page-header">
    <h2>{{ $title ?? 'Report - Accounts' }}</h2>
</div>

<div class="card">
    <div class="report-buttons">
        @foreach($reports as $report)
            <a href="{{ $report['url'] }}" target="_blank" class="btn btn-report" title="{{ $report['label'] }}">{{ $report['label'] }}</a>
        @endforeach
    </div>
</div>
@endsection
