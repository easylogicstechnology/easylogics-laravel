@extends('layouts.app')

@section('title', 'Debit Note / Credit Note - EasyLogics')

@php
    // One <option> list shared by every row: Members / Old Members / Ledgers (a plain grouped <select>).
    $accountOptions = '<option value="">Select account</option><optgroup label="Society Members List">';
    foreach ($memberList as $memberId => $memberName) {
        $flat = $flatNoList[$memberId] ?? '';
        $accountOptions .= '<option value="member-' . (int) $memberId . '">' . e($memberName . ' -- ' . $flat) . '</option>';
    }
    $accountOptions .= '</optgroup><optgroup label="Society Old Members List">';
    foreach ($oldMemberRows as $old) {
        $accountOptions .= '<option value="member-' . (int) $old->member_id . '-old">' . e($old->third_member . ' -- ' . $old->flat_no) . '</option>';
    }
    $accountOptions .= '</optgroup><optgroup label="Society Ledger List">';
    foreach ($ledgerList as $ledgerId => $ledgerName) {
        $accountOptions .= '<option value="ledger-' . (int) $ledgerId . '">' . e($ledgerName) . '</option>';
    }
    $accountOptions .= '</optgroup>';
@endphp

@section('content')
<style>
    .dcn-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: #5B6B85; margin-bottom: 5px; }
    .dcn-entry-card { background: #fff; border: 1px solid #E7EBF3; border-radius: 12px; padding: 18px 18px 8px; margin: 14px 0 18px; }
    .dcn-grid-header, .dcn-row { display: flex; gap: 8px; align-items: flex-start; width: 100%; }
    .dcn-grid-header { background: #EEF2F9; border-radius: 9px; padding: 10px 6px; margin: 0 0 10px; color: #2F4B7C; font-size: 12px; text-transform: uppercase; font-weight: 700; }
    .dcn-row { background: #FAFBFD; border: 1px solid #EEF1F7; border-radius: 10px; padding: 10px 6px; margin-bottom: 8px; }
    .dcn-c2 { flex: 0 0 15%; min-width: 0; } .dcn-c4 { flex: 0 0 34%; min-width: 0; } .dcn-x { flex: 0 0 auto; }
    .dcn-row select, .dcn-row input { width: 100%; }
    .dcn-bill-info { font-size: 12px; color: #395C94; margin-top: 4px; }
    .dcn-bill-info.dcn-warn { color: #a94442; }
    #dcn-totals { background: #fff; border: 1px solid #E7EBF3; border-radius: 10px; padding: 10px 8px; display: flex; gap: 8px; align-items: center; }
    .dcn-note-type label { margin-right: 22px; font-weight: 600; }
    .dcn-hint { font-size: 12px; color: #5B6B85; }
    .dcn-top { display: flex; gap: 20px; flex-wrap: wrap; }
    .dcn-top > div { flex: 1; min-width: 240px; }
</style>

<div class="page-header">
    <h2>Debit Note / Credit Note</h2>
</div>

<div class="card">
    @if (!$migrated)
        <div class="alert alert-error">The database has not been prepared for Debit Note / Credit Note yet
            (Config/Schema/debit_credit_notes_migration.sql). Please contact support.</div>
    @else
        <form method="post" action="{{ route('society.debitCreditNotes') }}" id="dcnForm" autocomplete="off">
            @csrf
            <div class="dcn-top">
                <div>
                    <div class="dcn-label">Note type</div>
                    <div class="dcn-note-type">
                        <label><input type="radio" name="DebitCreditNote[note_type]" value="DN" required> Debit Note</label>
                        <label><input type="radio" name="DebitCreditNote[note_type]" value="CN"> Credit Note</label>
                    </div>
                    <div class="dcn-hint" id="dcn-type-hint">Debit Note: the Member is debited (dues go up). Credit Note: the Member is credited (dues go down).</div>
                </div>
                <div>
                    <div class="dcn-label">Voucher Date</div>
                    <input type="date" class="form-control" name="DebitCreditNote[voucher_date]" min="{{ session('fy.year_start_date') }}" max="{{ session('fy.year_end_date') }}" value="{{ date('Y-m-d') }}" required>
                    <div class="dcn-hint">Not earlier than the member's latest bill date.</div>
                </div>
                <div>
                    <div class="dcn-label">Notes</div>
                    <textarea class="form-control" name="DebitCreditNote[note]" maxlength="480"></textarea>
                </div>
            </div>

            <div class="dcn-entry-card">
                <div class="dcn-grid-header">
                    <div class="dcn-c2">Type</div>
                    <div class="dcn-c4">Account Name</div>
                    <div class="dcn-c2">Adjust (member)</div>
                    <div class="dcn-c2">Dr. Amount</div>
                    <div class="dcn-c2">Cr. Amount</div>
                </div>
                <div id="dcn-rows"></div>
                <div id="dcn-totals" style="margin-top:12px;">
                    <div><button type="button" class="btn btn-success btn-sm" id="dcn-add-row">+ Add More</button></div>
                    <div style="flex:1; text-align:right; font-weight:bold;">Total</div>
                    <div class="dcn-c2" style="font-weight:bold;"><span id="dcn-total-debit">0.00</span></div>
                    <div class="dcn-c2" style="font-weight:bold;"><span id="dcn-total-credit">0.00</span></div>
                </div>
                <div style="padding:6px 0 4px;"><span id="dcn-balance-note" style="font-weight:600;font-size:13px;"></span></div>
            </div>

            @if ($payableOption)
                <div style="margin-bottom:10px;">
                    <label style="font-weight:600;"><input type="checkbox" name="DebitCreditNote[adjust_payable]" value="1">
                        Also adjust the bill's <b>Amount Payable</b></label>
                    <div class="dcn-hint">Left unticked (default), the note changes the bill's balance only and Amount Payable stays as it is.</div>
                </div>
            @endif

            <div class="dcn-hint" style="margin-bottom:12px;">
                At least one side of every note must be a Member. A note changes only the member's <b>latest bill</b>;
                older bills are never touched. <b>Auto</b>: a credit is settled against the latest bill's Tax, Interest and
                Principal in the society's settlement order (never more than the balance); a debit goes to Principal.
                <b>Tax / Interest / Principal</b> adjusts only that component of the latest bill.
            </div>
            <div>
                <button type="button" onclick="dcnSubmit()" class="btn btn-success">Submit</button>
                <a class="btn btn-success" href="{{ route('society.debitCreditNotes') }}">Cancel</a>
            </div>
        </form>

        <div class="table-responsive" style="margin-top:20px;">
            <table class="table table-hover table-bordered" id="datable_1">
                <thead>
                    <tr><th>#</th><th>Voucher No.</th><th>Type</th><th>Date</th><th>Account</th><th>Adjust</th><th>Dr. Amount</th><th>Cr. Amount</th><th>Notes</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @php $idCount = 1; @endphp
                    @foreach ($notes as $note)
                        @php $first = true; @endphp
                        @foreach ($note['lines'] as $line)
                            <tr>
                                <td>{{ $idCount }}</td>
                                <td>{{ $note['label'] }}</td>
                                <td>{{ $note['type'] }}</td>
                                <td>{{ $note['date'] }}</td>
                                <td>{{ $line['account'] }}</td>
                                <td>{{ $line['adjust'] }}</td>
                                <td>{{ $line['debit'] }}</td>
                                <td>{{ $line['credit'] }}</td>
                                <td>{{ $note['note'] }}</td>
                                <td style="white-space:nowrap;">
                                    @if ($first)
                                        @if ($note['can_delete'])
                                            <form method="post" action="{{ route('society.debitCreditNotes.delete', $note['voucher_no']) }}" style="display:inline;"
                                                  onsubmit="return confirm('Are you sure you want to delete # {{ $note['label'] }}?');">
                                                @csrf
                                                <button type="submit" title="Delete" style="background:none;border:0;cursor:pointer;color:#d9534f;font-size:16px;"><i class="fa fa-close"></i></button>
                                            </form>
                                        @else
                                            <span class="fa fa-lock text-muted" title="{{ $note['blocked_reason'] }}"></span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @php $first = false; $idCount++; @endphp
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@if ($migrated)
@section('scripts')
<script type="text/template" id="dcn-row-tpl">
<div class="dcn-row">
    <div class="dcn-c2">
        <select class="form-control dcn-type" name="DebitCreditNote[type][]"><option value="Debit">Debit</option><option value="Credit">Credit</option></select>
    </div>
    <div class="dcn-c4">
        <select class="form-control dcn-account" name="DebitCreditNote[account][]">{!! $accountOptions !!}</select>
        <div class="dcn-bill-info"></div>
    </div>
    <div class="dcn-c2">
        <select class="form-control dcn-adjust" name="DebitCreditNote[adjust][]" style="display:none">
            <option value="AUTO">Auto</option><option value="TAX">Tax</option><option value="INTEREST">Interest</option><option value="PRINCIPAL">Principal</option>
        </select>
    </div>
    <div class="dcn-c2"><input type="text" class="dcn-debit form-control" name="DebitCreditNote[debit][]" placeholder="0.00" value="0"></div>
    <div class="dcn-c2"><input type="text" class="dcn-credit form-control" name="DebitCreditNote[credit][]" placeholder="0.00" value="0"></div>
    <div class="dcn-x"><button class="btn btn-danger btn-sm dcn-remove" type="button" title="Remove row">&times;</button></div>
</div>
</script>
<script>
(function ($) {
    var latestBillUrl = @json(url('/society/debit-credit-notes/latest-bill'));
    var billCache = {};
    function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
    function addRow() {
        var $row = $($('#dcn-row-tpl').html());
        $('#dcn-rows').append($row);
        syncRow($row);
        $row.find('.dcn-remove').toggle($('#dcn-rows .dcn-row').length > 2);
        return $row;
    }
    // Debit rows take a debit amount, credit rows a credit amount - as on the Journal Voucher screen.
    function syncRow($row) {
        var isDebit = $row.find('.dcn-type').val() === 'Debit';
        $row.find('.dcn-debit').prop('readonly', !isDebit);
        $row.find('.dcn-credit').prop('readonly', isDebit);
        if (isDebit) { $row.find('.dcn-credit').val(0); } else { $row.find('.dcn-debit').val(0); }
        updateTotals();
        refreshInfo($row);
    }
    function isCurrentMember(value) { return /^member-\d+$/.test(value || ''); }
    function refreshInfo($row) {
        var acct = $row.find('.dcn-account').val();
        var $adj = $row.find('.dcn-adjust');
        var $info = $row.find('.dcn-bill-info');
        if (!isCurrentMember(acct)) {
            $adj.hide().val('AUTO');
            $info.text('').removeClass('dcn-warn');
            return;
        }
        $adj.show();
        if ($adj.val() === 'AUTO') { $info.text('').removeClass('dcn-warn'); return; }
        var show = function (b) {
            if (!b || !b.found) { $info.text('This member has no generated bill - only Auto can be used.').addClass('dcn-warn'); return; }
            $info.removeClass('dcn-warn').text('Latest bill #' + b.bill_no + ' (' + b.generated + '): Tax ' + b.tax.toFixed(2) +
                ' | Interest ' + b.interest.toFixed(2) + ' | Principal ' + b.principal.toFixed(2));
            $row.data('bill', b);
        };
        if (billCache[acct]) { show(billCache[acct]); return; }
        $info.text('Looking up the latest bill...');
        $.getJSON(latestBillUrl + '/' + acct, function (b) { billCache[acct] = b; show(b); });
    }
    function updateTotals() {
        var d = 0, c = 0;
        $('#dcn-rows .dcn-debit').each(function () { d += num($(this).val()); });
        $('#dcn-rows .dcn-credit').each(function () { c += num($(this).val()); });
        $('#dcn-total-debit').text(d.toFixed(2));
        $('#dcn-total-credit').text(c.toFixed(2));
        var diff = Math.round((d - c) * 100) / 100, $n = $('#dcn-balance-note');
        if (d === 0 && c === 0) { $n.text('').css('color', ''); }
        else if (diff === 0) { $n.text('Debit and credit match.').css('color', '#3c763d'); }
        else { $n.text('Out of balance by ' + Math.abs(diff).toFixed(2) + ' - ' + (diff > 0 ? 'credit' : 'debit') + ' is short.').css('color', '#a94442'); }
    }
    // Convenience only: pre-set the two starting rows for the chosen note type.
    function presetForType(type) {
        var $rows = $('#dcn-rows .dcn-row');
        if ($rows.length !== 2) { return; }
        var untouched = true;
        $rows.each(function () { if ($(this).find('.dcn-account').val() || num($(this).find('.dcn-debit').val()) || num($(this).find('.dcn-credit').val())) { untouched = false; } });
        if (!untouched) { return; }
        var first = type === 'DN' ? 'Debit' : 'Credit', second = type === 'DN' ? 'Credit' : 'Debit';
        $rows.eq(0).find('.dcn-type').val(first); $rows.eq(1).find('.dcn-type').val(second);
        $rows.each(function () { syncRow($(this)); });
    }
    $(document).on('change', '.dcn-type', function () { syncRow($(this).closest('.dcn-row')); });
    $(document).on('change', '.dcn-account, .dcn-adjust', function () { refreshInfo($(this).closest('.dcn-row')); });
    $(document).on('input change keyup', '.dcn-debit, .dcn-credit', updateTotals);
    $(document).on('click', '.dcn-remove', function () {
        $(this).closest('.dcn-row').remove();
        $('#dcn-rows .dcn-row').first().length && $('#dcn-rows .dcn-remove').toggle($('#dcn-rows .dcn-row').length > 2);
        updateTotals();
    });
    $(document).on('click', '#dcn-add-row', function () { addRow(); $('#dcn-rows .dcn-remove').toggle(true); });
    $(document).on('change', 'input[name="DebitCreditNote[note_type]"]', function () { presetForType($(this).val()); });
    window.dcnSubmit = function () {
        var d = 0, c = 0;
        $('#dcn-rows .dcn-debit').each(function () { d += num($(this).val()); });
        $('#dcn-rows .dcn-credit').each(function () { c += num($(this).val()); });
        if (!$('input[name="DebitCreditNote[note_type]"]:checked').length) { alert('Please choose Debit Note or Credit Note.'); return false; }
        if (d === 0 || Math.round((d - c) * 100) !== 0) { alert('Please check the debit and credit amounts - they must be equal.'); return false; }
        $('#dcnForm').submit();
    };
    $(document).ready(function () {
        addRow(); addRow();
        $('#dcn-rows .dcn-remove').hide();
    });
})(jQuery);
</script>
@endsection
@endif
