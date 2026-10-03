@extends('layouts.app')

@section('title', 'Trial Balance Diff Report - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Trial Balance Diff Report (All Societies - Current Financial Year)</h2>
</div>

<div class="card">
    <p style="color:#666; margin-bottom:12px;">
        Har active society ki current financial year ka Transaction Debit aur Credit total dikhaya gaya hai.
        Jahan Dr &ne; Cr hai, wo row red mein highlight hai.
        Showing {{ count($rows) }} of {{ number_format($totalSocieties) }} societies (page {{ $page }} of {{ $totalPages }}).
    </p>

    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Society</th>
                    <th>Financial Year</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Diff (Dr - Cr)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $mismatchStyle = 'color:#d9534f;background-color:#ffdddd;';
                    $sr = ($page - 1) * $pageSize + 1;
                @endphp
                @foreach ($rows as $r)
                    @php $isMismatch = ($r['diff'] !== null && abs($r['diff']) >= 0.01); @endphp
                    <tr @if ($isMismatch) style="{{ $mismatchStyle }}" @endif>
                        <td>{{ $sr++ }}</td>
                        <td>
                            {{ $r['society_name'] }}
                            <div style="font-size:11px;color:#999;">ID: {{ $r['society_id'] }}</div>
                        </td>
                        <td>{{ $r['year_label'] ?: '-' }}</td>
                        @if ($r['error'])
                            <td colspan="3" style="color:#999;font-style:italic;">{{ $r['error'] }}</td>
                        @else
                            <td class="text-right">{{ number_format($r['debit'], 2) }}</td>
                            <td class="text-right">{{ number_format($r['credit'], 2) }}</td>
                            <td class="text-right" style="font-weight:{{ $isMismatch ? 'bold' : 'normal' }};">{{ number_format($r['diff'], 2) }}</td>
                        @endif
                    </tr>
                @endforeach
                @if (empty($rows))
                    <tr><td colspan="6" style="text-align:center;">Is page par koi society nahi mili.</td></tr>
                @endif
            </tbody>
        </table>
    </div>

    <div style="text-align:center; margin-top:15px;">
        @if ($page > 1)
            <a class="btn btn-sm" style="background:#e6e6e6;color:#333;" href="{{ route('admin.reports.trialBalanceDiff', ['page' => $page - 1]) }}">&laquo; Previous</a>
        @endif
        <span style="margin:0 10px;">Page {{ $page }} / {{ $totalPages }}</span>
        @if ($page < $totalPages)
            <a class="btn btn-sm" style="background:#e6e6e6;color:#333;" href="{{ route('admin.reports.trialBalanceDiff', ['page' => $page + 1]) }}">Next &raquo;</a>
        @endif
    </div>
</div>
@endsection
