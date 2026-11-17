@extends('layouts.app')

@section('title', 'WhatsApp APIs - EasyLogics')

@section('content')
<style>
.wa-chip { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.wa-ACTIVE { background:#dcfce7; color:#16a34a; } .wa-PENDING { background:#fef3c7; color:#d97706; }
.wa-DISCONNECTED { background:#fee2e2; color:#dc2626; } .wa-DISABLED { background:#f3f4f6; color:#4b5563; } .wa-NONE { background:#e0f2fe; color:#0369a1; }
.wa-edit summary { cursor:pointer; color:#3498db; font-weight:600; }
.wa-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px, 1fr)); gap:10px 14px; margin-top:10px; background:#fafafa; border:1px solid #eee; padding:12px; border-radius:6px; }
.wa-grid label { font-size:12px; font-weight:600; display:block; margin-bottom:3px; }
.wa-grid small { color:#777; font-size:11px; }
.wa-meta-only, .wa-k3-only { display:none; } .wa-grid[data-provider="meta"] .wa-meta-only { display:block; } .wa-grid[data-provider="k3"] .wa-k3-only { display:block; }
</style>

<div class="page-header">
    <h2>&#128241; WhatsApp APIs (Reseller-wise)</h2>
</div>

@if ($errors->any())
    <div class="alert alert-error"><span>{{ implode(' ', $errors->all()) }}</span></div>
@endif

<div class="card" style="margin-bottom:14px;">
    <p style="margin:0 0 8px;color:#555;">
        Har reseller ka apna WhatsApp number aur API yahan set kijiye. Us reseller ki societies ke bill <strong>usi ke number/API se</strong> jayenge.
        Jis reseller ka API set nahi hai (ya ACTIVE nahi hai), uski societies <strong>Common API</strong> (.env) se bhejengi.
        Ye page sirf Admin ke liye hai. API key ek baar save hone ke baad dobara nahi dikhti, sirf aakhri 4 akshar.
    </p>
    <table class="table table-bordered" style="font-size:13px;margin:0;">
        <tr>
            <th style="width:220px;">Common API (.env)</th>
            <td>
                {{ $common['provider'] }} &middot;
                Key: {!! $common['key'] ? '<span style="color:#16a34a;font-weight:600;">filled in</span>' : '<span style="color:#dc2626;font-weight:600;">empty</span>' !!} &middot;
                Template: {{ $common['template'] ?: '-' }} &middot;
                {{ $common['enabled'] ? 'Enabled' : 'Disabled' }} &middot;
                Fallback for resellers without own API: <strong>{{ $common['fallback'] ? 'ON' : 'OFF' }}</strong>
                <small style="color:#777;">(WHATSAPP_API_KEY / WHATSAPP_COMMON_FALLBACK in .env)</small>
            </td>
        </tr>
    </table>
</div>

@if (!$ready)
    <div class="alert alert-error"><span>Table <code>reseller_whatsapp_senders</code> (ya uske naye columns) abhi nahi hai. Pehle migration chalaiye:
        <code>php artisan migrate --path=database/migrations/2026_11_12_000002_create_reseller_whatsapp_senders_table.php</code></span>
        <form method="POST" action="{{ route('admin.whatsappSenders.migrate') }}" style="margin-top:8px;" onsubmit="return confirm('WhatsApp ki tables banayein? (sirf nayi tables/columns banengi, kuch delete nahi hoga)');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Create WhatsApp tables</button>
        </form></div>
@endif

<div class="card">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:10px;">
        <input type="search" id="waResellerSearch" class="form-control" style="max-width:360px;" placeholder="&#128269; Reseller search: naam, username, mobile ya WhatsApp number" autocomplete="off" oninput="waFilterResellers()">
        <select id="waStatusFilter" class="form-control" style="max-width:220px;" onchange="waFilterResellers()">
            <option value="">Sab resellers</option>
            <option value="OWN">Apna API set hai</option>
            <option value="ACTIVE">ACTIVE</option>
            <option value="PENDING">PENDING</option>
            <option value="DISCONNECTED">DISCONNECTED</option>
            <option value="DISABLED">DISABLED</option>
            <option value="COMMON">Common API wale</option>
        </select>
        <span id="waResellerCount" style="font-size:12px;color:#666;"></span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="font-size:13px;" id="waResellerTable">
            <thead>
                <tr>
                    <th>#</th><th>Reseller</th><th>Societies</th><th>WhatsApp Number</th><th>Provider</th>
                    <th>API Key</th><th>Template</th><th>Status</th><th style="min-width:280px;">Set / Edit</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($resellers as $i => $r)
                @php
                    $s = $senders->get($r->id);
                    $name = trim(($r->firstname ?? '') . ' ' . ($r->lastname ?? '')) ?: $r->username;
                    $prov = old('provider', $s->provider ?? 'interakt');
                @endphp
                <tr class="wa-row" data-search="{{ strtolower($name . ' ' . $r->username . ' ' . ($r->contact_no ?? '') . ' ' . ($s->sender_phone ?? '')) }}" data-status="{{ $s->status ?? 'COMMON' }}">
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $name }}</strong><br><small style="color:#777;">{{ $r->username }} {{ $r->contact_no ? '· ' . $r->contact_no : '' }}</small></td>
                    <td>{{ $societyCounts[$r->id] ?? 0 }}</td>
                    <td>{{ $s->sender_phone ?? '—' }}</td>
                    <td>{{ $s ? ($providers[$s->provider] ?? $s->provider) : '—' }}</td>
                    <td>{{ $s && $s->api_key_last4 ? '••••' . $s->api_key_last4 : '—' }}</td>
                    <td>{{ $s->template_name ?? '' ?: ($s ? 'common: ' . $common['template'] : '—') }}</td>
                    <td>
                        @if ($s)
                            <span class="wa-chip wa-{{ $s->status }}">{{ $s->status }}</span>
                            @if ($s->last_error)<br><small style="color:#dc2626;">{{ $s->last_error }}</small>@endif
                            @if ($s->provider === 'k3' && $s->api_key_enc)
                                <div style="margin-top:6px; display:flex; gap:4px; flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('admin.whatsappSenders.k3Webhook', $r->id) }}" onsubmit="return confirm('K3 ko webhook {{ route('whatsapp.webhook') }} set karein? (number {{ $s->sender_phone }})');">
                                        @csrf
                                        <input type="hidden" name="action" value="set">
                                        <button type="submit" class="btn btn-success btn-xs" title="K3 ko batata hai ki Delivered / Read status kahan bhejna hai">Set K3 Webhook</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whatsappSenders.k3Webhook', $r->id) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="get">
                                        <button type="submit" class="btn btn-default btn-xs" title="K3 mein abhi kaunsa webhook set hai">Check</button>
                                    </form>
                                </div>
                            @endif
                        @else
                            <span class="wa-chip wa-NONE">COMMON API</span>
                        @endif
                    </td>
                    <td>
                        @if ($ready)
                        <details class="wa-edit">
                            <summary>{{ $s ? 'Edit API' : '+ Set API' }}</summary>
                            <form method="POST" action="{{ route('admin.whatsappSenders.save', $r->id) }}">
                                @csrf
                                <div class="wa-grid" data-provider="{{ $prov }}">
                                    <div>
                                        <label>WhatsApp Number *</label>
                                        <input type="text" name="sender_phone" class="form-control" required value="{{ $s->sender_phone ?? '' }}" placeholder="98XXXXXXXX">
                                    </div>
                                    <div>
                                        <label>Provider *</label>
                                        <select name="provider" class="form-control" onchange="this.closest('.wa-grid').dataset.provider=this.value;">
                                            @foreach ($providers as $k => $label)
                                                <option value="{{ $k }}" @selected($prov === $k)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label>Status *</label>
                                        <select name="status" class="form-control">
                                            @foreach ($statuses as $st)
                                                <option value="{{ $st }}" @selected(($s->status ?? 'ACTIVE') === $st)>{{ $st }}</option>
                                            @endforeach
                                        </select>
                                        <small>Sirf ACTIVE se message jayega.</small>
                                    </div>
                                    <div>
                                        <label>API Key {{ $s ? '(khali = purani hi rahegi)' : '*' }}</label>
                                        <input type="password" name="api_key" class="form-control" autocomplete="new-password" placeholder="{{ $s && $s->api_key_last4 ? '••••' . $s->api_key_last4 : '' }}">
                                    </div>
                                    <div>
                                        <label>Webhook Secret (optional)</label>
                                        <input type="password" name="webhook_secret" class="form-control" autocomplete="new-password" placeholder="{{ $s && $s->webhook_secret_enc ? 'saved - khali = wahi rahega' : '' }}">
                                    </div>
                                    <div>
                                        <label>Template Name</label>
                                        <input type="text" name="template_name" class="form-control" value="{{ $s->template_name ?? '' }}" placeholder="{{ $common['template'] }}">
                                        <small>Khali = common template.</small>
                                    </div>
                                    <div>
                                        <label>Template Variables (order)</label>
                                        <input type="text" name="body_params" class="form-control" value="{{ $s->body_params ?? '' }}" placeholder="member_name,flat_no,bill_no,amount,due_date">
                                        <small>member_name, flat_no, amount, society_name, bill_month, bill_month_name (e.g. April 2026), bill_no, due_date</small>
                                    </div>
                                    <div>
                                        <label>Language Code</label>
                                        <input type="text" name="language_code" class="form-control" value="{{ $s->language_code ?? '' }}" placeholder="en">
                                    </div>
                                    <div class="wa-k3-only" style="grid-column:1/-1;">
                                        <small style="color:#0369a1;">K3: URL <code>https://partners.pinbot.ai/v2/messages</code>, header <code>apikey</code> + <code>wanumber</code> (upar wala WhatsApp number) apne aap lagenge. API key K3 se lijiye.
                                        Template <code>maintainanence_bill</code> ke liye Variables: <code>bill_month_name</code>.</small>
                                    </div>
                                    <div class="wa-meta-only">
                                        <label>API URL (send message) *</label>
                                        <input type="url" name="api_url" class="form-control" value="{{ $s->api_url ?? '' }}" placeholder="https://.../{phone_number_id}/messages">
                                        <small>Provider ke API docs wala URL. {phone_number_id} likh sakte hain.</small>
                                    </div>
                                    <div class="wa-meta-only">
                                        <label>Phone Number ID</label>
                                        <input type="text" name="phone_number_id" class="form-control" value="{{ $s->phone_number_id ?? '' }}">
                                    </div>
                                    <div class="wa-meta-only">
                                        <label>Auth Header</label>
                                        <input type="text" name="auth_header" class="form-control" value="{{ $s->auth_header ?? '' }}" placeholder="Bearer (default) ya apikey">
                                        <small>Khali = "Authorization: Bearer KEY"</small>
                                    </div>
                                </div>
                                <div style="margin-top:8px;">
                                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                                </div>
                            </form>
                            @if ($s && $s->webhook_secret_enc)
                                <form method="POST" action="{{ route('admin.whatsappSenders.clearSecret', $r->id) }}" style="margin-top:4px;" onsubmit="return confirm('Webhook secret hata dein?');">
                                    @csrf
                                    <button type="submit" class="btn btn-default btn-sm">Remove webhook secret</button>
                                </form>
                            @endif
                        </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Koi reseller nahi mila.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p style="font-size:12px;color:#777;margin:8px 0 0;">
        Delivered / Read status ke liye har provider ke panel mein webhook URL daaliye: <code>{{ route('whatsapp.webhook') }}</code>.
        K3 mein ye <code>https://partners.pinbot.ai/v2/setwebhooks</code> se set hota hai; webhook secret rakha ho to header <code>X-Webhook-Secret</code> mein wahi secret dijiye.
    </p>
</div>
@endsection

@section('scripts')
<script>
function waFilterResellers() {
    var q = (document.getElementById('waResellerSearch').value || '').toLowerCase().trim();
    var st = document.getElementById('waStatusFilter').value;
    var rows = document.querySelectorAll('#waResellerTable tr.wa-row'), shown = 0;
    rows.forEach(function (tr) {
        var okText = q === '' || tr.dataset.search.indexOf(q) !== -1;
        var rs = tr.dataset.status;
        var okStatus = st === '' || (st === 'OWN' ? rs !== 'COMMON' : rs === st);
        var show = okText && okStatus;
        tr.style.display = show ? '' : 'none';
        if (show) shown++;
    });
    document.getElementById('waResellerCount').textContent = shown + ' / ' + rows.length + ' resellers';
}
document.addEventListener('DOMContentLoaded', waFilterResellers);
</script>
@endsection
