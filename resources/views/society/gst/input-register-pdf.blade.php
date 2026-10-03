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
<h4>GST Input / Purchase Register</h4>
<table>
    <thead>
    <tr><th>Sr</th><th>Supplier</th><th>GSTIN</th><th>Invoice No</th><th>Invoice Date</th><th>HSN/SAC</th><th>Taxable Amt</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Total</th></tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $v)
        @php $totalInvoice = $v->amount + $v->sgst_amount + $v->cgst_amount + $v->igst_amount; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $v->title ?? '' }}</td>
            <td>{{ $v->gst_no ?? '' }}</td>
            <td>{{ $v->bill_no }}</td>
            <td>{{ $v->bill_date }}</td>
            <td>{{ $v->hsn_sac }}</td>
            <td class="text-right">{{ number_format($v->amount, 2) }}</td>
            <td class="text-right">{{ number_format($v->cgst_amount, 2) }}</td>
            <td class="text-right">{{ number_format($v->sgst_amount, 2) }}</td>
            <td class="text-right">{{ number_format($v->igst_amount, 2) }}</td>
            <td class="text-right">{{ number_format($totalInvoice, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="6" class="text-right">Total</th>
        <th class="text-right">{{ number_format($totals->total_taxable ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_cgst ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_sgst ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($totals->total_igst ?? 0, 2) }}</th>
        <th></th>
    </tr>
    </tfoot>
</table>
</body>
</html>
