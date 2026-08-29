@extends('layouts.app')

@section('title', 'Society Features - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Society Features (SMS / WhatsApp / Mobile App)</h2>
</div>

<div id="feature-notify"></div>

@if($societies->isEmpty())
<div class="card">
    <p style="text-align:center; color:#999;">No active societies found.</p>
</div>
@else
<div class="card">
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Society Name</th>
                    <th>Code</th>
                    <th>SMS</th>
                    <th>WhatsApp</th>
                    <th>Mobile App</th>
                </tr>
            </thead>
            <tbody>
                @foreach($societies as $index => $society)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $society->society_name }}</strong></td>
                    <td>{{ $society->society_code }}</td>
                    <td>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" onchange="toggleFeature({{ $society->id }}, 'enable_sms', this.checked)" {{ $society->enable_sms === 'Y' ? 'checked' : '' }}>
                            <span class="feature-badge" id="sms-badge-{{ $society->id }}" style="font-size:11px; padding:2px 8px; border-radius:10px; {{ $society->enable_sms === 'Y' ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;' }}">
                                {{ $society->enable_sms === 'Y' ? 'ON' : 'OFF' }}
                            </span>
                        </label>
                    </td>
                    <td>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" onchange="toggleFeature({{ $society->id }}, 'whatsapp_enabled', this.checked)" {{ $society->whatsapp_enabled ? 'checked' : '' }}>
                            <span class="feature-badge" id="whatsapp-badge-{{ $society->id }}" style="font-size:11px; padding:2px 8px; border-radius:10px; {{ $society->whatsapp_enabled ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;' }}">
                                {{ $society->whatsapp_enabled ? 'ON' : 'OFF' }}
                            </span>
                        </label>
                    </td>
                    <td>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" onchange="toggleFeature({{ $society->id }}, 'mobile_app_enabled', this.checked)" {{ $society->mobile_app_enabled ? 'checked' : '' }}>
                            <span class="feature-badge" id="mobile-badge-{{ $society->id }}" style="font-size:11px; padding:2px 8px; border-radius:10px; {{ $society->mobile_app_enabled ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;' }}">
                                {{ $society->mobile_app_enabled ? 'ON' : 'OFF' }}
                            </span>
                        </label>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="background:#fffbe6; border:1px solid #ffe58f;">
    <p style="font-size:13px; color:#8a6d3b; margin:0;">
        <strong>How it works:</strong> Toggle SMS, WhatsApp, or Mobile App for each society. Only enabled features will be visible and accessible to the society.
    </p>
</div>
@endif

<script>
function toggleFeature(societyId, feature, checked) {
    var notify = document.getElementById('feature-notify');
    var value = checked ? '1' : '0';

    fetch('{{ route("admin.societies.updateFeatures") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ society_id: societyId, feature: feature, value: value })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error === 0) {
            notify.innerHTML = '<div class="alert alert-success">' + data.error_message + '</div>';
            var badgeMap = { enable_sms: 'sms', whatsapp_enabled: 'whatsapp', mobile_app_enabled: 'mobile' };
            var badge = document.getElementById(badgeMap[feature] + '-badge-' + societyId);
            if (badge) {
                badge.textContent = checked ? 'ON' : 'OFF';
                badge.style.background = checked ? '#d4edda' : '#f8d7da';
                badge.style.color = checked ? '#155724' : '#721c24';
            }
        } else {
            notify.innerHTML = '<div class="alert alert-error">' + (data.error_message || 'Error') + '</div>';
        }
        setTimeout(function() { notify.innerHTML = ''; }, 3000);
    })
    .catch(function() {
        notify.innerHTML = '<div class="alert alert-error">Something went wrong.</div>';
    });
}
</script>
@endsection
