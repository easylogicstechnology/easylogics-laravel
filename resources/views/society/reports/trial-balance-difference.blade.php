@extends('layouts.app')
@section('title', $title ?? 'Trial Balance Difference')
@section('content')
@include('society.reports._styles')
<div class="page-header">
    <h2>{{ $mode === 'bank' ? 'Bank/Cash Difference' : 'Member Bill Difference' }}</h2>
    <div>
        @if($mode === 'bank')
            <a href="{{ route('society.reports.trialBalanceDifference') }}" class="btn btn-default btn-sm">Member Bill Difference</a>
        @else
            <a href="{{ route('society.reports.trialBalanceDifference', ['t' => 1]) }}" class="btn btn-default btn-sm">Bank/Cash Difference</a>
        @endif
        <a href="{{ route('society.reportAccounts') }}" class="btn btn-primary btn-sm">Back to Reports</a>
    </div>
</div>

@if($mode === 'bank')
    <div class="card ar-report">
        @include('society.reports._tb_difference_bank_body')
    </div>
@else
    <?php $postData = $post; ?>
    <div class="card">
        <form method="post" action="{{ route('society.reports.trialBalanceDifference') }}" class="ar-form" autocomplete="off">
            @csrf
            <div class="ar-row">
                <div class="ar-field"><label>From Date</label><input type="date" name="from_date" value="{{ $post['from_date'] ?? '' }}"></div>
                <div class="ar-field"><label>To Date</label><input type="date" name="to_date" value="{{ $post['to_date'] ?? '' }}"></div>
                <div class="ar-field">
                    <label>Member</label>
                    <select name="member_id" class="ar-wide">
                        <option value="">All Members</option>
                        @foreach($memberList as $id => $name)
                            <option value="{{ $id }}" {{ (isset($post['member_id']) && $post['member_id'] == $id) ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ar-field">
                    <label>Difference</label>
                    <select name="diff_filter">
                        <option value="">All</option>
                        <option value="diff" {{ ($post['diff_filter'] ?? '') == 'diff' ? 'selected' : '' }}>Only with Difference</option>
                        <option value="nodiff" {{ ($post['diff_filter'] ?? '') == 'nodiff' ? 'selected' : '' }}>No Difference</option>
                    </select>
                </div>
                <div class="ar-actions"><button type="submit" class="btn btn-success">Submit</button></div>
            </div>
        </form>
        <div class="ar-row" style="margin-top:8px">
            <button type="button" id="bulk_update_diff" class="btn btn-primary" disabled>Update Selected (fix difference)</button>
            <span id="bulk_update_count" style="margin-left:10px;color:#666;"></span>
            <span id="bulk_update_status" style="margin-left:10px;"></span>
        </div>
    </div>

    <div class="card">
        <div id="print_member_monthly_cotribution">
            <div class="print-member-monthly-cotribution ar-report">
                <table>
                    <thead>
                        <tr>
                            <th style="width:30px;"><input type="checkbox" id="diff_select_all" title="Select all rows with a difference"></th>
                            <th>Member</th><th>Bill No</th><th>Month</th><th>Bill Amount</th><th>Contribution</th><th>GST</th><th>Interest</th>
                            <th>DrAmount</th><th>CrAmount</th><th>Difference</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $totalDiffR = 0; $totalDiffY = 0; $totalCr = 0; $totalDr = 0; ?>
                    @foreach($members as $member)
                        <?php
                            $allBills = $bills[$member['id']] ?? [];
                            $shown = [];
                            foreach ($allBills as $b) {
                                $d = ($b['contri'] + $b['gst'] + $b['interest']) - $b['bill_amount'];
                                $isDiff = abs($d) >= 1;
                                if (isset($diffFilter) && $diffFilter == 'diff' && !$isDiff) { continue; }
                                if (isset($diffFilter) && $diffFilter == 'nodiff' && $isDiff) { continue; }
                                $shown[] = $b;
                            }
                        ?>
                        @continue(empty($shown))
                        <?php $i = 0; $totBill = $totContri = $totGst = $totInt = $totDiff = $totCrRow = 0; ?>
                        @foreach($shown as $bill)
                            <?php
                                $totBill += $bill['bill_amount'];
                                $totContri += $bill['contri'];
                                $totGst += $bill['gst'];
                                $totInt += $bill['interest'];
                                $cr = $bill['contri'] + $bill['gst'] + $bill['interest'];
                                $totCrRow += $cr;
                                $diff = $cr - $bill['bill_amount'];
                                $totDiff += $diff;
                                $totalDr += $bill['bill_amount'];
                                $totalCr += $cr;
                                $f = '';
                                // half a paisa: below this the Difference cell prints 0.00 anyway
                                if (abs($diff) >= 0.005 && abs($diff) < 1) { $f = 'background-color:yellow;'; $totalDiffY += $diff; }
                                elseif (abs($diff) >= 1) { $f = 'background-color:red;'; $totalDiffR += $diff; }
                                $monthCell = (isset($bill['bill_month']) && $bill['bill_month'] >= 1 && $bill['bill_month'] <= 12) ? date('F', mktime(0, 0, 0, (int) $bill['bill_month'], 10)) : ($bill['bill_month'] ?? '');
                            ?>
                            <tr>
                                <td class="text-center">@if(abs($diff) >= 1)<input type="checkbox" class="diff-row-check" value="{{ $member['id'] . ':' . $bill['financial_year_id'] . ':' . $bill['bill_type'] }}">@endif</td>
                                <td>{{ $i == 0 ? $member['member_name'] . ' - ' . $member['id'] . ' - ' . $member['flat_no'] : '' }}</td>
                                <td>{{ $bill['bill_no'] }}</td>
                                <td>{{ $monthCell }}</td>
                                <td>{{ number_format((float) $bill['bill_amount'], 2) }}</td>
                                <td>{{ number_format((float) $bill['contri'], 2) }}</td>
                                <td>{{ number_format((float) $bill['gst'], 2) }}</td>
                                <td>{{ number_format((float) $bill['interest'], 2) }}</td>
                                <td>{{ number_format((float) $bill['bill_amount'], 2) }}</td>
                                <td>{{ number_format($cr, 2) }}</td>
                                <td @if($f) style="{{ $f }}" @endif>{{ number_format($diff, 2) }}</td>
                            </tr>
                            <?php $i++; ?>
                        @endforeach
                        <tr>
                            <td colspan="4">Total</td>
                            <td>{{ number_format($totBill, 2) }}</td>
                            <td>{{ number_format($totContri, 2) }}</td>
                            <td>{{ number_format($totGst, 2) }}</td>
                            <td>{{ number_format($totInt, 2) }}</td>
                            <td>{{ number_format($totBill, 2) }}</td>
                            <td>{{ number_format($totCrRow, 2) }}</td>
                            <td>{{ number_format($totDiff, 2) }}</td>
                        </tr>
                    @endforeach
                        <tr>
                            <td colspan="8">Total</td>
                            <td>{{ number_format($totalDr, 2) }}</td>
                            <td>{{ number_format($totalCr, 2) }}</td>
                            <td>{{ 'Total Diff (<1):' . $totalDiffY . '  Total Diff(>=1)' . $totalDiffR }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
@if($mode !== 'bank')
<script>
(function () {
    var selectAll = document.getElementById('diff_select_all');
    var btn = document.getElementById('bulk_update_diff');
    var countEl = document.getElementById('bulk_update_count');
    var statusEl = document.getElementById('bulk_update_status');
    if (!btn) { return; }
    function checks() { return document.querySelectorAll('.diff-row-check'); }
    function checked() { return Array.prototype.filter.call(checks(), function (c) { return c.checked; }); }
    function refresh() {
        var n = checked().length;
        btn.disabled = (n === 0);
        countEl.textContent = n ? (n + ' selected') : '';
    }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            Array.prototype.forEach.call(checks(), function (c) { c.checked = selectAll.checked; });
            refresh();
        });
    }
    document.addEventListener('change', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('diff-row-check')) { refresh(); }
    });
    btn.addEventListener('click', function () {
        var rows = checked();
        if (!rows.length) { return; }
        // one recalculation per member + financial year + bill type
        var seen = {}, tuples = [];
        rows.forEach(function (c) { if (!seen[c.value]) { seen[c.value] = 1; tuples.push(c.value); } });
        if (!window.confirm('Recalculate ' + tuples.length + ' member bill(s) to fix the difference? This updates the bills.')) { return; }
        btn.disabled = true;
        // small batches, one request at a time: each member's recalculation is heavy
        var BATCH = 5;
        var url = @json(route('society.reports.bulkRecalculate'));
        var token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : @json(csrf_token());
        var totalDone = 0, totalFailed = 0, idx = 0;
        function sendBatch() {
            if (idx >= tuples.length) {
                statusEl.innerHTML = '<span style="color:#3c763d;">Done - ' + totalDone + ' recalculated' + (totalFailed ? (', ' + totalFailed + ' failed') : '') + '. Reloading...</span>';
                setTimeout(function () { location.reload(); }, 900);
                return;
            }
            var batch = tuples.slice(idx, idx + BATCH);
            statusEl.textContent = 'Updating ' + Math.min(idx + BATCH, tuples.length) + '/' + tuples.length + '...';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) { return; }
                var parsed = false;
                try {
                    var r = JSON.parse(xhr.responseText);
                    var m = /(\d+) recalculated(?:, (\d+) failed)?/.exec(r.message || '');
                    if (m) { totalDone += parseInt(m[1], 10); totalFailed += m[2] ? parseInt(m[2], 10) : 0; }
                    parsed = true;
                } catch (err) {}
                if (!parsed) {
                    statusEl.innerHTML = '<span style="color:#a94442;">Stopped at ' + idx + '/' + tuples.length + ' - a batch did not respond. ' + totalDone + ' updated so far; press again to continue.</span>';
                    btn.disabled = false;
                    return;
                }
                idx += BATCH;
                sendBatch();
            };
            xhr.send('rows=' + encodeURIComponent(batch.join(',')));
        }
        sendBatch();
    });
})();
</script>
@endif
@endsection
