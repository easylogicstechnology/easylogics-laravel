@extends('layouts.app')

@section('title', 'My Societies - EasyLogics')

@section('content')
<div class="page-header">
    <h2>My Assigned Societies</h2>
    @if(Auth::user()->hasPermission('software', 'add'))
    <a href="{{ route('reseller.societies.create') }}" class="btn btn-primary">Create Society</a>
    @endif
</div>

<div class="card">
    <h3>Select Society & Financial Year</h3>
    <form method="POST" action="" id="switchSocietyForm" style="margin-top:12px;">
        @csrf
        <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="flex:1; min-width:250px; margin-bottom:0;">
                <label>Society Name</label>
                <select class="form-control" name="society_id" id="society_select" required>
                    <option value="">Select Society</option>
                    @foreach($assignedSocieties as $assigned)
                        @if($assigned->society && $assigned->society->status == 1)
                        <option value="{{ $assigned->societie_id }}">{{ $assigned->society->society_name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:250px; margin-bottom:0;">
                <label>Financial Year</label>
                <select class="form-control" name="financial_year_id" id="year_select" required>
                    <option value="">Select Year</option>
                </select>
            </div>
            <div style="margin-bottom:0;">
                <button type="submit" class="btn btn-success" id="proceedBtn" disabled>Proceed</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:12px;">All Assigned Societies</h3>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Society Name</th>
                    <th>Society Code</th>
                    <th>Members</th>
                    <th>Features</th>
                    <th>Last Updated</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignedSocieties as $index => $assigned)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $assigned->society->user->username ?? '' }}</td>
                    <td>{{ $assigned->society->society_name ?? '' }}</td>
                    <td>{{ $assigned->society->society_code ?? '' }}</td>
                    <td>
                        <strong>{{ $societyMemberCounts[$assigned->societie_id] ?? 0 }}</strong>
                    </td>
                    <td>
                        @if($assigned->society)
                            @if($assigned->society->enable_sms === 'Y')
                                <span style="background:#d4edda; color:#155724; padding:2px 6px; border-radius:8px; font-size:11px;">SMS</span>
                            @else
                                <span style="color:#999; font-size:12px;">None</span>
                            @endif
                        @endif
                    </td>
                    <td>{{ $assigned->society->udate ? date('d/m/Y H:i', strtotime($assigned->society->udate)) : '' }}</td>
                    <td>
                        @if($assigned->society && $assigned->society->status == 1)
                            <span style="color:#27ae60; font-weight:600; font-size:12px;">Active</span>
                        @else
                            <span style="color:#999; font-size:12px;">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:#999;">No societies assigned</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('society_select').addEventListener('change', function() {
    var societyId = this.value;
    var yearSelect = document.getElementById('year_select');
    var proceedBtn = document.getElementById('proceedBtn');
    var form = document.getElementById('switchSocietyForm');

    yearSelect.innerHTML = '<option value="">Select Year</option>';
    proceedBtn.disabled = true;

    if (!societyId) return;

    form.action = "{{ url('reseller/societies/switch') }}/" + societyId;

    fetch("{{ url('reseller/societies/years') }}/" + societyId, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(years) {
        yearSelect.innerHTML = '<option value="">Select Year</option>';
        if (years.length === 0) {
            yearSelect.innerHTML = '<option value="">No years mapped</option>';
            return;
        }

        var today = new Date();
        var todayStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');
        var currentId = null;
        var latestId = years[0].id;

        years.forEach(function(y) {
            var s = (y.year_start_date || '').substring(0, 10);
            var e = (y.year_end_date || '').substring(0, 10);
            if (s && e && todayStr >= s && todayStr <= e) currentId = y.id;
        });

        if (currentId === null) currentId = latestId;

        years.forEach(function(y) {
            var opt = document.createElement('option');
            opt.value = y.id;
            opt.textContent = y.year;
            if (y.id == currentId) opt.selected = true;
            yearSelect.appendChild(opt);
        });

        proceedBtn.disabled = false;
    });
});

document.getElementById('year_select').addEventListener('change', function() {
    document.getElementById('proceedBtn').disabled = !this.value;
});
</script>
@endsection
