@extends('layouts.app')

@section('title', 'Reseller Plans & Prices - EasyLogics')

@php
    // Everyone's plans vs. special plans that only one reseller sees.
    $generalPlans = [];
    $specialPlans = [];
    foreach ($plans as $p) {
        if (!empty($p->only_reseller_id)) {
            $specialPlans[] = $p;
        } else {
            $generalPlans[] = $p;
        }
    }
    $pickerName = fn ($id) => $usernames[(int) $id] ?? '';
@endphp

@section('content')
<style>
    .rp-wrap { position: relative; min-width: 210px; }
    .rp-list { display: none; position: absolute; top: 100%; left: 0; right: 0; min-width: 300px; z-index: 1000; background: #fff; border: 1px solid #ccc; box-shadow: 0 4px 10px rgba(0,0,0,.15); max-height: 300px; overflow-y: auto; }
    .rp-item { padding: 6px 10px; cursor: pointer; border-bottom: 1px solid #eee; overflow: hidden; }
    .rp-item small { color: #999; display: block; }
    .rp-chosen { font-size: 11px; margin-top: 2px; color: #1e8449; }
    .plan-input-days { width: 90px; } .plan-input-price { width: 120px; } .plan-input-order { width: 90px; }
    .plan-inline { display: flex; gap: 8px; align-items: flex-start; flex-wrap: wrap; }
    .plan-inline .form-control { width: auto; }
    #planTabs a { cursor: pointer; }
</style>

<div class="page-header">
    <h2>Reseller Plans &amp; Prices</h2>
</div>

<div class="card">
    @if ($tableMissing)
        <div class="alert alert-error">
            Plans table abhi nahi bani. Database mein <code>app/Config/Schema/reseller_plans_table.sql</code> chalaiye, phir ye page kholiye.
            Tab tak resellers ko built-in plans dikhte hain.
        </div>
    @else
        <p style="color:#666; margin-bottom:12px;">
            Badlav turant reseller ke Payment Dashboard par dikhta hai. Jo payment ho chuki hai uski price/invoice nahi badalti -
            har order apni price ke saath save rehta hai.
        </p>
        <ul class="nav-tabs" id="planTabs" style="margin-bottom:15px;">
            <li class="active"><a data-tab="general">Sab resellers ke plans ({{ count($generalPlans) }})</a></li>
            <li><a data-tab="special">Special plan - ek reseller ke liye ({{ count($specialPlans) }})</a></li>
        </ul>

        <!-- TAB 1: plans everybody sees -->
        <div id="tab-general">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Plan</th><th>Name</th><th>Days</th><th>Price (Rs.)</th><th>Order</th>
                            <th style="text-align:center;">Active</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($generalPlans as $p)
                            @php $formId = 'planForm' . (int) $p->id; @endphp
                            <tr @if (empty($p->is_active)) style="background:#f7f7f7;color:#888;" @endif>
                                <td style="font-size:11px;">{{ $p->plan_key }}</td>
                                <td><input form="{{ $formId }}" type="text" name="name" value="{{ $p->name }}" maxlength="100" required class="form-control"></td>
                                <td><input form="{{ $formId }}" type="number" name="days" value="{{ (int) $p->days }}" min="1" max="3650" step="1" required class="form-control plan-input-days"></td>
                                <td><input form="{{ $formId }}" type="number" name="amount" value="{{ number_format((float) $p->amount, 2, '.', '') }}" min="100" step="0.01" required class="form-control plan-input-price"></td>
                                <td><input form="{{ $formId }}" type="number" name="sort_order" value="{{ (int) $p->sort_order }}" min="0" max="999" step="1" class="form-control plan-input-order"></td>
                                <td style="text-align:center;"><input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked(!empty($p->is_active))></td>
                                <td>
                                    <form id="{{ $formId }}" method="post" action="{{ route('admin.reports.resellerPlans.save', (int) $p->id) }}" onsubmit="return confirm('Save this plan?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        @if (empty($generalPlans))
                            <tr><td colspan="7" class="text-muted">Koi plan nahi - niche se jodiye.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <h4 style="margin-top:20px; margin-bottom:8px;">Naya plan jodo (sab resellers ke liye)</h4>
            <form method="post" action="{{ route('admin.reports.resellerPlans.save') }}" class="plan-inline" onsubmit="return confirm('Add this plan for ALL resellers?');">
                @csrf
                <input type="text" name="name" placeholder="Plan name" maxlength="100" required class="form-control" style="width:200px;">
                <input type="number" name="days" placeholder="Days" min="1" max="3650" step="1" required class="form-control" style="width:90px;">
                <input type="number" name="amount" placeholder="Price Rs." min="100" step="0.01" required class="form-control" style="width:120px;">
                <input type="number" name="sort_order" value="50" min="0" max="999" step="1" title="Order" class="form-control" style="width:80px;">
                <label style="margin:6px 8px 0;"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button type="submit" class="btn btn-sm btn-success">Add plan</button>
            </form>
            <p class="text-muted" style="margin-top:8px;font-size:12px;">Rs. 100 se kam ka plan sab resellers ke liye nahi ho sakta - wo Special tab se ek reseller ko hi diya ja sakta hai.</p>
        </div>

        <!-- TAB 2: a plan only one chosen reseller sees -->
        <div id="tab-special" style="display:none;">
            <div class="alert alert-info">
                Yahan kisi <strong>ek reseller</strong> ke liye alag plan/price rakhiye: reseller ka naam search karke list se chuniye.
                Ye plan sirf usi reseller ke Payment Dashboard par dikhega, baaki kisi ko nahi. Re. 1 ka live test bhi yahin se hota hai:
                apne test reseller ko chuniye, price 1 rakhiye, Save kijiye, uske login se payment kijiye, phir plan ka Active hata dijiye.
            </div>

            <h4 style="margin-bottom:8px;">Naya special plan</h4>
            <form method="post" action="{{ route('admin.reports.resellerPlans.save') }}" class="plan-inline js-special-form" style="margin-bottom:20px;">
                @csrf
                <input type="hidden" name="require_reseller" value="1">
                <div class="rp-wrap">
                    <input type="hidden" name="only_reseller_id" value="" class="rp-id">
                    <input type="text" class="form-control rp-input" placeholder="Reseller search karo (naam / username / ID)" autocomplete="off" style="width:100%;">
                    <div class="rp-chosen"></div>
                    <div class="rp-list"></div>
                </div>
                <input type="text" name="name" placeholder="Plan name" maxlength="100" required class="form-control" style="width:180px;">
                <input type="number" name="days" placeholder="Days" min="1" max="3650" step="1" required class="form-control" style="width:90px;">
                <input type="number" name="amount" placeholder="Price Rs." min="0.01" step="0.01" required class="form-control" style="width:110px;">
                <input type="number" name="sort_order" value="50" min="0" max="999" step="1" title="Order" class="form-control" style="width:80px;">
                <label style="margin:6px 8px 0;"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button type="submit" class="btn btn-sm btn-success">Add special plan</button>
            </form>

            <h4 style="margin-bottom:8px;">Maujooda special plans</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Reseller</th><th>Name</th><th>Days</th><th>Price (Rs.)</th><th>Order</th>
                            <th style="text-align:center;">Active</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($specialPlans as $p)
                            @php
                                $formId = 'planForm' . (int) $p->id;
                                $oid = (int) $p->only_reseller_id;
                            @endphp
                            <tr @if (empty($p->is_active)) style="background:#f7f7f7;color:#888;" @endif>
                                <td>
                                    <div class="rp-wrap">
                                        <input form="{{ $formId }}" type="hidden" name="only_reseller_id" value="{{ $oid }}" class="rp-id">
                                        <input type="text" class="form-control rp-input" value="{{ $pickerName($oid) }}" placeholder="Reseller badalne ke liye search karo" autocomplete="off">
                                        <div class="rp-chosen">@if ($pickerName($oid) !== ''){{ $pickerName($oid) }} &middot; ID {{ $oid }}@else<span style="color:#d9534f;">unknown ID {{ $oid }}</span>@endif</div>
                                        <div class="rp-list"></div>
                                    </div>
                                </td>
                                <td><input form="{{ $formId }}" type="text" name="name" value="{{ $p->name }}" maxlength="100" required class="form-control"></td>
                                <td><input form="{{ $formId }}" type="number" name="days" value="{{ (int) $p->days }}" min="1" max="3650" step="1" required class="form-control plan-input-days"></td>
                                <td><input form="{{ $formId }}" type="number" name="amount" value="{{ number_format((float) $p->amount, 2, '.', '') }}" min="0.01" step="0.01" required class="form-control plan-input-price"></td>
                                <td><input form="{{ $formId }}" type="number" name="sort_order" value="{{ (int) $p->sort_order }}" min="0" max="999" step="1" class="form-control plan-input-order"></td>
                                <td style="text-align:center;"><input form="{{ $formId }}" type="checkbox" name="is_active" value="1" @checked(!empty($p->is_active))></td>
                                <td>
                                    <form id="{{ $formId }}" method="post" class="js-special-form" action="{{ route('admin.reports.resellerPlans.save', (int) $p->id) }}">
                                        @csrf
                                        <input type="hidden" name="require_reseller" value="1">
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        @if (empty($specialPlans))
                            <tr><td colspan="7" class="text-muted">Abhi koi special plan nahi.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@if (!$tableMissing)
@section('scripts')
<script>
(function () {
    var searchUrl = @json(route('admin.reports.searchResellers'));
    // ---- tabs (remembered in the URL hash so a save returns to the same tab) ----
    var tabs = { general: document.getElementById('tab-general'), special: document.getElementById('tab-special') };
    var links = document.querySelectorAll('#planTabs a');
    function showTab(name) {
        if (!tabs[name]) { name = 'general'; }
        for (var k in tabs) { tabs[k].style.display = (k === name) ? '' : 'none'; }
        for (var i = 0; i < links.length; i++) {
            links[i].parentNode.className = (links[i].getAttribute('data-tab') === name) ? 'active' : '';
        }
        try { history.replaceState(null, '', '#' + name); } catch (e) {}
    }
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function () { showTab(this.getAttribute('data-tab')); });
    }
    showTab(window.location.hash.replace('#', ''));
    // ---- reseller search pickers: type, pick from the list, the ID goes into the hidden field ----
    function line(text, css) { var d = document.createElement('div'); d.textContent = text; if (css) { d.style.cssText = css; } return d; }
    function initPicker(wrap) {
        var idField = wrap.querySelector('.rp-id');
        var input = wrap.querySelector('.rp-input');
        var list = wrap.querySelector('.rp-list');
        var chosen = wrap.querySelector('.rp-chosen');
        var timer = null, seq = 0;
        function hide() { list.style.display = 'none'; list.innerHTML = ''; }
        function choose(r) {
            idField.value = r.id;
            input.value = r.username;
            chosen.style.color = '#1e8449';
            chosen.textContent = (r.name ? r.name + ' · ' : '') + r.username + ' · ID ' + r.id;
            hide();
        }
        function render(rows) {
            list.innerHTML = '';
            if (!rows.length) { list.appendChild(line('Koi reseller nahi mila', 'padding:8px 10px;color:#999;font-size:12px;')); list.style.display = 'block'; return; }
            rows.forEach(function (r) {
                var el = document.createElement('div');
                el.className = 'rp-item';
                el.appendChild(line(r.name !== '' ? r.name : r.username, 'font-weight:600;font-size:13px;'));
                var small = document.createElement('small');
                small.textContent = r.username + ' · ID ' + r.id + (r.area ? ' · ' + r.area : '') + (r.active ? '' : ' · Inactive');
                el.appendChild(small);
                el.addEventListener('mouseenter', function () { el.style.background = '#e8f0fe'; });
                el.addEventListener('mouseleave', function () { el.style.background = '#fff'; });
                el.addEventListener('mousedown', function (e) { e.preventDefault(); choose(r); });
                list.appendChild(el);
            });
            list.style.display = 'block';
        }
        function run() {
            var q = input.value.replace(/^\s+|\s+$/g, '');
            if (q.length < 2 && !/^[0-9]+$/.test(q)) { hide(); return; }
            var mine = ++seq;
            var xhr = new XMLHttpRequest();
            xhr.open('GET', searchUrl + '?q=' + encodeURIComponent(q), true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function () { if (mine !== seq) { return; } try { render(JSON.parse(xhr.responseText)); } catch (e) { hide(); } };
            xhr.onerror = hide;
            xhr.send();
        }
        input.addEventListener('input', function () {
            // Typing again means the earlier pick no longer stands until a new one is chosen from the list.
            idField.value = '';
            chosen.style.color = '#d9534f';
            chosen.textContent = 'List se reseller chuniye';
            clearTimeout(timer); timer = setTimeout(run, 200);
        });
        input.addEventListener('blur', function () { setTimeout(hide, 150); });
    }
    var wraps = document.querySelectorAll('.rp-wrap');
    for (var w = 0; w < wraps.length; w++) { initPicker(wraps[w]); }
    // A special plan cannot be saved without a picked reseller; ask before saving.
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.classList || !f.classList.contains('js-special-form')) { return; }
        var picked = f.querySelector('.rp-id');
        if (!picked) { picked = document.querySelector('.rp-id[form="' + f.id + '"]'); }
        if (!picked || picked.value === '') { e.preventDefault(); alert('Pehle search karke list se ek reseller chuniye.'); return; }
        if (!confirm('Save this special plan for the selected reseller?')) { e.preventDefault(); }
    });
})();
</script>
@endsection
@endif
