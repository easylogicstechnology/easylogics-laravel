@extends('layouts.app')
@section('title', 'Bulk Paste Member Tariff')
@section('content')
{{-- CakePHP societys_members/bulk_paste_tariff.ctp, saved through society.saveBulkPasteTariff --}}
<style>
.bulk-paste-container { padding: 0; }
.paste-grid-wrapper { overflow-x: auto; max-height: 70vh; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; }
#pasteGrid { width: 100%; border-collapse: collapse; font-size: 12px; }
#pasteGrid thead th { background: #4472C4; color: #fff; padding: 6px 8px; text-align: left; position: sticky; top: 0; z-index: 2; white-space: nowrap; font-size: 11px; border-right: 1px solid #3a63a8; }
#pasteGrid tbody td { padding: 2px; border: 1px solid #e0e0e0; }
#pasteGrid tbody tr:nth-child(even) { background: #f8f9fa; }
#pasteGrid tbody tr:hover { background: #e8f0fe; }
#pasteGrid tbody tr.row-saved { background: #d4edda !important; }
#pasteGrid tbody tr.row-error { background: #f8d7da !important; }
#pasteGrid input { width: 100%; border: 1px solid transparent; padding: 4px 6px; font-size: 12px; background: transparent; box-sizing: border-box; }
#pasteGrid input:focus { border-color: #4472C4; outline: none; background: #fff; }
#pasteGrid input.field-error { border-color: #dc3545; background: #fff5f5; }
#pasteGrid td.row-num { text-align: center; background: #f1f3f4; color: #666; font-weight: bold; padding: 4px; width: 35px; }
#pasteGrid td.status-cell { width: 30px; text-align: center; }
.paste-zone { border: 2px dashed #ccc; border-radius: 8px; padding: 30px; text-align: center; color: #999; margin-bottom: 15px; cursor: text; transition: all 0.3s; background: #fafbfc; }
.paste-zone:focus { border-color: #4472C4; background: #e8f0fe; outline: none; }
.paste-zone.has-data { display: none; }
.toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
.toolbar .btn { font-size: 13px; }
.stats-bar { display: flex; gap: 12px; flex-wrap: wrap; }
.stat-item { font-size: 12px; padding: 3px 10px; border-radius: 12px; font-weight: 500; }
.stat-total { background: #e8f0fe; color: #1a73e8; }
.stat-valid { background: #e6f4ea; color: #137333; }
.stat-error { background: #fce8e6; color: #c5221f; }
.stat-saved { background: #d4edda; color: #155724; }
</style>

<div class="container-fluid bulk-paste-container">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default card-view">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h6 class="panel-title txt-dark"><i class="fa fa-paste"></i> Bulk Paste Member Tariff</h6>
                    </div>
                    <div class="pull-right">
                        <a href="{{ route('society.memberTariff') }}" class="btn btn-default btn-sm"><i class="fa fa-list"></i> Member Tariff</a>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">

                        <div class="top-controls" style="margin-bottom:15px;">
                            <span class="stats-bar" id="statsBar">
                                <span class="stat-item stat-total" id="statTotal">Rows: 0</span>
                                <span class="stat-item stat-valid" id="statValid">Valid: 0</span>
                                <span class="stat-item stat-error" id="statErrors">Errors: 0</span>
                                <span class="stat-item stat-saved" id="statSaved" style="display:none;">Saved: 0</span>
                            </span>
                        </div>

                        <div class="paste-zone" id="pasteZone" tabindex="0">
                            <i class="fa fa-clipboard" style="font-size:40px;color:#ccc;"></i>
                            <h4 style="margin:10px 0 5px;color:#888;">Excel se data Ctrl+C karein, phir yahan Ctrl+V karein</h4>
                            <p style="font-size:12px;color:#aaa;">
                                Pehla column Flat Number, uske baad har Particular (Ledger Head) ka rate column
                            </p>
                            <p style="font-size:11px;color:#bbb;margin-top:3px;">
                                Software columns: Flat Number | {{ $ledgerHeads->pluck('title')->implode(' | ') }}
                            </p>
                            <p style="font-size:11px;color:#bbb;margin-top:3px;">Ya neeche grid mein manually rows add karein. Khaali cell wala head us member ke liye chhod diya jayega.</p>
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
                                        <th style="min-width:90px;">Flat Number</th>
                                        @foreach($ledgerHeads as $head)
                                                <th style="min-width:90px;">{{ $head->title }}</th>
                                        @endforeach
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
            </div>
        </div>
    </div>
</div>



<script>
var validFlats = @json($validFlats);
var validFlatsSet = {};
for (var i = 0; i < validFlats.length; i++) {
    validFlatsSet[validFlats[i].toString().trim().toUpperCase()] = true;
}
var LEDGER_HEADS = @json($ledgerHeads->map(fn ($h) => ['id' => $h->id, 'title' => $h->title])->values());
// COLS[0] is always the flat number; COLS[1..] map 1:1 to LEDGER_HEADS by index.
var COLS = ['flat_number'];
for (var lh = 0; lh < LEDGER_HEADS.length; lh++) { COLS.push('head_' + LEDGER_HEADS[lh].id); }

var rowCounter = 0;
var isSaving = false;

function normalizeHeader(str) {
    return str.toString().replace(/([a-z0-9])([A-Z])/g, '$1 $2').toLowerCase().replace(/[_\-\.\/\\]+/g, ' ').replace(/[^a-z0-9\s]/g, '').replace(/\s+/g, ' ').trim();
}

var FLAT_ALIASES = ['flat number','flat no','flat_no','flatno','flat','unit no','unit number','unit','flat/shop','shop no','shop number','flat/shop no','flat_number','member flat','wing flat'];

function detectHeaderMapping(cells) {
    var mapping = {}; // excelIdx -> colKey ('flat_number' or 'head_<id>')
    var matchCount = 0;

    for (var i = 0; i < cells.length; i++) {
        var raw = cells[i].trim();
        if (!raw) continue;
        var norm = normalizeHeader(raw);
        if (!norm) continue;

        if (FLAT_ALIASES.indexOf(norm) !== -1) {
            mapping[i] = 'flat_number';
            matchCount++;
            continue;
        }
        for (var h = 0; h < LEDGER_HEADS.length; h++) {
            var headNorm = normalizeHeader(LEDGER_HEADS[h].title);
            if (headNorm === norm || headNorm.indexOf(norm) !== -1 || norm.indexOf(headNorm) !== -1) {
                mapping[i] = 'head_' + LEDGER_HEADS[h].id;
                matchCount++;
                break;
            }
        }
    }

    var nonEmpty = 0;
    for (var j = 0; j < cells.length; j++) { if (cells[j].trim()) nonEmpty++; }
    var isHeader = matchCount >= 2 || (nonEmpty > 0 && matchCount / nonEmpty >= 0.5);
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

    var colMapping = detectHeaderMapping(nonEmptyLines[0]);
    var dataStartIdx = colMapping ? 1 : 0;

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
                if (input) input.value = cells[c].trim();
            }
        }
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

function addGridRow() {
    showGrid();
    rowCounter++;
    var tbody = document.getElementById('gridBody');
    var tr = document.createElement('tr');
    tr.setAttribute('data-row-id', rowCounter);

    var html = '<td class="row-num">' + rowCounter + '</td>';
    for (var c = 0; c < COLS.length; c++) {
        var placeholder = (COLS[c] === 'flat_number') ? 'e.g. 1/0101' : '0.00';
        html += '<td><input type="text" data-col="' + COLS[c] + '" placeholder="' + placeholder + '" onblur="validateRow(this.closest(\'tr\'))"></td>';
    }
    html += '<td class="status-cell"><a href="javascript:void(0);" onclick="removeRow(this)" title="Remove" style="color:#dc3545;"><i class="fa fa-times"></i></a></td>';

    tr.innerHTML = html;
    tbody.appendChild(tr);
    return tr;
}

function addEmptyRows(count) {
    for (var i = 0; i < count; i++) addGridRow();
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

function validateRow(tr) {
    if (!tr) return true;
    var inputs = tr.querySelectorAll('input');
    var hasError = false;

    inputs.forEach(function(input) {
        input.classList.remove('field-error');
        input.title = '';
    });

    var flatInput = tr.querySelector('input[data-col="flat_number"]');
    var hasData = false;
    inputs.forEach(function(inp) { if (inp.value.trim()) hasData = true; });

    if (flatInput) {
        var flatVal = flatInput.value.trim().toUpperCase();
        if (!flatVal) {
            if (hasData) {
                flatInput.classList.add('field-error');
                flatInput.title = 'Flat Number is required';
                hasError = true;
            }
        } else if (!validFlatsSet[flatVal]) {
            flatInput.classList.add('field-error');
            flatInput.title = 'Flat Number not found in system';
            hasError = true;
        }
    }

    inputs.forEach(function(inp) {
        var col = inp.getAttribute('data-col');
        if (col && col.indexOf('head_') === 0 && inp.value.trim()) {
            var val = inp.value.trim().replace(/,/g, '');
            if (isNaN(val)) {
                inp.classList.add('field-error');
                inp.title = 'Must be a number';
                hasError = true;
            }
        }
    });

    tr.classList.remove('row-error');
    if (hasError) tr.classList.add('row-error');
    return !hasError;
}

function validateAllRows() {
    var rows = document.querySelectorAll('#gridBody tr');
    var errorCount = 0, validCount = 0;

    rows.forEach(function(tr) {
        var hasData = false;
        tr.querySelectorAll('input').forEach(function(inp) { if (inp.value.trim()) hasData = true; });
        if (!hasData) return;
        if (validateRow(tr)) validCount++; else errorCount++;
    });

    updateStats();
    return errorCount === 0;
}

function updateStats() {
    var rows = document.querySelectorAll('#gridBody tr');
    var total = 0, valid = 0, errors = 0, saved = 0;

    rows.forEach(function(tr) {
        var hasData = false;
        tr.querySelectorAll('input').forEach(function(inp) { if (inp.value.trim()) hasData = true; });
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
        var flatNo = '';
        var heads = {};
        tr.querySelectorAll('input').forEach(function(inp) {
            var col = inp.getAttribute('data-col');
            var val = inp.value.trim();
            if (val) hasData = true;
            if (col === 'flat_number') flatNo = val;
            else if (col && col.indexOf('head_') === 0 && val !== '') heads[col.substring(5)] = val;
        });

        if (hasData && flatNo) {
            dataRows.push({ _row_index: idx, flat_number: flatNo, heads: heads });
        }
    });

    if (dataRows.length === 0) {
        alert('No valid rows to save. Fix errors first.');
        return;
    }

    if (!confirm('Save ' + dataRows.length + ' rows? Check data carefully before saving.')) return;

    isSaving = true;
    var saveBtn = document.querySelector('#gridToolbar .btn-success');
    var origText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;

    fetch('{{ route("society.saveBulkPasteTariff") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({ rows: dataRows })
    })
    .then(function(r) { return r.json(); })
    .then(function(response) {
            isSaving = false;
            saveBtn.innerHTML = origText;
            saveBtn.disabled = false;

            if (response.success) {
                var allRows = document.querySelectorAll('#gridBody tr');
                if (response.results) {
                    response.results.forEach(function(result) {
                        var rowIdx = -1;
                        for (var d = 0; d < dataRows.length; d++) {
                            if (result.row == d) { rowIdx = dataRows[d]._row_index; break; }
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
                                if (firstInput && result.errors) firstInput.title = result.errors.join(', ');
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
    })
    .catch(function() {
        isSaving = false;
        saveBtn.innerHTML = origText;
        saveBtn.disabled = false;
        alert('Server error. Please try again.');
    });
}
</script>
@endsection
