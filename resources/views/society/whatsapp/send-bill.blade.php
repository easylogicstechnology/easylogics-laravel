{{-- CakePHP app/View/SocietyBills/send_bill_whatsapp.ctp: same form, same counters, same table and the same script (only the URLs and the loader are Laravel's) --}}
@extends('layouts.app')
@section('title', 'Send WhatsApp Bill - EasyLogics')
@section('content')
@php
// 1-Monthly, 2-BiMonthly, 3-Quarterly, 4-Quadruple, 5-Half Yearly, 6-Yearly
$billingFrequenciesArray = [
    1 => ['4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December', '1' => 'January', '2' => 'February', '3' => 'March'],
    2 => ['4' => 'Apr-May', '6' => 'Jun-Jul', '8' => 'Aug-Sep', '10' => 'Oct-Nov', '12' => 'Dec-Jan', '2' => 'Feb-Mar'],
    3 => ['4' => 'Apr-May-Jun', '7' => 'Jul-Aug-Sep', '10' => 'Oct-Nov-Dec', '1' => 'Jan-Feb-Mar'],
    4 => [],
    5 => ['4' => 'Apr-Sep', '10' => 'Oct-Mar'],
    6 => ['4' => 'April-March'],
];
$months = $billingFrequenciesArray[$billingFrequencyId] ?? [];
@endphp
<style>
.wa-counter-bar { display:flex; flex-wrap:wrap; gap:8px; margin:15px 0; }
.wa-counter { flex:1; min-width:100px; text-align:center; padding:10px 8px; border-radius:6px; border:1px solid #ddd; background:#fff; }
.wa-counter .num { font-size:24px; font-weight:700; line-height:1; }
.wa-counter .lbl { font-size:11px; text-transform:uppercase; font-weight:600; margin-top:4px; letter-spacing:0.5px; }
.wa-counter.c-total .num { color:#3b82f6; }
.wa-counter.c-sent .num { color:#22c55e; }
.wa-counter.c-failed .num { color:#ef4444; }
.wa-counter.c-pending .num { color:#f59e0b; }
.wa-counter.c-limit .num { color:#8b5cf6; }
.wa-counter.c-invalid .num { color:#ec4899; }
.wa-counter.c-pdf .num { color:#6b7280; }
.wa-counter.c-delivered .num { color:#2563eb; }
.wa-counter.c-read .num { color:#4f46e5; }
.wa-progress-bar { height:8px; border-radius:4px; background:#e5e7eb; overflow:hidden; margin:10px 0; display:flex; }
.wa-progress-bar .seg-sent { background:#22c55e; }
.wa-progress-bar .seg-delivered { background:#3b82f6; }
.wa-progress-bar .seg-read { background:#6366f1; }
.wa-progress-bar .seg-failed { background:#ef4444; }
.wa-progress-bar .seg-limit { background:#8b5cf6; }
.wa-progress-bar .seg-invalid { background:#ec4899; }
.wa-progress-bar .seg-pdf { background:#6b7280; }
.wa-progress-bar .seg-pending { background:#fbbf24; }
.chip { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
.chip-SENT { background:#dcfce7; color:#16a34a; }
.chip-DELIVERED { background:#dbeafe; color:#2563eb; }
.chip-READ { background:#e0e7ff; color:#4f46e5; }
.chip-FAILED { background:#fee2e2; color:#dc2626; }
.chip-PENDING { background:#fef3c7; color:#d97706; }
.chip-API_LIMIT_REACHED { background:#ede9fe; color:#7c3aed; }
.chip-INVALID_PHONE { background:#fce7f3; color:#db2777; }
.chip-PDF_FAILED { background:#f3f4f6; color:#4b5563; }
.chip-IN_PROGRESS { background:#dbeafe; color:#2563eb; }
.chip-COMPLETED { background:#dcfce7; color:#16a34a; }
.chip-PARTIAL { background:#fef3c7; color:#d97706; }
.chip-STOPPED { background:#fee2e2; color:#dc2626; }
.btn-retry { font-size:11px; padding:2px 10px; border:1px solid #8b5cf6; background:transparent; color:#8b5cf6; border-radius:4px; cursor:pointer; }
.btn-retry:hover { background:#8b5cf6; color:#fff; }
.wa-batch-info { padding:10px 15px; background:#f0f9ff; border-left:4px solid #3b82f6; border-radius:0 6px 6px 0; margin:10px 0; font-size:13px; }
.wa-loader { display:none; position:fixed; inset:0; background:rgba(255,255,255,.6); z-index:9999; align-items:center; justify-content:center; font-size:16px; font-weight:600; color:#16a34a; }
.wa-form .form-group label { font-size:12px; }
</style>

<div class="wa-loader" id="waLoader">&#8987; Sending WhatsApp bills, please wait...</div>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;">
    <h2>&#128172; Send WhatsApp Bill</h2>
    <a href="{{ route('society.whatsappBatchHistory') }}" class="btn btn-default btn-sm">&#128338; Batch History</a>
</div>

<div class="alert alert-info" style="margin-bottom:10px;">&#128241; WhatsApp is bheja jayega: <strong>{{ $senderLabel }}</strong></div>

<div class="card wa-form">
    <form id="Society_Member_WhatsApp_Bill" name="Society_Member_WhatsApp_Bill" onsubmit="return false;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            <div class="form-group" style="flex:1 1 220px;">
                <label class="control-label">Building</label>
                <select name="building_id" class="form-control" onchange="waLoadWings(this.value);">
                    <option value="">-- All Buildings --</option>
                    @foreach ($societyBuildingLists as $buildingID => $buildingName)
                        <option value="{{ $buildingID }}">{{ $buildingName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1 1 180px;">
                <label class="control-label">Wing Name</label>
                <select name="wing_id" id="wa_bill_wing_id" class="form-control"></select>
            </div>
            <div class="form-group" style="flex:1 1 180px;">
                <label class="control-label">Bill For</label>
                <select name="month" id="wa_month" class="form-control">
                    <option value="">-- All Months --</option>
                    @foreach ($months as $monthID => $monthName)
                        <option value="{{ $monthID }}">{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1 1 160px;">
                <label class="control-label">Flat No</label>
                <input type="text" class="form-control" id="wa_flat_no" name="flat_no">
            </div>
            <div class="form-group" style="flex:1 1 100px;">
                <label class="control-label">Floor No</label>
                <input type="text" class="form-control" id="wa_floor_no" name="floor_no">
            </div>
            <div class="form-group" style="flex:1 1 160px;">
                <label for="wa_bill_from" class="control-label">Bill No. From</label>
                <input type="text" class="form-control" id="wa_bill_from" name="bill_from">
            </div>
            <div class="form-group" style="flex:1 1 160px;">
                <label for="wa_bill_to" class="control-label">Bill No. To</label>
                <input type="text" class="form-control" id="wa_bill_to" name="bill_to">
            </div>
            <div class="form-group" style="flex:1 1 160px;">
                <label for="wa_receipt_from" class="control-label">Receipt From</label>
                <input type="date" class="form-control" id="wa_receipt_from" name="receipt_from">
            </div>
            <div class="form-group" style="flex:1 1 160px;">
                <label for="wa_receipt_to" class="control-label">Receipt To</label>
                <input type="date" class="form-control" id="wa_receipt_to" name="receipt_to">
            </div>
        </div>
        <div style="margin-top:8px;">
            @if (\App\Support\ResellerContext::can('tb_whatsappbill', 'generate'))
                <button type="button" class="btn btn-success btn-sm" id="btnSendBatch" onclick="startWhatsAppBatch();">&#128172; Send WhatsApp Bill</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnRetryAll" style="display:none;" onclick="retryFailedBills();">&#8635; Retry Failed</button>
            @else
                <button type="button" class="btn btn-success btn-sm" id="btnSendBatch" style="display:none;"></button>
                <button type="button" class="btn btn-warning btn-sm" id="btnRetryAll" style="display:none !important;"></button>
            @endif
            <a class="btn btn-success btn-sm" href="{{ route('society.dashboard') }}" title="Go to dashboard">Cancel</a>
        </div>
    </form>

    <div id="whatsapp_batch_result" style="margin-top:20px;"></div>
</div>
@endsection

@section('scripts')
<script>
var WA_URLS = {
    send: @json(route('society.whatsappSendBatch')),
    status: @json(route('society.whatsappBatchStatus')),
    retry: @json(route('society.whatsappRetryFailed')),
    wings: @json(route('society.getWings'))
};
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
function showLoader() { document.getElementById('waLoader').style.display = 'flex'; }
function hideLoader() { document.getElementById('waLoader').style.display = 'none'; }
function waLoadWings(buildingId) {
    var sel = document.getElementById('wa_bill_wing_id');
    sel.innerHTML = '';
    if (!buildingId) return;
    $.getJSON(WA_URLS.wings, { building_id: buildingId }, function (wings) {
        var html = '<option value="">Select Wing</option>';
        $.each(wings || {}, function (id, name) { html += '<option value="' + id + '">' + $('<div>').text(name).html() + '</option>'; });
        sel.innerHTML = html;
    });
}
function waEsc(v) { return String(v === null || v === undefined ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
var currentBatchId = null;
var pollTimer = null;

function startWhatsAppBatch() {
    if (!confirm('Send WhatsApp bills to all matching members? This will call the WhatsApp API for each bill.')) {
        return;
    }

    var btn = document.getElementById('btnSendBatch');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
    document.getElementById('btnRetryAll').style.display = 'none';
    showLoader();

    var formData = $('#Society_Member_WhatsApp_Bill').serialize();

    $.ajax({
        url: WA_URLS.send,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            hideLoader();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-whatsapp"></i> Send WhatsApp Bill';

            if (response.error) {
                $('#whatsapp_batch_result').html('<div class="alert alert-warning">' + response.error + '</div>');
                return;
            }

            currentBatchId = response.batch_id;
            renderBatchStatus(response);
            startPolling();
        },
        error: function(xhr) {
            hideLoader();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-whatsapp"></i> Send WhatsApp Bill';
            var msg = 'Error sending WhatsApp bills.';
            try {
                var resp = JSON.parse(xhr.responseText);
                if (resp.error) msg = resp.error;
            } catch(e) {
                if (xhr.responseText) msg = 'Server Error: ' + xhr.responseText.substring(0, 300);
            }
            $('#whatsapp_batch_result').html('<div class="alert alert-danger">' + msg + '</div>');
        }
    });
}

function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(function() {
        fetchBatchStatus(currentBatchId);
    }, 3000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function fetchBatchStatus(batchId) {
    $.ajax({
        url: WA_URLS.status + '?batch_id=' + encodeURIComponent(batchId),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error) {
                stopPolling();
                return;
            }
            renderBatchStatusFull(response);
            if (response.summary.status !== 'IN_PROGRESS') {
                stopPolling();
            }
        },
        error: function() {
            stopPolling();
        }
    });
}

function renderBatchStatus(response) {
    var s = response.summary;
    var html = buildCounterBar(s);
    html += buildProgressBar(s);
    html += '<div class="wa-batch-info">';
    html += '<strong>Batch:</strong> ' + response.batch_id;
    html += ' &middot; <strong>Status:</strong> <span class="chip chip-' + s.status + '">' + s.status + '</span>';
    if (s.stopped_at) {
        html += ' &middot; <strong>Stopped at:</strong> ' + waEsc(s.stopped_at);
    }
    html += '</div>';
    html += '<div id="wa_bills_table"></div>';
    $('#whatsapp_batch_result').html(html);

    if (s.status !== 'IN_PROGRESS' && s.status !== 'COMPLETED') {
        document.getElementById('btnRetryAll').style.display = 'inline-block';
    }

    fetchBatchStatus(response.batch_id);
}

function renderBatchStatusFull(response) {
    var s = response.summary;
    var html = buildCounterBar(s);
    html += buildProgressBar(s);
    html += '<div class="wa-batch-info">';
    html += '<strong>Batch:</strong> ' + response.batch_id;
    html += ' &middot; <strong>Status:</strong> <span class="chip chip-' + s.status + '">' + s.status + '</span>';
    if (s.stopped_at) {
        html += ' &middot; <strong>Stopped at:</strong> ' + waEsc(s.stopped_at);
    }
    if (s.created_at) {
        html += ' &middot; <strong>Started:</strong> ' + s.created_at;
    }
    if (s.completed_at) {
        html += ' &middot; <strong>Completed:</strong> ' + s.completed_at;
    }
    html += '</div>';

    if (response.bills && response.bills.length > 0) {
        html += buildBillsTable(response.bills);
    }

    $('#whatsapp_batch_result').html(html);

    var hasRetryable = false;
    if (response.bills) {
        for (var i = 0; i < response.bills.length; i++) {
            var st = response.bills[i].status;
            if (st === 'FAILED' || st === 'API_LIMIT_REACHED' || st === 'PENDING') {
                hasRetryable = true;
                break;
            }
        }
    }
    document.getElementById('btnRetryAll').style.display = (hasRetryable && s.status !== 'IN_PROGRESS') ? 'inline-block' : 'none';
}

function buildCounterBar(s) {
    var html = '<div class="wa-counter-bar">';
    html += '<div class="wa-counter c-total"><div class="num">' + s.total + '</div><div class="lbl">Total</div></div>';
    html += '<div class="wa-counter c-sent"><div class="num">' + s.sent + '</div><div class="lbl">Sent</div></div>';
    html += '<div class="wa-counter c-delivered"><div class="num">' + (s.delivered || 0) + '</div><div class="lbl">Delivered</div></div>';
    html += '<div class="wa-counter c-read"><div class="num">' + (s.read || 0) + '</div><div class="lbl">Read</div></div>';
    html += '<div class="wa-counter c-failed"><div class="num">' + s.failed + '</div><div class="lbl">Failed</div></div>';
    html += '<div class="wa-counter c-pending"><div class="num">' + s.pending + '</div><div class="lbl">Pending</div></div>';
    html += '<div class="wa-counter c-limit"><div class="num">' + s.api_limit + '</div><div class="lbl">API Limit</div></div>';
    html += '<div class="wa-counter c-invalid"><div class="num">' + s.invalid_phone + '</div><div class="lbl">Invalid Phone</div></div>';
    html += '<div class="wa-counter c-pdf"><div class="num">' + s.pdf_failed + '</div><div class="lbl">PDF Failed</div></div>';
    html += '</div>';
    return html;
}

function buildProgressBar(s) {
    var total = parseInt(s.total) || 1;
    var pRead = (parseInt(s.read || 0) / total * 100).toFixed(1);
    var pDelivered = (parseInt(s.delivered || 0) / total * 100).toFixed(1);
    var pSent = (parseInt(s.sent) / total * 100).toFixed(1);
    var pFailed = (parseInt(s.failed) / total * 100).toFixed(1);
    var pLimit = (parseInt(s.api_limit) / total * 100).toFixed(1);
    var pInvalid = (parseInt(s.invalid_phone) / total * 100).toFixed(1);
    var pPdf = (parseInt(s.pdf_failed) / total * 100).toFixed(1);
    var pPending = (parseInt(s.pending) / total * 100).toFixed(1);

    var html = '<div class="wa-progress-bar">';
    html += '<div class="seg-read" style="width:' + pRead + '%"></div>';
    html += '<div class="seg-delivered" style="width:' + pDelivered + '%"></div>';
    html += '<div class="seg-sent" style="width:' + pSent + '%"></div>';
    html += '<div class="seg-failed" style="width:' + pFailed + '%"></div>';
    html += '<div class="seg-limit" style="width:' + pLimit + '%"></div>';
    html += '<div class="seg-invalid" style="width:' + pInvalid + '%"></div>';
    html += '<div class="seg-pdf" style="width:' + pPdf + '%"></div>';
    html += '<div class="seg-pending" style="width:' + pPending + '%"></div>';
    html += '</div>';
    var totalSuccess = parseInt(s.sent) + parseInt(s.delivered || 0) + parseInt(s.read || 0);
    var pSuccess = (totalSuccess / total * 100).toFixed(1);
    html += '<p style="text-align:center;font-size:12px;color:#666;margin:0;">' + pSuccess + '% sent/delivered/read</p>';
    return html;
}

function buildBillsTable(bills) {
    var html = '<table class="table table-bordered table-striped" style="font-size:13px;margin-top:15px;">';
    html += '<thead><tr>';
    html += '<th>Sr</th><th>Bill No</th><th>Flat</th><th>Member Name</th><th>Phone</th><th>Amount</th>';
    html += '<th>Status</th><th>Sent At</th><th>Delivered</th><th>Read</th><th>Error</th><th>Retry</th><th>Action</th>';
    html += '</tr></thead><tbody>';

    for (var i = 0; i < bills.length; i++) {
        var b = bills[i];
        var retryable = (b.status === 'FAILED' || b.status === 'API_LIMIT_REACHED' || b.status === 'PENDING');
        html += '<tr>';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td>' + (b.bill_no || '-') + '</td>';
        html += '<td>' + (waEsc(b.flat_no) || '-') + '</td>';
        html += '<td>' + (waEsc(b.member_name) || '-') + '</td>';
        html += '<td>' + (waEsc(b.phone) || '-') + '</td>';
        html += '<td>Rs. ' + b.amount + '</td>';
        html += '<td><span class="chip chip-' + b.status + '">' + formatStatus(b.status) + '</span></td>';
        html += '<td>' + (b.sent_at || '—') + '</td>';
        html += '<td>' + (b.delivered_at || '—') + '</td>';
        html += '<td>' + (b.read_at || '—') + '</td>';
        // Show the whole error (wrapped), HTML-escaped - the provider's reason is what tells why a bill failed.
        var errText = String(b.error || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        html += '<td style="min-width:220px;max-width:360px;white-space:normal;word-break:break-word;font-size:11px;" title="' + errText + '">' + (errText || '—') + '</td>';
        html += '<td>' + (b.retry_count > 0 ? b.retry_count : '—') + '</td>';
        html += '<td style="white-space:nowrap;">';
        if (b.pdf_url) {
            html += '<a href="' + waEsc(b.pdf_url) + '" target="_blank" rel="noopener" class="btn btn-xs btn-default" title="Open the bill PDF that was sent">PDF</a> ';
        }
        if (b.api_response) {
            html += '<a href="#" class="btn btn-xs btn-default" title="' + String(b.api_response).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + '" onclick="alert(this.title); return false;">API</a> ';
        }
        if (retryable) {
            html += '<button class="btn-retry" onclick="retrySingleBill(\'' + currentBatchId + '\',' + b.id + ')"><i class="fa fa-refresh"></i></button>';
        }
        html += '</td>';
        html += '</tr>';
    }

    html += '</tbody></table>';
    return html;
}

function formatStatus(status) {
    var map = {
        'SENT': 'SENT',
        'DELIVERED': 'DELIVERED',
        'READ': 'READ',
        'FAILED': 'FAILED',
        'PENDING': 'PENDING',
        'API_LIMIT_REACHED': 'API LIMIT',
        'INVALID_PHONE': 'INVALID PHONE',
        'PDF_FAILED': 'PDF FAILED'
    };
    return map[status] || status;
}

function retryFailedBills() {
    if (!currentBatchId) {
        alert('No batch to retry.');
        return;
    }
    if (!confirm('Retry all failed, API limit, and pending bills?')) {
        return;
    }

    var btn = document.getElementById('btnRetryAll');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Retrying...';
    showLoader();

    $.ajax({
        url: WA_URLS.retry,
        type: 'POST',
        data: { batch_id: currentBatchId },
        dataType: 'json',
        success: function(response) {
            hideLoader();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-refresh"></i> Retry Failed';

            if (response.error) {
                alert(response.error);
                return;
            }

            renderBatchStatusFull(response);
            if (response.summary.status === 'IN_PROGRESS') {
                startPolling();
            }
        },
        error: function(xhr) {
            hideLoader();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-refresh"></i> Retry Failed';
            alert('Error retrying bills.');
        }
    });
}

function retrySingleBill(batchId, logId) {
    if (!confirm('Retry this bill?')) return;
    showLoader();
    $.ajax({
        url: WA_URLS.retry,
        type: 'POST',
        data: { batch_id: batchId, log_ids: [logId] },
        dataType: 'json',
        success: function(response) {
            hideLoader();
            if (response.error) {
                alert(response.error);
                return;
            }
            fetchBatchStatus(batchId);
        },
        error: function() {
            hideLoader();
            alert('Error retrying bill.');
        }
    });
}

$(document).ready(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var batchParam = urlParams.get('batch') || '';
    var statusUrl = WA_URLS.status;
    if (batchParam) {
        statusUrl += '?batch_id=' + encodeURIComponent(batchParam);
    }
    $.ajax({
        url: statusUrl,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.error) return;
            currentBatchId = response.batch_id;
            renderBatchStatusFull(response);
            if (response.summary.status === 'IN_PROGRESS') {
                startPolling();
            }
        }
    });
});
</script>
@endsection
