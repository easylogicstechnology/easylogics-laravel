@extends('layouts.app')
@section('title', 'Reminder Letters Preview')
@section('content')
@include('society.reports._styles')
<?php $mp = $input ?? []; ?>
<div class="page-header">
    <h2>Reminder Letters Preview</h2>
    <a href="{{ route('society.reports.duesFromMembers') }}" class="btn btn-primary btn-sm">&laquo; Back to Filters</a>
</div>

@if(session('reminder_flash'))
    <div class="card"><div class="ar-note" style="font-size:14px;color:#222">{{ session('reminder_flash') }}</div></div>
@endif
@if(!empty($flash))
    <div class="card"><div class="ar-note" style="font-size:14px;color:#222">{{ $flash }}</div></div>
@endif

<div class="card">
    <form method="post" action="{{ route('society.reports.duesFromMembers') }}" id="reminderLettersActionForm">
        @csrf
        @foreach(['payment_date', 'operator', 'amount', 'member_record', 'building_id', 'wing_id', 'flat_no', 'flat_no_to', 'clear_dues_by_date'] as $f)
            <input type="hidden" name="{{ $f }}" value="{{ $mp[$f] ?? '' }}">
        @endforeach
        <input type="hidden" name="report_type" value="Reminder Letter">
        <div style="margin-bottom:18px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <button type="submit" name="letter_action" value="view_pdf" formtarget="_blank" class="btn btn-info">PDF View</button>
            <button type="submit" name="letter_action" value="download_pdf" class="btn btn-success">Download PDF (All)</button>
            <button type="submit" name="letter_action" value="email_all" class="btn btn-primary" onclick="return confirm('Send reminder emails to all {{ count($reminderLetters) }} member(s) with an email on file?');">Email All</button>
            <span class="ar-note">Found {{ count($reminderLetters) }} member(s) with outstanding dues as on {{ date('d/m/Y', strtotime($asOnDate)) }}.</span>
        </div>
    </form>

    @if(empty($reminderLetters))
        <div class="ar-note" style="font-size:14px">No members with outstanding dues found for the selected filters.</div>
    @endif

    @foreach($reminderLetters as $letter)
        <?php
            $phoneDigits = preg_replace('/[^0-9]/', '', $letter['phone']);
            $waAvailable = strlen($phoneDigits) >= 10;
            if ($waAvailable && strlen($phoneDigits) == 10) {
                $phoneDigits = '91' . $phoneDigits;
            }
            $waText = "REMINDER LETTER\n\n"
                . "To,\n" . $letter['member_name'] . "\n"
                . "Unit No. " . $letter['flat_no'] . (!empty($letter['wing_name']) ? ', ' . $letter['wing_name'] : '') . "\n"
                . $letter['building_name'] . "\n\n"
                . "Sub.: Outstanding Dues\n\n"
                . "Dear Sir/Madam,\n\n"
                . "We find from the records that the aforesaid dues are outstanding, Rs. " . number_format($letter['due_amount'], 2) . "/- on your name as on " . date('d/m/Y', strtotime($asOnDate)) . ".\n\n"
                . "You are requested to kindly clear the said dues " . (!empty($clearDuesByDate) ? 'on or before ' . date('d/m/Y', strtotime($clearDuesByDate)) : 'at the earliest') . " in the interest of the smooth running of the society's affairs.\n\n"
                . "If you have already paid the aforesaid amount then kindly ignore this letter/mail.\n\n"
                . "Your co-operation in this matter will be highly appreciated.\n\n"
                . "For " . (isset($society->society_name) ? strtoupper($society->society_name) : '');
            $waLink = 'https://wa.me/' . $phoneDigits . '?text=' . urlencode($waText);
        ?>
        <div style="border:1px solid #ddd;padding:12px;margin-bottom:16px;border-radius:4px">
            <div style="text-align:right">
                @if($waAvailable)
                    <a href="{{ $waLink }}" target="_blank" class="btn btn-success btn-sm">Send via WhatsApp</a>
                @else
                    <span class="ar-note">WhatsApp unavailable</span>
                @endif
                @if(!empty($letter['email']))
                    <span class="ar-note" style="margin-left:6px">{{ $letter['email'] }}</span>
                @else
                    <span class="ar-note" style="margin-left:6px">No email on file</span>
                @endif
            </div>
            <hr>
            <p><b>{{ $letter['member_name'] }}</b><br>
            Unit No. {{ $letter['flat_no'] }}{{ !empty($letter['wing_name']) ? ', ' . $letter['wing_name'] : '' }}<br>
            {{ $letter['building_name'] }}</p>
            <p>Dues outstanding: <b>Rs. {{ number_format($letter['due_amount'], 2) }}/-</b> as on {{ date('d/m/Y', strtotime($asOnDate)) }}</p>
            <p class="ar-note">Clear by: {{ !empty($clearDuesByDate) ? date('d/m/Y', strtotime($clearDuesByDate)) : 'at the earliest' }}</p>
        </div>
    @endforeach
</div>
@endsection
