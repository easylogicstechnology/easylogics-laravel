<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Bill #{{ $header['bill_no'] ?? $header['id'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; padding: 20px; }
        .print-header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .print-header h2 { font-size: 18px; margin-bottom: 2px; }
        .print-header h4 { font-size: 14px; font-weight: normal; color: #555; }
        .bill-info { margin-bottom: 15px; }
        .bill-info table { width: 100%; }
        .bill-info td { padding: 3px 5px; vertical-align: top; }
        .bill-info .label { font-weight: bold; width: 120px; }
        table.bill-lines { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.bill-lines th, table.bill-lines td { border: 1px solid #999; padding: 5px 8px; text-align: left; }
        table.bill-lines th { background: #f0f0f0; font-weight: bold; font-size: 12px; }
        table.bill-lines td.amount { text-align: right; }
        table.bill-lines tfoot td { font-weight: bold; border-top: 2px solid #333; }
        .totals-section { margin-top: 10px; }
        .totals-section table { width: 350px; float: right; }
        .totals-section td { padding: 3px 8px; }
        .totals-section td.amount { text-align: right; font-weight: bold; }
        .totals-section tr.grand-total td { border-top: 2px solid #333; font-size: 15px; }
        .clearfix::after { content: ''; display: table; clear: both; }
        .print-footer { margin-top: 40px; text-align: center; font-size: 11px; color: #777; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:15px; text-align:right;">
        <button onclick="window.print();" style="padding:8px 20px; font-size:14px; cursor:pointer; background:#4CAF50; color:#fff; border:none; border-radius:4px;">Print</button>
        <button onclick="window.close();" style="padding:8px 20px; font-size:14px; cursor:pointer; background:#f44336; color:#fff; border:none; border-radius:4px; margin-left:5px;">Close</button>
    </div>
    <div class="print-header">
        <h2>{{ $societyName }}</h2>
        <h4>Vendor Bill</h4>
    </div>
    <div class="bill-info">
        <table>
            <tr>
                <td class="label">Vendor:</td>
                <td>{{ $vendorName }}</td>
                <td class="label">Bill Type:</td>
                <td>{{ $header['bill_type'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Bill No:</td>
                <td>{{ $header['bill_no'] ?? '' }}</td>
                <td class="label">PO No:</td>
                <td>{{ $header['po_no'] ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Bill Date:</td>
                <td>{{ !empty($header['bill_date']) ? date('d/m/Y', strtotime($header['bill_date'])) : '' }}</td>
                <td class="label">Due Date:</td>
                <td>{{ !empty($header['due_date']) ? date('d/m/Y', strtotime($header['due_date'])) : '' }}</td>
            </tr>
            @if (!empty($header['title']))
            <tr>
                <td class="label">Title:</td>
                <td colspan="3">{{ $header['title'] }}</td>
            </tr>
            @endif
            @if (!empty($header['remarks']))
            <tr>
                <td class="label">Remarks:</td>
                <td colspan="3">{{ $header['remarks'] }}</td>
            </tr>
            @endif
        </table>
    </div>
    @php
        $totalAmount = $totalSgst = $totalCgst = $totalIgst = 0;
    @endphp
    <table class="bill-lines">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Bill Particulars</th>
                <th style="width:90px;">Amount</th>
                <th style="width:55px;">SGST %</th>
                <th style="width:80px;">SGST Amt</th>
                <th style="width:55px;">CGST %</th>
                <th style="width:80px;">CGST Amt</th>
                <th style="width:55px;">IGST %</th>
                <th style="width:80px;">IGST Amt</th>
                <th>HSN/SAC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $i => $line)
                @php
                    $amount = (float) ($line->amount ?? 0);
                    $sgstAmt = (float) ($line->sgst_amount ?? 0);
                    $cgstAmt = (float) ($line->cgst_amount ?? 0);
                    $igstAmt = (float) ($line->igst_amount ?? 0);
                    $totalAmount += $amount; $totalSgst += $sgstAmt; $totalCgst += $cgstAmt; $totalIgst += $igstAmt;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->particular_title }}</td>
                    <td class="amount">{{ number_format($amount, 2) }}</td>
                    <td class="amount">{{ $line->sgst_rate ?? '0' }}</td>
                    <td class="amount">{{ number_format($sgstAmt, 2) }}</td>
                    <td class="amount">{{ $line->cgst_rate ?? '0' }}</td>
                    <td class="amount">{{ number_format($cgstAmt, 2) }}</td>
                    <td class="amount">{{ $line->igst_rate ?? '0' }}</td>
                    <td class="amount">{{ number_format($igstAmt, 2) }}</td>
                    <td>{{ $line->hsn_sac ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;">Total</td>
                <td class="amount">{{ number_format($totalAmount, 2) }}</td>
                <td></td>
                <td class="amount">{{ number_format($totalSgst, 2) }}</td>
                <td></td>
                <td class="amount">{{ number_format($totalCgst, 2) }}</td>
                <td></td>
                <td class="amount">{{ number_format($totalIgst, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <div class="totals-section clearfix">
        <table>
            <tr>
                <td>Sub Total:</td>
                <td class="amount">{{ number_format($totalAmount + $totalSgst + $totalCgst + $totalIgst, 2) }}</td>
            </tr>
            @if (!empty($header['tds_percent']) && $header['tds_percent'] > 0)
            <tr>
                <td>TDS ({{ $header['tds_percent'] }}%):</td>
                <td class="amount">- {{ number_format($header['tds_amount'] ?? 0, 2) }}</td>
            </tr>
            @endif
            @if (!empty($header['deduct_amount']) && $header['deduct_amount'] > 0)
            <tr>
                <td>Deduction:</td>
                <td class="amount">- {{ number_format($header['deduct_amount'], 2) }}</td>
            </tr>
            @endif
            @if (isset($header['round_off_amount']) && $header['round_off_amount'] != 0)
            <tr>
                <td>Round Off:</td>
                <td class="amount">{{ number_format($header['round_off_amount'], 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Total Bill:</td>
                <td class="amount">{{ number_format($header['total_bill_amount'] ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>
    <div class="print-footer">
        <p>Generated on {{ date('d/m/Y h:i A') }}</p>
    </div>
</body>
</html>
