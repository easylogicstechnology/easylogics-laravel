@extends('layouts.app')

@section('title', ($singleSocietyRecord ? 'Edit' : 'Create') . ' Society Login - EasyLogics')

@section('content')
<div class="page-header">
    <h2>{{ $singleSocietyRecord ? 'Edit Society Login' : 'Create Society Login' }}</h2>
    <a href="{{ route('admin.societies.index') }}" class="btn btn-primary">View Societies</a>
</div>

<div class="card" style="max-width: 700px;">
    <div id="show_notify_error"></div>

    <form id="SocietyLoginForm" autocomplete="off">
        @csrf
        <input type="hidden" name="society_user_id" value="{{ $singleSocietyRecord->id ?? '' }}">

        <div class="form-group">
            <label>Access Level <span class="required">*</span></label>
            <select class="form-control" id="access_level" name="access_level" required onchange="toggleSocietyFields()">
                <option value="">Select</option>
                <option value="2" {{ (isset($singleSocietyRecord) && $singleSocietyRecord->access_level == 2) ? 'selected' : '' }}>Society (Individual)</option>
                <option value="3" {{ (isset($singleSocietyRecord) && $singleSocietyRecord->access_level == 3) ? 'selected' : '' }}>Reseller</option>
            </select>
        </div>

        <div class="form-group">
            <label>Username <span class="required">*</span></label>
            <input type="text" class="form-control" name="username" placeholder="Username" value="{{ $singleSocietyRecord->username ?? '' }}" maxlength="75" required>
        </div>

        @if(!$singleSocietyRecord)
        <div class="form-group">
            <label>Password <span class="required">*</span></label>
            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
        </div>
        @endif

        @php
            $society = $singleSocietyRecord?->societies?->first();
        @endphp
        <div class="reseller-fields" style="{{ (!$singleSocietyRecord || $singleSocietyRecord->access_level != 3) ? 'display:none' : '' }}">
            <div class="form-group">
                <label>Member Credit Limit</label>
                <input type="number" class="form-control" name="member_credit" placeholder="0 = Unlimited" value="{{ $singleSocietyRecord->member_credit ?? 0 }}" min="0">
                <small style="color:#999; font-size:12px;">Maximum members this reseller can create across all societies. 0 = no limit.</small>
            </div>
        </div>

        <div class="society-fields" style="{{ ($singleSocietyRecord && $singleSocietyRecord->access_level == 3) ? 'display:none' : '' }}">
            <div class="form-group">
                <label>Society Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="society_name" name="society_name" placeholder="Society Name" value="{{ $society->society_name ?? '' }}" maxlength="75" onblur="generateShortCode(this.value)">
            </div>

            <div class="form-group">
                <label>Society ShortCode <span class="required">*</span></label>
                <input type="text" class="form-control" id="society_code" name="society_code" placeholder="Society ShortCode" value="{{ $society->society_code ?? '' }}" maxlength="75" readonly>
            </div>

            <div class="form-group">
                <label>Enable SMS <span class="required">*</span></label>
                <select class="form-control" name="enable_sms">
                    <option value="N" {{ (optional($society)->enable_sms !== 'Y') ? 'selected' : '' }}>No</option>
                    <option value="Y" {{ (optional($society)->enable_sms === 'Y') ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>

        <button type="button" class="btn btn-success" onclick="submitForm()">
            {{ $singleSocietyRecord ? 'Update' : 'Add' }}
        </button>
    </form>
</div>

<script>
function toggleSocietyFields() {
    var val = document.getElementById('access_level').value;
    document.querySelector('.society-fields').style.display = (val === '3') ? 'none' : '';
    document.querySelector('.reseller-fields').style.display = (val === '3') ? '' : 'none';
}

function generateShortCode(name) {
    if (name) {
        var words = name.trim().split(/\s+/);
        var code = words.map(function(w) { return w.charAt(0).toUpperCase(); }).join('');
        document.getElementById('society_code').value = code;
    }
}

function submitForm() {
    var form = document.getElementById('SocietyLoginForm');
    var formData = new FormData(form);
    var notify = document.getElementById('show_notify_error');

    fetch('{{ route("admin.societies.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.error === 0) {
            notify.innerHTML = '<div class="alert alert-success">' + data.error_message + '</div>';
            setTimeout(function() { window.location.href = '{{ route("admin.societies.index") }}'; }, 1500);
        } else if (data.errors) {
            var msgs = Object.values(data.errors).flat().join('<br>');
            notify.innerHTML = '<div class="alert alert-error">' + msgs + '</div>';
        } else {
            notify.innerHTML = '<div class="alert alert-error">' + (data.error_message || data.message || 'Something went wrong') + '</div>';
        }
    })
    .catch(function(err) {
        notify.innerHTML = '<div class="alert alert-error">Something went wrong. Please try again.</div>';
    });
}

toggleSocietyFields();
</script>
@endsection
