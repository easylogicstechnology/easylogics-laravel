<html>
<head>
<style>
body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 4px; }
th { background: #eee; }
.text-right { text-align: right; }
</style>
</head>
<body>
<h3>{{ $societyDetails->society_name ?? '' }}</h3>
<h4>GST Outward Supply Register</h4>
<table>
    <thead>
    <tr><th>Sr</th><th>Bill No</th><th>Bill Date</th><th>Member</th><th>GSTIN</th><th>HSN/SAC</th><th>Taxable Value</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total GST</th><th>Invoice Total</th></tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $m)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $m->bill_no }}</td>
            <td>{{ $m->bill_generated_date }}</td>
            <td>{{ $m->member_name }}</td>
            <td>{{ $m->GSTIN ?? '' }}</td>
            <td>{{ $m->hsn_code ?? '' }}</td>
            <td class="text-right">{{ number_format($m->computed['taxable_value'], 2) }}</td>
            <td class="text-right">{{ number_format($m->cgst_total, 2) }}</td>
            <td class="text-right">{{ number_format($m->sgst_total, 2) }}</td>
            <td class="text-right">{{ number_format($m->igst_total, 2) }}</td>
            <td class="text-right">{{ number_format($m->tax_total, 2) }}</td>
            <td class="text-right">{{ number_format($m->monthly_bill_amount, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="6" class="text-right">Total</th>
        <th class="text-right"></th>
        <th class="text-right">{{ number_format($totals->total_cgst ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_sgst ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_igst ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_gst ?? 0, 2) }}</th>
        <th></th>
    </tr>
    </tfoot>
</table>
</body>
</html>
