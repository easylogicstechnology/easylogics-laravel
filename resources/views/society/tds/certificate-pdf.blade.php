<html>
<head>
<style>
body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { border: 1px solid #333; padding: 5px; }
th { background: #eee; }
.text-right { text-align: right; }
.header-box { border: 1px solid #333; padding: 8px; margin-bottom: 10px; }
</style>
</head>
<body>
<h3 style="text-align:center;">TDS Deduction Statement</h3>
<div class="header-box">
    <b>Deductor (Society):</b> {{ $society->society_name ?? '' }}<br>
    <b>TAN:</b> {{ $society->tan_no ?? '' }} &nbsp;
    <b>PAN:</b> {{ $society->pan_no ?? '' }}<br>
    <b>Financial Year:</b> {{ $financialYear->year ?? '' }}
</div>
<div class="header-box">
    <b>Deductee:</b> {{ $vendor->contact_person_name ?? '' }}<br>
    <b>PAN:</b> {{ $vendor->pan_no ?? '' }}
</div>
<table>
    <thead>
    <tr><th>Sr</th><th>Deduction Date</th><th>Section</th><th>Gross Amount</th><th>TDS Rate</th><th>TDS Amount</th></tr>
    </thead>
    <tbody>
    @foreach($txns as $i => $t)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $t->deduction_date }}</td>
            <td>{{ $t->section_code }}</td>
            <td class="text-right">{{ number_format($t->gross_amount, 2) }}</td>
            <td class="text-right">{{ number_format($t->tds_rate, 2) }}</td>
            <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr><th colspan="5" class="text-right">Total TDS</th><th class="text-right">{{ number_format($totalTds, 2) }}</th></tr>
    </tfoot>
</table>
<p style="margin-top:20px;">This is a system-generated TDS deduction statement. Government-issued TDS certificate (Form 16A) must be downloaded from the TRACES portal.</p>
</body>
</html>
