@extends('layouts.app')

@section('title', 'Reseller Society Report - EasyLogics')

@php
    if (!function_exists('safeReportDate')) {
        function safeReportDate($value) {
            if (empty($value) || str_starts_with($value, '0000-00-00')) {
                return '-';
            }
            return \Carbon\Carbon::parse($value)->format('d-M-Y');
        }
    }
@endphp

@section('content')
<div class="page-header">
    <h2>Reseller Society Report</h2>
</div>

<p class="text-muted">Each active reseller with their societies - members, bills, and status.</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Reseller</th>
                <th>Society</th>
                <th>Created</th>
                <th class="text-right">Members</th>
                <th class="text-right">Total Bills</th>
                <th class="text-right">This Month Bills</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @php $lastResellerId = null; @endphp
            @forelse($rows as $r)
                @if($r->reseller_user_id !== $lastResellerId)
                    @php $lastResellerId = $r->reseller_user_id; @endphp
                <tr style="background:#f5f7fa;">
                    <td colspan="7">
                        <strong>{{ trim($r->reseller_name) !== '' ? $r->reseller_name : $r->reseller_username }}</strong>
                        ({{ $r->reseller_username }})
                        @if($r->reseller_area) &middot; {{ $r->reseller_area }} @endif
                        &middot; Reseller since {{ safeReportDate($r->reseller_cdate) }}
                        &middot; {!! ((int)$r->reseller_status === 1) ? '<span style="color:#5cb85c;">Active</span>' : '<span style="color:#d9534f;">Inactive</span>' !!}
                    </td>
                </tr>
                @endif
                <tr>
                    <td></td>
                    <td>
                        {{ $r->society_name }}
                        <div style="font-size:11px;color:#999;">ID: {{ $r->society_id }}</div>
                    </td>
                    <td>{{ safeReportDate($r->society_cdate) }}</td>
                    <td class="text-right">{{ number_format((int)$r->total_members) }}</td>
                    <td class="text-right">{{ number_format((int)$r->total_bills) }}</td>
                    <td class="text-right">{{ number_format((int)$r->bills_this_month) }}</td>
                    <td>{!! ((int)$r->society_status === 1) ? '<span style="color:#5cb85c;">Active</span>' : '<span style="color:#d9534f;">Inactive</span>' !!}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No reseller-assigned societies found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
