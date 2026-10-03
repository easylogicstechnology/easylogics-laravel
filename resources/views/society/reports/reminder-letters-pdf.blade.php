<html>
<head>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #111; }
    .letter { page-break-after: always; padding: 20px 10px; }
    .letter:last-child { page-break-after: auto; }
    .center { text-align: center; }
    .right { text-align: right; }
    .society-name { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
    .society-line { font-size: 10.5px; margin-bottom: 1px; }
    .title { font-size: 13px; font-weight: bold; text-decoration: underline; margin: 22px 0 20px; }
    .addr-row { width: 100%; }
    .addr-row td { vertical-align: top; }
    .to-block { line-height: 1.5; }
    .sub-line { text-align: center; font-weight: bold; margin: 22px 0 16px; }
    p { line-height: 1.7; margin: 0 0 12px; text-align: justify; }
    .signature { margin-top: 46px; }
</style>
</head>
<body>
@foreach($reminderLetters as $letter)
<div class="letter">
    <div class="center society-name">{{ $society->society_name ?? '' }}</div>
    @if(!empty($society->registration_no))
    <div class="center society-line">{{ $society->registration_no }}</div>
    @endif
    @if(!empty($society->address))
    <div class="center society-line">{{ $society->address }}{{ !empty($society->telephone_no) ? ', TEL.NO.' . $society->telephone_no : '' }}</div>
    @endif
    <div class="center title">REMINDER LETTER</div>
    <table class="addr-row">
        <tr>
            <td style="width:65%;">
                To,
                <div class="to-block">
                    <b>{{ $letter['member_name'] }}</b><br>
                    Unit No. {{ $letter['flat_no'] }}{{ !empty($letter['wing_name']) ? ', ' . $letter['wing_name'] : '' }}<br>
                    {{ $letter['building_name'] }}
                </div>
            </td>
            <td class="right" style="width:35%;">Date: {{ date('d/m/Y') }}</td>
        </tr>
    </table>
    <div class="sub-line">Sub.: Outstanding Dues</div>
    <p>Dear Sir/Madam,</p>
    <p>We find from the records that the aforesaid dues are outstanding, Rs. {{ number_format($letter['due_amount'], 2) }}/- on your name as on {{ date('d/m/Y', strtotime($asOnDate)) }}.</p>
    <p>You are requested to kindly clear the said dues {{ !empty($clearDuesByDate) ? 'on or before ' . date('d/m/Y', strtotime($clearDuesByDate)) : 'at the earliest' }} in the interest of the smooth running of the society's affairs.</p>
    <p>If you have already paid the aforesaid amount then kindly ignore this letter/mail.</p>
    <p>Your co-operation in this matter will be highly appreciated.</p>
    <div class="signature">
        For {{ isset($society->society_name) ? strtoupper($society->society_name) : '' }}
        <br><br><br>
        _______________________<br>
        Authorized Signatory
    </div>
</div>
@endforeach
@if(empty($reminderLetters))
<div class="letter center">No members with outstanding dues found for the selected filters.</div>
@endif
</body>
</html>
