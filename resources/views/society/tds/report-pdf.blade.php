<html>
<head>
<style>
body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 4px; }
th { background: #eee; }
h3 { margin-bottom: 2px; }
.text-right { text-align: right; }
</style>
</head>
<body>
<h3>{{ $societyDetails->society_name ?? '' }}</h3>
<h4>TDS Report</h4>
<table>
    <thead>
    <tr><th>Sr</th><th>Deduction Date</th><th>Deductee</th><th>PAN</th><th>Invoice No</th><th>Section</th><th>Gross</th><th>Rate</th><th>TDS Amt</th><th>Net Amt</th><th>Status</th></tr>
    </thead>
    <tbody>
    @foreach($reportData as $i => $t)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $t->deduction_date }}</td>
            <td>{{ $t->contact_person_name }}</td>
            <td>{{ $t->pan_no }}</td>
            <td>{{ $t->invoice_no }}</td>
            <td>{{ $t->section_code }}</td>
            <td class="text-right">{{ number_format($t->gross_amount, 2) }}</td>
            <td class="text-right">{{ number_format($t->tds_rate, 2) }}</td>
            <td class="text-right">{{ number_format($t->tds_amount, 2) }}</td>
            <td class="text-right">{{ number_format($t->net_amount, 2) }}</td>
            <td>{{ $t->challan_status }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="6" class="text-right">Total</th>
        <th class="text-right">{{ number_format($totals->total_gross ?? 0, 2) }}</th>
        <th></th>
        <th class="text-right">{{ number_format($totals->total_tds ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_net ?? 0, 2) }}</th>
        <th></th>
    </tr>
    </tfoot>
</table>
</body>
</html>
