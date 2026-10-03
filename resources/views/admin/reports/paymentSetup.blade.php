@extends('layouts.app')

@section('title', 'Payment Setup Check - EasyLogics')

@php
    $ok = fn ($good, $text) => new \Illuminate\Support\HtmlString(
        '<span style="color:' . ($good ? '#1e8449' : '#d9534f') . ';font-weight:600;">' . ($good ? '&#10004; ' : '&#10008; ') . e($text) . '</span>'
    );
@endphp

@section('content')
<div class="page-header">
    <h2>Payment Setup Check</h2>
</div>

<div class="card">
    <p style="color:#666; margin-bottom:12px;">
        Ye page dikhata hai ki <strong>is server</strong> ko online payment ka setup kaisa dikh raha hai. Koi key ya secret yahan nahi dikhta -
        sirf <code>rzp_test</code> / <code>rzp_live</code> ka prefix aur "filled in / empty".
    </p>

    @if ($ready)
        <div class="alert alert-success"><strong>Setup theek hai.</strong> Mode: <strong>{{ strtoupper($mode) }}</strong>. Neeche "Razorpay se test karo" se check kar sakte hain ki Razorpay ye keys maanta hai.</div>
    @else
        <div class="alert alert-error">
            <strong>Online payment abhi band hai.</strong> Wajah:
            <ul style="margin:6px 0 0 18px;">
                @foreach ($advice as $line)<li>{{ $line }}</li>@endforeach
                @if (empty($advice))<li>Config theek nahi hai ({{ $problem }}).</li>@endif
            </ul>
        </div>
    @endif

    <h4 style="margin-top:20px;">1. Config files (is server par)</h4>
    <table class="table table-bordered">
        <thead><tr><th style="width:170px;">File</th><th>Path (server par)</th><th style="width:330px;">Status</th></tr></thead>
        <tbody>
            @foreach ($files as $f)
            <tr>
                <td>{{ $f['name'] }}</td>
                <td style="font-size:12px;word-break:break-all;">{{ $f['path'] }}</td>
                <td>
                    @if (!$f['exists'])
                        {{ $ok(false, 'NOT FOUND at this path') }}
                    @elseif (!$f['readable'])
                        {{ $ok(false, 'exists, PHP cannot read it') }}
                    @elseif (!$f['loads'])
                        {{ $ok(false, 'BROKEN: ' . $f['error']) }}
                    @else
                        {{ $ok(true, 'found and loads') }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4 style="margin-top:20px;">2. Razorpay setting jo abhi chal rahi hai</h4>
    <table class="table table-bordered">
        <tbody>
            <tr><td style="width:170px;">Mode</td><td><strong>{{ $mode }}</strong> <span class="text-muted">(sirf isi naam ka block padha jaata hai)</span></td></tr>
            @foreach ($blocks as $blockName => $b)
            <tr @if ($blockName === $mode) style="background:#eef7ee;" @endif>
                <td>"{{ $blockName }}" block @if ($blockName === $mode)<span style="background:#27ae60;color:#fff;font-size:11px;padding:1px 6px;border-radius:3px;">in use</span>@endif</td>
                <td style="font-size:13px;">
                    key_id: <strong>{{ $b['key_id'] }}</strong> &nbsp;|&nbsp;
                    key_secret: <strong>{{ $b['key_secret'] }}</strong> &nbsp;|&nbsp;
                    webhook_secret: <strong>{{ $b['webhook_secret'] }}</strong>
                </td>
            </tr>
            @endforeach
            <tr><td>Result</td><td>{{ $ok($problem === '', $problem === '' ? 'keys present and match the mode' : 'problem = ' . $problem) }}</td></tr>
        </tbody>
    </table>

    <h4 style="margin-top:20px;">3. Database tables</h4>
    <table class="table table-bordered">
        <tbody>
            <tr><td style="width:170px;">reseller_payments</td><td>{{ $ok($tables['reseller_payments'], $tables['reseller_payments'] ? 'exists' : 'MISSING - payment history cannot be saved') }}</td></tr>
            <tr><td>reseller_plan_orders</td><td>{{ $ok($tables['reseller_plan_orders'], $tables['reseller_plan_orders'] ? 'exists' : 'MISSING - Pay Now cannot create an order (app/Config/Schema/reseller_plan_orders_table.sql)') }}</td></tr>
            <tr><td>reseller_plans</td><td>{{ $ok($tables['reseller_plans'], $tables['reseller_plans'] ? 'exists (prices come from Reports > Reseller Plans)' : 'missing - plans fall back to the built-in plans (app/Config/Schema/reseller_plans_table.sql)') }}</td></tr>
        </tbody>
    </table>

    <h4 style="margin-top:20px;">4. Server</h4>
    <table class="table table-bordered">
        <tbody>
            <tr><td style="width:170px;">PHP</td><td>{{ $server['php'] }}</td></tr>
            <tr><td>cURL (Razorpay ko call karne ke liye)</td><td>{{ $ok($server['curl'], $server['curl'] ? 'enabled' : 'NOT enabled - Pay Now cannot work') }}</td></tr>
            <tr><td>OpenSSL</td><td>{{ $ok($server['openssl'], $server['openssl'] ? 'enabled' : 'NOT enabled') }}</td></tr>
            <tr><td>Webhook URL<br><span class="text-muted" style="font-size:11px;">(Razorpay Dashboard &rarr; Webhooks; events: payment.captured, order.paid, payment.failed)</span></td><td class="text-muted">Payment webhook is not available in the Laravel app yet - keep using the CakePHP webhook URL until the payment flow is migrated.</td></tr>
        </tbody>
    </table>

    <h4 style="margin-top:20px;">5. Razorpay se test karo</h4>
    <p class="text-muted" style="font-size:12px; margin:6px 0;">Razorpay ko ek read-only request jaati hai (koi order/charge nahi banta) ye dekhne ke liye ki wo ye keys maanta hai ya nahi.</p>
    <form method="post" action="{{ route('admin.reports.paymentSetup') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">Razorpay se test karo</button>
    </form>
    @if ($test !== null)
        <div class="alert {{ $test['ok'] ? 'alert-success' : 'alert-error' }}" style="margin-top:10px;">{{ $test['message'] }}</div>
    @endif
</div>
@endsection
