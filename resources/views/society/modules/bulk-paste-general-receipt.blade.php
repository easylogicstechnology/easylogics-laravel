@extends('layouts.app')
@section('title', 'Bulk Paste General Receipt Entry')
@section('content')
<style>
.bulk-paste-container { padding: 0; }
.paste-grid-wrapper { overflow-x: auto; max-height: 70vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; }
#pasteGrid { width: 100%; border-collapse: collapse; font-size: 12px; }
#pasteGrid thead th { background: #009688; color: #fff; padding: 6px 8px; text-align: left; position: sticky; top: 0; z-index: 2; white-space: nowrap; font-size: 11px; border-right: 1px solid #00796b; }
#pasteGrid tbody td { padding: 2px; border: 1px solid #e0e0e0; }
#pasteGrid tbody tr:nth-child(even) { background: #f8f9fa; }
#pasteGrid tbody tr:hover { background: #e0f2f1; }
#pasteGrid tbody tr.row-saved { background: #d4edda !important; }
#pasteGrid tbody tr.row-error { background: #f8d7da !important; }
#pasteGrid input { width: 100%; border: 1px solid transparent; padding: 4px 6px; font-size: 12px; background: transparent; box-sizing: border-box; }
#pasteGrid input:focus { border-color: #009688; outline: none; background: #fff; }
#pasteGrid input.field-error { border-color: #dc3545; background: #fff5f5; }
#pasteGrid input.field-warning { border-color: #ffc107; background: #fffbe6; }
#pasteGrid td.row-num { text-align: center; background: #f1f3f4; color: #666; font-weight: bold; padding: 4px; width: 35px; }
#pasteGrid td.net-amount-cell { text-align: right; padding: 4px 6px; color: #444; background: #f8f9fa; }
#pasteGrid td.status-cell { width: 30px; text-align: center; }
.paste-zone { border: 2px dashed #ccc; border-radius: 8px; padding: 30px; text-align: center; color: #999; margin-bottom: 15px; cursor: text; transition: all 0.3s; background: #fafbfc; }
.paste-zone:focus { border-color: #009688; background: #e0f2f1; outline: none; }
.paste-zone.has-data { display: none; }
.toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
.toolbar .btn { font-size: 13px; }
.stats-bar { display: flex; gap: 12px; flex-wrap: wrap; }
.stat-item { font-size: 12px; padding: 3px 10px; border-radius: 12px; font-weight: 500; }
.stat-total { background: #e8f0fe; color: #1a73e8; }
.stat-valid { background: #e6f4ea; color: #137333; }
.stat-error { background: #fce8e6; color: #c5221f; }
.stat-saved { background: #d4edda; color: #155724; }
.btn { display:inline-block; padding:6px 12px; border-radius:4px; border:1px solid transparent; cursor:pointer; text-decoration:none; font-size:13px; }
.btn-success { background:#28a745; color:#fff; border-color:#28a745; }
.btn-info { background:#17a2b8; color:#fff; border-color:#17a2b8; }
.btn-default { background:#f8f9fa; color:#333; border-color:#ccc; }
.btn-warning { background:#ffc107; color:#333; border-color:#ffc107; }
</style>

<div class="bulk-paste-container">
    <div class="page-header">
        <h2>Bulk Paste General Receipt Entry</h2>
        <div>
            <a href="{{ route('society.generalReceipt') }}" class="btn btn-default btn-sm">Receipt List</a>
            <a href="{{ route('society.addGeneralReceipt') }}" class="btn btn-default btn-sm">Single Receipt</a>
        </div>
    </div>

    <div class="card">
        <div class="top-controls" style="display:flex;gap:15px;align-items:flex-end;flex-wrap:wrap;margin-bottom:15px;">
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:3px;">Bank Account <span style="font-weight:normal;color:#888;">(Type=Bank rows ke liye)</span></label>
                <select id="bankAccountId" class="form-control" style="width:230px;">
                    <option value="">-- Select Bank Account --</option>
                    @foreach($societyBankLists ?? [] as $bankId => $bankName)
                        <option value="{{ $bankId }}">{{ $bankName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:3px;">Cash Account <span style="font-weight:normal;color:#888;">(Type=Cash rows ke liye)</span></label>
                <select id="cashAccountId" class="form-control" style="width:230px;">
                    <option value="">-- Select Cash Account --</option>
                    @foreach($societyCashLists ?? [] as $cashId => $cashName)
                        <option value="{{ $cashId }}">{{ $cashName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;padding-top:18px;">
                <span class="stats-bar" id="statsBar">
                    <span class="stat-item stat-total" id="statTotal">Rows: 0</span>
                    <span class="stat-item stat-valid" id="statValid">Valid: 0</span>
                    <span class="stat-item stat-error" id="statErrors">Errors: 0</span>
                    <span class="stat-item stat-saved" id="statSaved" style="display:none;">Saved: 0</span>
                </span>
            </div>
        </div>
        <p style="font-size:11px;color:#888;margin-top:-8px;margin-bottom:15px;">
            Har row apna "Type" (Bank/Cash) khud bata sakta hai — ek hi batch me Bank aur Cash dono entries ek saath ja sakti hain, jo bhi account upar select kiya hai wahi use hoga.
            "Paid To" text ko existing Income ledger heads se auto-match kiya jayega; naya naam mile to woh head Income category me auto-create ho jayega.
        </p>

        <div class="paste-zone" id="pasteZone" tabindex="0">
            <i class="fa fa-clipboard" style="font-size:40px;color:#ccc;"></i>
            <h4 style="margin:10px 0 5px;color:#888;">Excel se data Ctrl+C karein, phir yahan Ctrl+V karein</h4>
            <p style="font-size:12px;color:#aaa;">
                Excel columns kisi bhi order mein ho sakte hain — header row se automatic mapping ho jayegi
            </p>
            <p style="font-size:11px;color:#bbb;margin-top:3px;">
                Software columns: Paid To | Amount | Type (Bank/Cash) | TDS Bank | TDS Amount | Payment Date | Cheque Date | Cheque No | Bank Name | Particulars | Remark
            </p>
            <p style="font-size:11px;color:#bbb;margin-top:3px;">Ya neeche grid mein manually rows add karein</p>
        </div>

        <div class="toolbar" id="gridToolbar" style="display:none;">
            <button type="button" class="btn btn-success btn-sm" onclick="saveAllRows()"><i class="fa fa-save"></i> Save All</button>
            <button type="button" class="btn btn-info btn-sm" onclick="validateAllRows()"><i class="fa fa-check"></i> Validate</button>
            <button type="button" class="btn btn-default btn-sm" onclick="addEmptyRows(5)"><i class="fa fa-plus"></i> Add 5 Rows</button>
            <button type="button" class="btn btn-default btn-sm" onclick="addEmptyRows(1)"><i class="fa fa-plus"></i> Add 1 Row</button>
            <button type="button" class="btn btn-warning btn-sm" onclick="clearAllRows()"><i class="fa fa-eraser"></i> Clear All</button>
            <span style="flex:1;"></span>
            <span style="font-size:11px;color:#888;"><i class="fa fa-info-circle"></i> Paste karne ke baad bhi individual cells edit kar sakte hain</span>
        </div>

        <div class="paste-grid-wrapper" id="gridWrapper" style="display:none;">
            <table id="pasteGrid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th style="min-width:150px;">Paid To</th>
                        <th style="min-width:90px;">Amount</th>
                        <th style="min-width:70px;">Type</th>
                        <th style="min-width:110px;">TDS Bank</th>
                        <th style="min-width:80px;">TDS Amt</th>
                        <th style="min-width:90px;">Net Amt</th>
                        <th style="min-width:100px;">Payment Date</th>
                        <th style="min-width:100px;">Cheque Date</th>
                        <th style="min-width:90px;">Cheque No</th>
                        <th style="min-width:110px;">Bank Name</th>
                        <th style="min-width:140px;">Particulars</th>
                        <th style="min-width:120px;">Remark</th>
                        <th style="width:30px;"></th>
                    </tr>
                </thead>
                <tbody id="gridBody"></tbody>
            </table>
        </div>

        <div class="toolbar" id="bottomToolbar" style="display:none;margin-top:10px;">
            <button type="button" class="btn btn-success" onclick="saveAllRows()"><i class="fa fa-save"></i> Save All Valid Rows</button>
            <button type="button" class="btn btn-info btn-sm" onclick="validateAllRows()"><i class="fa fa-check"></i> Re-Validate</button>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
var ledgerHeadTitles = {!! json_encode(array_values(($societyOtherIncomeHeadsLists ?? collect())->toArray())) !!};
var ledgerHeadSet = {};
for (var i = 0; i < ledgerHeadTitles.length; i++) { ledgerHeadSet[ledgerHeadTitles[i].toString().trim().toUpperCase()] = true; }

var rowCounter = 0;
var isSaving = false;

var COLS = ['paid_to','amount','payment_type','tds_bank','tds_amount','payment_date','cheque_date','cheque_number','bank_name','particulars','remark'];

var HEADER_ALIASES = {
    'paid_to':        ['paid to','received from','ledger head','income head','head','category','income category','account head','paid_to','ledgerhead'],
    'amount':         ['amount','amt','received amount','receipt amount','total','amount_paid'],
    'payment_type':   ['type','payment type','mode','payment mode','bank/cash','payment_type'],
    'tds_bank':       ['tds bank','tds_bank','tdsbank'],
    'tds_amount':     ['tds amount','tds','tds amt','tax','tax amount','tds_amount'],
    'payment_date':   ['payment date','date','receipt date','received date','pay date','payment_date'],
    'cheque_date':    ['cheque date','chq date','check date','cheque_date'],
    'cheque_number':  ['cheque no','cheque number','cheque no.','chq no','check no','reference no','ref no','cheque_number','chequeno'],
    'bank_name':      ['bank name','bank','payer bank','bank_name','bankname'],
    'particulars':    ['particulars','description','details','purpose'],
    'remark':         ['remark','remarks','notes','note','comment','comments','memo','narration']
};

var _aliasToCol = {};
(function() {
    for (var colKey in HEADER_ALIASES) {
        var aliases = HEADER_ALIASES[colKey];
        for (var a = 0; a < aliases.length; a++) {
            _aliasToCol[aliases[a]] = colKey;
        }
    }
})();

function normalizeHeader(str) {
    return str.toString().replace(/([a-z0-9])([A-Z])/g, '$1 $2').toLowerCase().replace(/[_\-\.\/\\]+/g, ' ').replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
}

function detectHeaderMapping(cells) {
    var mapping = {};
    var matchCount = 0;
    var usedCols = {};

    for (var i = 0; i < cells.length; i++) {
        var raw = cells[i].trim();
        if (!raw) continue;
        var norm = normalizeHeader(raw);
        if (!norm) continue;
        var matchedCol = _aliasToCol[norm] || null;

        if (!matchedCol) {
            for (var alias in _aliasToCol) {
                if (norm.indexOf(alias) !== -1 || alias.indexOf(norm) !== -1) {
                    matchedCol = _aliasToCol[alias];
                    break;
                }
            }
        }

        if (matchedCol && !usedCols[matchedCol]) {
            mapping[i] = matchedCol;
            usedCols[matchedCol] = true;
            matchCount++;
        }
    }

    var nonEmpty = 0;
    for (var j = 0; j < cells.length; j++) { if (cells[j].trim()) nonEmpty++; }
    var isHeader = matchCount >= 3 || (nonEmpty > 0 && matchCount / nonEmpty >= 0.5);
    return isHeader ? mapping : null;
}

function remapRow(cells, colMapping) {
    var mapped = [];
    for (var c = 0; c < COLS.length; c++) mapped.push('');
    for (var excelIdx in colMapping) {
        var colKey = colMapping[excelIdx];
        var masterIdx = COLS.indexOf(colKey);
        if (masterIdx >= 0 && parseInt(excelIdx) < cells.length) {
            mapped[masterIdx] = cells[parseInt(excelIdx)].trim();
        }
    }
    return mapped;
}

function showMappingNotice(colMapping, headerCells) {
    var mapped = [], unmapped = [], extraCols = [];
    var mappedSet = {};

    for (var excelIdx in colMapping) {
        var colKey = colMapping[excelIdx];
        mappedSet[colKey] = true;
        mapped.push('"' + headerCells[parseInt(excelIdx)].trim() + '" -> ' + colKey.replace(/_/g,' '));
    }
    for (var c = 0; c < COLS.length; c++) {
        if (!mappedSet[COLS[c]]) unmapped.push(COLS[c].replace(/_/g,' '));
    }
    for (var i = 0; i < headerCells.length; i++) {
        if (headerCells[i].trim() && !colMapping.hasOwnProperty(i.toString())) {
            extraCols.push('"' + headerCells[i].trim() + '"');
        }
    }

    var html = '<b style="color:#00796b;"><i class="fa fa-columns"></i> Column Mapping Auto-Detected</b><br>';
    html += '<span style="color:#137333;">Mapped (' + mapped.length + '): </span>' + mapped.join(' | ') + '<br>';
    if (unmapped.length) html += '<span style="color:#e37400;">Unmapped (empty): </span>' + unmapped.join(', ') + '<br>';
    if (extraCols.length) html += '<span style="color:#999;">Extra columns ignored: </span>' + extraCols.join(', ');

    var noticeDiv = document.getElementById('mappingNotice');
    if (!noticeDiv) {
        noticeDiv = document.createElement('div');
        noticeDiv.id = 'mappingNotice';
        noticeDiv.style.cssText = 'background:#e0f2f1;border:1px solid #009688;border-radius:6px;padding:10px 15px;margin-bottom:12px;font-size:12px;color:#333;position:relative;line-height:1.6;';
        var closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'position:absolute;top:5px;right:10px;cursor:pointer;font-size:16px;color:#666;';
        closeBtn.onclick = function() { noticeDiv.style.display = 'none'; };
        noticeDiv.appendChild(closeBtn);
        var toolbar = document.getElementById('gridToolbar');
        toolbar.parentNode.insertBefore(noticeDiv, toolbar);
    }
    noticeDiv.style.display = 'block';
    var contentDiv = noticeDiv.querySelector('.mapping-content');
    if (!contentDiv) {
        contentDiv = document.createElement('div');
        contentDiv.className = 'mapping-content';
        noticeDiv.appendChild(contentDiv);
    }
    contentDiv.innerHTML = html;
}

document.getElementById('pasteZone').addEventListener('paste', function(e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData('text');
    if (text && text.trim()) {
        parsePastedData(text);
    }
});

document.addEventListener('paste', function(e) {
    var active = document.activeElement;
    if (active && active.tagName === 'INPUT' && active.closest('#pasteGrid')) {
        var text = (e.clipboardData || window.clipboardData).getData('text');
        if (text.indexOf('\t') !== -1 || text.indexOf('\n') !== -1) {
            e.preventDefault();
            var startInput = active;
            var startRow = startInput.closest('tr');
            var startCell = startInput.closest('td');
            var startColIdx = Array.from(startRow.children).indexOf(startCell) - 1;
            pasteIntoGrid(text, startRow, startColIdx);
        }
    }
});

function parsePastedData(text) {
    var lines = text.split(/\r?\n/);
    var nonEmptyLines = [];
    for (var i = 0; i < lines.length; i++) {
        var line = lines[i].trim();
        if (!line) continue;
        var cells = line.split('\t');
        if (cells.length < 2) continue;
        nonEmptyLines.push(cells);
    }

    if (nonEmptyLines.length === 0) return;

    var colMapping = null;
    var dataStartIdx = 0;

    colMapping = detectHeaderMapping(nonEmptyLines[0]);
    if (colMapping) {
        showMappingNotice(colMapping, nonEmptyLines[0]);
        dataStartIdx = 1;
        if (nonEmptyLines.length > 1) {
            var secondLine = nonEmptyLines[1].join('').replace(/[\-=_\s#\.]/g, '');
            if (!secondLine) dataStartIdx = 2;
        }
    } else {
        while (dataStartIdx < nonEmptyLines.length) {
            var firstCell = nonEmptyLines[dataStartIdx][0].trim();
            if (/^(paid|received|particulars|date|cheque|amount|tds|tax|notes|remark|sr|#)/i.test(firstCell)) {
                dataStartIdx++;
            } else {
                break;
            }
        }
    }

    var dataRows = nonEmptyLines.slice(dataStartIdx);
    if (dataRows.length === 0) return;

    showGrid();

    for (var r = 0; r < dataRows.length; r++) {
        var row = addGridRow();
        var cells = dataRows[r];

        if (colMapping) {
            var mapped = remapRow(cells, colMapping);
            for (var c = 0; c < COLS.length; c++) {
                var input = row.querySelector('input[data-col="' + COLS[c] + '"]');
                if (input) input.value = mapped[c];
            }
        } else {
            for (var c2 = 0; c2 < Math.min(cells.length, COLS.length); c2++) {
                var input2 = row.querySelector('input[data-col="' + COLS[c2] + '"]');
                if (input2) input2.value = cells[c2].trim();
            }
        }
        updateNetAmount(row);
    }

    validateAllRows();
    updateStats();
}

function pasteIntoGrid(text, startRow, startColIdx) {
    var lines = text.split(/\r?\n/);
    var tbody = document.getElementById('gridBody');
    var allRows = Array.from(tbody.querySelectorAll('tr'));
    var rowIdx = allRows.indexOf(startRow);

    for (var i = 0; i < lines.length; i++) {
        var line = lines[i].trim();
        if (!line) continue;
        var cells = line.split('\t');
        var targetRowIdx = rowIdx + i;

        var targetRow;
        if (targetRowIdx < allRows.length) {
            targetRow = allRows[targetRowIdx];
        } else {
            targetRow = addGridRow();
            allRows = Array.from(tbody.querySelectorAll('tr'));
        }

        for (var c = 0; c < cells.length; c++) {
            var colIdx = startColIdx + c;
            if (colIdx >= 0 && colIdx < COLS.length) {
                var input = targetRow.querySelector('input[data-col="' + COLS[colIdx] + '"]');
                if (input) {
                    input.value = cells[c].trim();
                }
            }
        }
        updateNetAmount(targetRow);
    }

    validateAllRows();
    updateStats();
}

function showGrid() {
    document.getElementById('pasteZone').classList.add('has-data');
    document.getElementById('gridWrapper').style.display = 'block';
    document.getElementById('gridToolbar').style.display = 'flex';
    document.getElementById('bottomToolbar').style.display = 'flex';
}

function updateNetAmount(tr) {
    var amt = parseFloat((tr.querySelector('input[data-col="amount"]').value || '0').replace(/,/g,'')) || 0;
    var tds = parseFloat((tr.querySelector('input[data-col="tds_amount"]').value || '0').replace(/,/g,'')) || 0;
    var net = amt - tds;
    tr.querySelector('.net-amount-cell').textContent = net ? net.toFixed(2) : '';
}

function addGridRow() {
    showGrid();
    rowCounter++;
    var tbody = document.getElementById('gridBody');
    var tr = document.createElement('tr');
    tr.setAttribute('data-row-id', rowCounter);

    var html = '<td class="row-num">' + rowCounter + '</td>';
    for (var c = 0; c < COLS.length; c++) {
        var placeholder = '';
        if (COLS[c] === 'payment_date' || COLS[c] === 'cheque_date') placeholder = 'DD-MM-YYYY';
        if (COLS[c] === 'amount' || COLS[c] === 'tds_amount') placeholder = '0.00';
        if (COLS[c] === 'paid_to') placeholder = 'Income head';
        if (COLS[c] === 'payment_type') placeholder = 'Bank/Cash';
        var defaultVal = (COLS[c] === 'payment_type') ? 'value="Bank"' : '';
        var onEvt = ' onblur="validateRow(this.closest(\'tr\'))"';
        if (COLS[c] === 'amount' || COLS[c] === 'tds_amount') onEvt += ' oninput="updateNetAmount(this.closest(\'tr\'))"';
        html += '<td><input type="text" data-col="' + COLS[c] + '" placeholder="' + placeholder + '" ' + defaultVal + onEvt + '></td>';
        if (COLS[c] === 'tds_amount') {
            html += '<td class="net-amount-cell"></td>';
        }
    }
    html += '<td class="status-cell"><a href="javascript:void(0);" onclick="removeRow(this)" title="Remove" style="color:#dc3545;"><i class="fa fa-times"></i></a></td>';

    tr.innerHTML = html;
    tbody.appendChild(tr);
    return tr;
}

function addEmptyRows(count) {
    for (var i = 0; i < count; i++) {
        addGridRow();
    }
    updateStats();
}

function removeRow(el) {
    var tr = el.closest('tr');
    tr.remove();
    renumberRows();
    updateStats();
}

function renumberRows() {
    var rows = document.querySelectorAll('#gridBody tr');
    for (var i = 0; i < rows.length; i++) {
        rows[i].querySelector('.row-num').textContent = (i + 1);
    }
}

function clearAllRows() {
    if (!confirm('Clear all rows?')) return;
    document.getElementById('gridBody').innerHTML = '';
    document.getElementById('pasteZone').classList.remove('has-data');
    document.getElementById('gridWrapper').style.display = 'none';
    document.getElementById('gridToolbar').style.display = 'none';
    document.getElementById('bottomToolbar').style.display = 'none';
    rowCounter = 0;
    updateStats();
}

function isValidDateFormat(dateStr) {
    dateStr = dateStr.replace(/[\/\.]/g, '-');
    var patterns = [
        /^\d{1,2}-\d{1,2}-\d{2,4}$/,
        /^\d{4}-\d{1,2}-\d{1,2}$/,
        /^\d{1,2}-[A-Za-z]{3}-\d{2,4}$/
    ];
    for (var i = 0; i < patterns.length; i++) {
        if (patterns[i].test(dateStr)) return true;
    }
    return false;
}

function validateRow(tr) {
    if (!tr) return;
    var inputs = tr.querySelectorAll('input');
    var hasError = false;

    inputs.forEach(function(input) {
        input.classList.remove('field-error', 'field-warning');
        input.title = '';
    });

    var rowHasData = false;
    inputs.forEach(function(inp) { if (inp.value.trim()) rowHasData = true; });
    if (!rowHasData) { tr.classList.remove('row-error'); return true; }

    var paidToInput = tr.querySelector('input[data-col="paid_to"]');
    var paymentDateInput = tr.querySelector('input[data-col="payment_date"]');
    var amountInput = tr.querySelector('input[data-col="amount"]');
    var tdsAmountInput = tr.querySelector('input[data-col="tds_amount"]');

    if (paidToInput) {
        var paidToVal = paidToInput.value.trim();
        if (!paidToVal) {
            paidToInput.classList.add('field-error');
            paidToInput.title = 'Paid To is required';
            hasError = true;
        } else if (!ledgerHeadSet[paidToVal.toUpperCase()]) {
            paidToInput.classList.add('field-warning');
            paidToInput.title = 'New Income head will be created: ' + paidToVal;
        }
    }

    if (paymentDateInput) {
        var pdVal = paymentDateInput.value.trim();
        if (!pdVal) {
            paymentDateInput.classList.add('field-error');
            paymentDateInput.title = 'Payment Date is required';
            hasError = true;
        } else if (!isValidDateFormat(pdVal)) {
            paymentDateInput.classList.add('field-error');
            paymentDateInput.title = 'Invalid date format';
            hasError = true;
        }
    }

    if (amountInput) {
        var amtVal = amountInput.value.trim().replace(/,/g, '');
        if (amtVal === '' || isNaN(amtVal) || parseFloat(amtVal) <= 0) {
            amountInput.classList.add('field-error');
            amountInput.title = 'Valid amount required';
            hasError = true;
        }
    }

    var paymentTypeInput = tr.querySelector('input[data-col="payment_type"]');
    if (paymentTypeInput && paymentTypeInput.value.trim()) {
        var typeVal = paymentTypeInput.value.trim().toUpperCase();
        if (typeVal !== 'BANK' && typeVal !== 'CASH') {
            paymentTypeInput.classList.add('field-warning');
            paymentTypeInput.title = 'Will default to Bank';
        }
    }

    if (tdsAmountInput && tdsAmountInput.value.trim()) {
        var tdsVal = tdsAmountInput.value.trim().replace(/,/g, '');
        if (isNaN(tdsVal)) {
            tdsAmountInput.classList.add('field-warning');
            tdsAmountInput.title = 'Should be a number';
        }
    }

    tr.classList.remove('row-error');
    if (hasError) {
        tr.classList.add('row-error');
    }

    return !hasError;
}

function validateAllRows() {
    var rows = document.querySelectorAll('#gridBody tr');
    var errorCount = 0;
    var validCount = 0;

    rows.forEach(function(tr) {
        var hasData = false;
        tr.querySelectorAll('input').forEach(function(inp) {
            if (inp.value.trim()) hasData = true;
        });
        if (!hasData) return;

        if (validateRow(tr)) {
            validCount++;
        } else {
            errorCount++;
        }
    });

    updateStats();
    return errorCount === 0;
}

function updateStats() {
    var rows = document.querySelectorAll('#gridBody tr');
    var total = 0, valid = 0, errors = 0, saved = 0;

    rows.forEach(function(tr) {
        var hasData = false;
        tr.querySelectorAll('input').forEach(function(inp) {
            if (inp.value.trim()) hasData = true;
        });
        if (!hasData) return;
        total++;
        if (tr.classList.contains('row-saved')) { saved++; valid++; }
        else if (tr.classList.contains('row-error')) errors++;
        else valid++;
    });

    document.getElementById('statTotal').textContent = 'Rows: ' + total;
    document.getElementById('statValid').textContent = 'Valid: ' + valid;
    document.getElementById('statErrors').textContent = 'Errors: ' + errors;
    if (saved > 0) {
        document.getElementById('statSaved').style.display = '';
        document.getElementById('statSaved').textContent = 'Saved: ' + saved;
    }
}

function saveAllRows() {
    if (isSaving) return;

    validateAllRows();

    var rows = document.querySelectorAll('#gridBody tr');
    var dataRows = [];

    rows.forEach(function(tr, idx) {
        if (tr.classList.contains('row-saved')) return;
        if (tr.classList.contains('row-error')) return;

        var hasData = false;
        var rowData = {};
        tr.querySelectorAll('input').forEach(function(inp) {
            var col = inp.getAttribute('data-col');
            var val = inp.value.trim();
            if (val) hasData = true;
            rowData[col] = val;
        });

        if (hasData) {
            rowData['_row_index'] = idx;
            dataRows.push(rowData);
        }
    });

    if (dataRows.length === 0) {
        alert('No valid rows to save. Fix errors first.');
        return;
    }

    var bankAccountId = document.getElementById('bankAccountId').value;
    var cashAccountId = document.getElementById('cashAccountId').value;

    var needsBank = false, needsCash = false;
    dataRows.forEach(function(rowData) {
        var typeVal = (rowData.payment_type || 'Bank').trim().toUpperCase();
        if (typeVal === 'CASH') needsCash = true; else needsBank = true;
    });

    if (needsBank && !bankAccountId) {
        alert('Kuch rows Type=Bank hain - Bank Account select karein!');
        document.getElementById('bankAccountId').focus();
        return;
    }
    if (needsCash && !cashAccountId) {
        alert('Kuch rows Type=Cash hain - Cash Account select karein!');
        document.getElementById('cashAccountId').focus();
        return;
    }

    if (!confirm('Save ' + dataRows.length + ' rows? Check data carefully before saving.')) return;

    isSaving = true;
    var saveBtn = document.querySelector('.btn-success');
    var origText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    var postData = {
        bank_account_id: bankAccountId,
        cash_account_id: cashAccountId,
        rows: dataRows
    };

    $.ajax({
        url: '{{ route('society.saveBulkPasteGeneralReceipt') }}',
        type: 'POST',
        data: JSON.stringify(postData),
        contentType: 'application/json',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            isSaving = false;
            saveBtn.innerHTML = origText;
            saveBtn.disabled = false;

            if (response.success) {
                var allRows = document.querySelectorAll('#gridBody tr');

                if (response.results) {
                    response.results.forEach(function(result) {
                        var rowIdx = -1;
                        for (var d = 0; d < dataRows.length; d++) {
                            if (dataRows[d]._row_index === undefined) continue;
                            if (result.row == d) {
                                rowIdx = dataRows[d]._row_index;
                                break;
                            }
                        }
                        if (rowIdx >= 0 && rowIdx < allRows.length) {
                            var tr = allRows[rowIdx];
                            if (result.status === 'success') {
                                tr.classList.remove('row-error');
                                tr.classList.add('row-saved');
                                tr.querySelectorAll('input').forEach(function(inp) {
                                    inp.readOnly = true;
                                    inp.style.color = '#666';
                                });
                            } else if (result.status === 'error') {
                                tr.classList.add('row-error');
                                var firstInput = tr.querySelector('input');
                                if (firstInput && result.errors) {
                                    firstInput.title = result.errors.join(', ');
                                }
                            }
                        }
                    });
                }

                updateStats();
                var msg = 'Saved: ' + response.added + ' / ' + response.total;
                if (response.failed > 0) msg += ' | Failed: ' + response.failed;
                alert(msg);
            } else {
                alert('Error: ' + (response.message || 'Save failed'));
            }
        },
        error: function(xhr) {
            isSaving = false;
            saveBtn.innerHTML = origText;
            saveBtn.disabled = false;
            alert('Server error. Please try again.');
        }
    });
}
</script>
@endsection
