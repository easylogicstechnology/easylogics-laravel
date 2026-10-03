@extends('layouts.app')

@section('title', 'Reseller Dashboard - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Reseller Dashboard</h2>
</div>

@if(!empty($subscriptionExpiry))
    @php
        $__daysLeft = (int) ceil((strtotime($subscriptionExpiry) - strtotime(date('Y-m-d'))) / 86400);
        $__isExpired = $__daysLeft < 0;
        $__isSoon = !$__isExpired && $__daysLeft <= 15;
        $__bannerClass = $__isExpired ? 'alert-error' : ($__isSoon ? 'alert-warning' : 'alert-success');
        $__bannerBg = $__isExpired ? '#f8d7da' : ($__isSoon ? '#fff3cd' : '#d4edda');
        $__bannerColor = $__isExpired ? '#721c24' : ($__isSoon ? '#856404' : '#155724');
    @endphp
    <div style="background:{{ $__bannerBg }}; color:{{ $__bannerColor }}; padding:10px 16px; border-radius:4px; margin-bottom:16px; font-size:14px;">
        <strong>Subscription Expiry:</strong> {{ \Carbon\Carbon::parse($subscriptionExpiry)->format('d-M-Y') }}
        @if($__isExpired)
            &mdash; <strong>Expired {{ abs($__daysLeft) }} day(s) ago.</strong> Please renew to continue.
        @elseif($__isSoon)
            &mdash; Expiring in {{ $__daysLeft }} day(s). Please renew soon.
        @else
            &mdash; {{ $__daysLeft }} day(s) remaining.
        @endif
    </div>
@endif

@if(!empty($awaitingConfirmationCount))
    <div style="background:#d1ecf1; color:#0c5460; padding:10px 16px; border-radius:4px; margin-bottom:16px; font-size:14px;">
        <strong>{{ $awaitingConfirmationCount }} complaint(s)</strong> marked resolved by Admin - please confirm the fix or reopen them.
        <a href="{{ route('reseller.complaints') }}" style="color:#0c5460; font-weight:600; text-decoration:underline;">Review now &raquo;</a>
    </div>
@endif

<div class="grid-4" style="margin-bottom:20px;">
    <div class="card" style="border-left:4px solid #e74c3c;">
        <h3>Total Societies</h3>
        <div class="value">{{ $totalSocieties }}</div>
        <div style="font-size:12px; color:#999; margin-top:4px;">Active: {{ $activeSocieties }}</div>
    </div>
    <div class="card" style="border-left:4px solid #3498db;">
        <h3>Total Members</h3>
        <div class="value">{{ number_format($totalMembers) }}</div>
    </div>
    <div class="card" style="border-left:4px solid #f39c12;">
        <h3>New Societies This Year</h3>
        <div class="value">{{ $newThisYear }}</div>
        <div style="font-size:12px; color:#999; margin-top:4px;">Added in {{ date('Y') }}</div>
    </div>
    <div class="card" style="border-left:4px solid #95a5a6;">
        <h3>Inactive Societies</h3>
        <div class="value">{{ $droppedThisYear }}</div>
        <div style="font-size:12px; color:#999; margin-top:4px;">Status: Inactive</div>
    </div>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0;">Societies Overview</h3>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="text" id="society-search" class="form-control" placeholder="Search society..." oninput="filterSocieties()" style="max-width:250px; margin:0; padding:6px 10px; font-size:13px;">
            @if(Auth::user()->hasPermission('software', 'add'))
            <a href="{{ route('reseller.societies.create') }}" class="btn btn-primary btn-sm">Create Society</a>
            @endif
        </div>
    </div>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Society Name</th>
                    <th>Code</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="society-table-body">
                @forelse($assignedSocieties as $index => $assigned)
                <tr data-name="{{ strtolower($assigned->society->society_name ?? '') }}">
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $assigned->society->society_name ?? '-' }}</strong></td>
                    <td>{{ $assigned->society->society_code ?? '-' }}</td>
                    <td>{{ $societyMemberCounts[$assigned->societie_id] ?? 0 }}</td>
                    <td>
                        @if($assigned->society && $assigned->society->status == 1)
                            <span style="background:#d4edda; color:#155724; padding:3px 10px; border-radius:10px; font-size:12px;">Active</span>
                        @else
                            <span style="background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:10px; font-size:12px;">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($assigned->society && $assigned->society->status == 1)
                        <form method="POST" action="{{ route('reseller.societies.switch', $assigned->societie_id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Open Society</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#999;">No societies assigned. <a href="{{ route('reseller.societies.create') }}">Create your first society</a></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div id="society-count" style="font-size:12px; color:#999; margin-top:8px; text-align:right;">Total: {{ $totalSocieties }} societies</div>
</div>

<script>
function filterSocieties() {
    var query = document.getElementById('society-search').value.toLowerCase().trim();
    var rows = document.querySelectorAll('#society-table-body tr[data-name]');
    var visible = 0;
    rows.forEach(function(row) {
        var name = row.getAttribute('data-name') || '';
        if (name.indexOf(query) !== -1) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });
    document.getElementById('society-count').textContent = query ? 'Showing: ' + visible + ' of {{ $totalSocieties }}' : 'Total: {{ $totalSocieties }} societies';
}
</script>
@endsection
