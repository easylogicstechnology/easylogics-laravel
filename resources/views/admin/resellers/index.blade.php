@extends('layouts.app')

@section('title', 'Reseller Credits - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Reseller Member Credits</h2>
</div>

<div id="credit-notify"></div>

@if(count($resellerData) === 0)
<div class="card">
    <p style="text-align:center; color:#999;">No resellers found. Create a reseller from Add Society page first.</p>
</div>
@else
<div class="card" style="margin-bottom:0; border-bottom:none; border-radius:6px 6px 0 0; padding-bottom:12px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <input type="text" id="reseller-search" class="form-control" placeholder="Search reseller by name..." oninput="filterResellers()" style="max-width:350px; margin:0;">
        <span id="reseller-count" style="font-size:13px; color:#666;">Total: {{ count($resellerData) }} resellers</span>
    </div>
</div>
<div class="card" style="border-radius:0 0 6px 6px;">
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Reseller Name</th>
                    <th>Total Societies</th>
                    <th>Members Used</th>
                    <th>Member Credit Limit</th>
                    <th>Remaining</th>
                    <th>Set Credit</th>
                </tr>
            </thead>
            <tbody id="reseller-table-body">
                @foreach($resellerData as $index => $data)
                <tr data-name="{{ strtolower($data['user']->username) }}">
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $data['user']->username }}</strong></td>
                    <td>{{ $data['total_societies'] }}</td>
                    <td>{{ $data['total_members'] }}</td>
                    <td>
                        <span id="credit-display-{{ $data['user']->id }}">{{ $data['credit'] }}</span>
                    </td>
                    <td>
                        <span style="color: {{ $data['remaining'] <= 0 && $data['credit'] > 0 ? '#e74c3c' : '#27ae60' }}; font-weight:600;">
                            {{ $data['remaining'] }}
                        </span>
                        @if($data['credit'] > 0 && $data['remaining'] <= 0)
                            <span style="color:#e74c3c; font-size:11px;">(Limit Reached)</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <input type="number" id="credit-input-{{ $data['user']->id }}" value="{{ $data['credit'] }}" min="0" style="width:100px; padding:4px 8px; border:1px solid #ddd; border-radius:4px; font-size:13px;">
                            <button class="btn btn-success btn-sm" onclick="updateCredit({{ $data['user']->id }})">Save</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="background:#fffbe6; border:1px solid #ffe58f;">
    <p style="font-size:13px; color:#8a6d3b; margin:0;">
        <strong>How it works:</strong> Member Credit is the maximum number of members a reseller can create across ALL their societies combined.
        If credit is 0, the reseller has unlimited member creation (no restriction). Set a value like 500 to restrict.
        When the limit is reached, the reseller must contact Admin to increase credit.
    </p>
</div>
@endif

<script>
function filterResellers() {
    var query = document.getElementById('reseller-search').value.toLowerCase().trim();
    var rows = document.querySelectorAll('#reseller-table-body tr');
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
    document.getElementById('reseller-count').textContent = query ? 'Showing: ' + visible + ' of {{ count($resellerData) }}' : 'Total: {{ count($resellerData) }} resellers';
}

function updateCredit(resellerId) {
    var creditInput = document.getElementById('credit-input-' + resellerId);
    var credit = parseInt(creditInput.value) || 0;
    var notify = document.getElementById('credit-notify');

    fetch('{{ route("admin.resellers.updateCredit") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ reseller_id: resellerId, member_credit: credit })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error === 0) {
            notify.innerHTML = '<div class="alert alert-success">' + data.error_message + '</div>';
            document.getElementById('credit-display-' + resellerId).textContent = credit;
        } else {
            notify.innerHTML = '<div class="alert alert-error">' + (data.error_message || 'Something went wrong') + '</div>';
        }
        setTimeout(function() { notify.innerHTML = ''; }, 3000);
    })
    .catch(function() {
        notify.innerHTML = '<div class="alert alert-error">Something went wrong. Please try again.</div>';
    });
}
</script>
@endsection
