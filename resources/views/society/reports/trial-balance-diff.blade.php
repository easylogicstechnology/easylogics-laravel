@extends('layouts.app')
@section('title', $title ?? 'Trial Balance Diff')
@section('content')
@include('society.reports._styles')
<?php
    $isMismatch = abs($diff) >= 0.01;
    $mismatchStyle = 'color:#d9534f;background-color:#ffdddd;';
    $fromDate = !empty($post['payment_date']) ? $post['payment_date'] : '';
    $toDate = !empty($post['payment_date_to']) ? $post['payment_date_to'] : '';
    $baseUrl = route('society.reports.trialBalanceDiff');
    $ajaxUrl = route('society.reports.trialBalanceDiffRange');
    $dayUrl = route('society.reports.trialBalanceDay');
?>
<div class="page-header">
    <h2>Trial Balance Diff (Debit vs Credit)</h2>
    <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
</div>

<div class="card ar-report">
    <p style="color:#666;">
        Current financial year{{ ($fromDate && $toDate) ? ' (' . date('d/m/Y', strtotime($fromDate)) . ' - ' . date('d/m/Y', strtotime($toDate)) . ')' : '' }}
        ka Debit aur Credit total. Poora breakdown dekhne ke liye <b>Trial Balance</b> button use karein.
    </p>
    <table style="max-width:600px;">
        <thead><tr><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Diff (Dr - Cr)</th></tr></thead>
        <tbody>
            <tr @if($isMismatch) style="{{ $mismatchStyle }}" @endif>
                <td class="text-right">{{ number_format($debit, 2) }}</td>
                <td class="text-right">{{ number_format($credit, 2) }}</td>
                <td class="text-right" style="font-weight:bold;">{{ number_format($diff, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @if($isMismatch)
        <div style="margin-top:10px;padding:10px 14px;border:1px solid #f0c0c0;background:#fff6f6;color:#a94442;font-size:13px;">
            Debit aur Credit match nahi kar rahe (Diff: {{ number_format($diff, 2) }}).
        </div>
    @else
        <div style="margin-top:10px;padding:10px 14px;border:1px solid #c3e6cb;background:#f2fff4;color:#1a7a35;font-size:13px;">
            Debit aur Credit match kar rahe hain is period ke liye.
        </div>
    @endif

    @if($isMismatch && !empty($dateRows))
        <h6 style="margin-top:25px;font-weight:600;">
            {{ $dateGranularity === 'day' ? ('Day-Wise Debit vs Credit - ' . date('F Y', strtotime($drillMonth . '-01'))) : 'Month-Wise Debit vs Credit' }}
        </h6>
        <p style="color:#666;font-size:13px;">
            @if($dateGranularity === 'day')
                Har din ka Dr/Cr - jis din Status <span style="color:#d9534f;">&#10008;</span> hai, usi din ki entry (Payment Entry / Journal Voucher / Receipt) check karein.
                <a href="{{ $baseUrl }}">&laquo; Month-wise par wapas jayein</a>
            @else
                Jis month ka Status <span style="color:#d9534f;">&#10008;</span> hai, usmein click karke din-wise dekh sakte hain.
            @endif
            <span id="tbProgress" style="margin-left:10px;color:#999;"></span>
        </p>
        <table style="max-width:600px;" id="tbDateTable">
            <thead>
                <tr><th>{{ $dateGranularity === 'day' ? 'Date' : 'Month' }}</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-center">Status</th></tr>
            </thead>
            <tbody>
                @foreach($dateRows as $i => $row)
                    <tr id="tbRow{{ $i }}" data-from="{{ $row['from'] }}" data-to="{{ $row['to'] }}" data-key="{{ $row['key'] }}" data-label="{{ $row['label'] }}">
                        <td class="tb-label">{{ $row['label'] }}</td>
                        <td class="text-right tb-debit">...</td>
                        <td class="text-right tb-credit">...</td>
                        <td class="text-center tb-status">&hellip;</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <script>
        (function () {
            var rows = document.querySelectorAll('#tbDateTable tbody tr');
            var isMonthMode = {{ $dateGranularity === 'month' ? 'true' : 'false' }};
            var baseUrl = @json($baseUrl);
            var ajaxUrl = @json($ajaxUrl);
            var dayUrl = @json($dayUrl);
            var idx = 0;
            var progress = document.getElementById('tbProgress');
            function updateProgress() { progress.textContent = '(' + idx + ' / ' + rows.length + ' computed)'; }
            function processNext() {
                if (idx >= rows.length) { progress.textContent = 'Poora ho gaya.'; return; }
                var row = rows[idx];
                updateProgress();
                var url = ajaxUrl + '?from=' + encodeURIComponent(row.getAttribute('data-from')) + '&to=' + encodeURIComponent(row.getAttribute('data-to'));
                fetch(url, { credentials: 'same-origin', headers: {'Accept': 'application/json'} })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var mismatch = Math.abs(data.diff) >= 0.01;
                        row.querySelector('.tb-debit').textContent = Number(data.debit).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        row.querySelector('.tb-credit').textContent = Number(data.credit).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        var statusCell = row.querySelector('.tb-status');
                        if (data.error) {
                            statusCell.innerHTML = '<span style="color:#999;">?</span>';
                        } else if (mismatch) {
                            statusCell.innerHTML = '<span style="color:#d9534f;font-weight:bold;">&#10008;</span>';
                            row.style.color = '#d9534f';
                            row.style.backgroundColor = '#ffdddd';
                            var labelCell = row.querySelector('.tb-label');
                            var a = document.createElement('a');
                            if (isMonthMode) {
                                a.href = baseUrl + '?month=' + row.getAttribute('data-key');
                            } else {
                                a.href = dayUrl + '?date=' + row.getAttribute('data-key');
                                a.target = '_blank';
                                a.title = 'Is din ki transactions dekhein aur edit/delete karein';
                            }
                            a.style.color = '#a94442';
                            a.style.textDecoration = 'underline';
                            a.textContent = row.getAttribute('data-label');
                            labelCell.innerHTML = '';
                            labelCell.appendChild(a);
                        } else {
                            statusCell.innerHTML = '<span style="color:#1a7a35;font-weight:bold;">&#10004;</span>';
                        }
                    })
                    .catch(function () { row.querySelector('.tb-status').innerHTML = '<span style="color:#999;">?</span>'; })
                    .finally(function () { idx++; processNext(); });
            }
            processNext();
        })();
        </script>
    @endif

    <h6 style="margin-top:25px;font-weight:600;">Record-Wise Debit vs Credit</h6>
    <table style="max-width:700px;margin-top:10px;">
        <thead><tr><th>Particulars</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
        <tbody>
            @foreach($heads as $subCatId => $group)
                @continue(empty($group['ledgers']))
                <tr>
                    <td colspan="3" style="font-weight:bold;background:#f5f5f5;">
                        {{ isset($subCats[$subCatId]['title']) ? $subCats[$subCatId]['title'] : ($subCatId == 7 ? 'Advances & Dues From Members' : 'Group ' . $subCatId) }}
                    </td>
                </tr>
                @foreach($group['ledgers'] as $ledger)
                    <tr>
                        <td>{{ $ledger['details']['title'] }}</td>
                        <td class="text-right">{{ number_format((float) $ledger['transactions']['debit'], 2) }}</td>
                        <td class="text-right">{{ number_format((float) $ledger['transactions']['credit'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;{{ $isMismatch ? $mismatchStyle : '' }}">
                <td>Grand Total</td>
                <td class="text-right">{{ number_format($debit, 2) }}</td>
                <td class="text-right">{{ number_format($credit, 2) }}</td>
            </tr>
            @if($isMismatch)
                <tr>
                    <td style="font-weight:bold;color:#d9534f;">Diff (Dr - Cr)</td>
                    <td colspan="2" class="text-center" style="font-weight:bold;{{ $mismatchStyle }}">{{ number_format($diff, 2) }}</td>
                </tr>
            @endif
        </tfoot>
    </table>
</div>
@endsection
