@extends('layouts.app')

@section('title', 'Reseller Dashboard - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Reseller Dashboard</h2>
</div>

@if($creditInfo['credit'] > 0)
<div style="background:linear-gradient(135deg,#1a5276,#2980b9); border-radius:8px; padding:16px 20px; margin-bottom:20px; color:#fff; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:0.8;">Member Credit</div>
        <div style="font-size:28px; font-weight:700; margin-top:2px;">{{ $creditInfo['used'] }} <span style="font-size:16px; opacity:0.7;">/ {{ $creditInfo['credit'] }}</span></div>
    </div>
    <div style="text-align:right;">
        <div style="font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:0.8;">Remaining</div>
        <div style="font-size:28px; font-weight:700; margin-top:2px; color:{{ $creditInfo['remaining'] > 0 ? '#2ecc71' : '#e74c3c' }};">{{ $creditInfo['remaining'] }}</div>
    </div>
    <div style="width:100%; background:rgba(255,255,255,0.2); border-radius:4px; height:8px; overflow:hidden;">
        @php $pct = $creditInfo['credit'] > 0 ? min(100, ($creditInfo['used'] / $creditInfo['credit']) * 100) : 0; @endphp
        <div style="width:{{ $pct }}%; height:100%; background:{{ $pct >= 90 ? '#e74c3c' : ($pct >= 70 ? '#f39c12' : '#2ecc71') }}; border-radius:4px; transition:width 0.3s;"></div>
    </div>
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
            <a href="{{ route('reseller.societies.create') }}" class="btn btn-primary btn-sm">Create Society</a>
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
