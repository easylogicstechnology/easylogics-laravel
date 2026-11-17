@extends('layouts.app')
@section('title', 'Member Tariff')
@section('content')
<div class="page-header">
    <h2>Regular Bill Tariff Master</h2>
    <div>
        <a href="{{ route('society.bulkPasteTariff') }}" class="btn btn-sm" style="background:#5bc0de; color:#fff; margin-right:6px;" title="Paste Excel data to set many members' tariff at once">Bulk Paste Tariff</a>
        <a href="{{ route('society.memberTariffUpload') }}" class="btn btn-sm" style="background:#f0ad4e; color:#fff;">Upload Member Tariff CSV</a>
    </div>
</div>

<div class="card">
    <div style="display:flex; gap:16px; margin-bottom:12px;">
        <a href="#" onclick="showTab('individual'); return false;" id="tabIndividual" style="color:#e67e22; font-weight:bold; text-decoration:none; border-bottom:2px solid #e67e22; padding-bottom:4px;">Individual Tariff</a>
        <a href="#" onclick="showTab('all'); return false;" id="tabAll" style="color:#333; text-decoration:none; padding-bottom:4px;">All Member's Tariff</a>
    </div>

    <div id="panelIndividual">
        <div style="display:flex; gap:16px;">
            <div style="flex:2;">
                <form method="POST" action="{{ route('society.memberTariff') }}">
                    @csrf
                    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:12px;">
                        <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                            <label>Society Members</label>
                            <select name="member_id" class="form-control" id="memberSelect" onchange="loadMemberTariff(this.value)" required>
                                <option value="">Choose Members</option>
                                @foreach($societyMemberList as $mId => $mName)
                                <option value="{{ $mId }}">{{ $mName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0; min-width:160px;">
                            <label>Effective Date :</label>
                            <input type="date" name="tariff_effective_since" class="form-control" id="effectiveDate" required>
                        </div>
                        <div class="form-group" style="margin:0; min-width:80px;">
                            <label>Unit No :</label>
                            <input type="text" class="form-control" id="memberFlatNo" readonly>
                        </div>
                    </div>

                    <div style="overflow-x:auto;">
                        <table style="width:80%;">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Particulars</th>
                                    <th>Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $s = 1; @endphp
                                @foreach($ledgerHeads as $lh)
                                <tr>
                                    <td>{{ $s++ }}</td>
                                    <td>{{ $lh->title }}</td>
                                    <td><input type="text" name="rates[{{ $lh->id }}]" class="form-control tariffRate" id="rate_{{ $lh->id }}" style="width:120px; text-align:right;" oninput="calcTariffTotal()"></td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td><b>Total</b></td>
                                    <td><input type="text" class="form-control" id="totalTariff" style="width:120px; text-align:right;" readonly></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:12px;">
                        <div class="form-group" style="max-width:400px;">
                            <label>Remark :</label>
                            <textarea name="remark" id="tariffRemark" rows="3" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Submit</button>
                    </div>
                </form>
            </div>

            <div style="flex:1;">
                <table>
                    <thead>
                        <tr style="background:#e9ecef;">
                            <th>PARTICULARS</th>
                            <th style="text-align:center;">RATE</th>
                        </tr>
                    </thead>
                    <tbody id="memberTariffSidebar">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="panelAll" style="display:none;">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:12px;">
            <div class="form-group" style="margin:0;">
                <label>Building Name <span class="required">*</span></label>
                <select class="form-control" id="allBuildingId" onchange="getAllBuildingWings(this.value)">
                    <option value="">Select building</option>
                    @foreach($buildings as $bId => $bName)
                    <option value="{{ $bId }}">{{ $bName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Wing</label>
                <select class="form-control" id="allWingId">
                    <option value="">Select Wing</option>
                </select>
            </div>
            <button type="button" class="btn btn-sm" style="background:#5bc0de; color:#fff;" onclick="loadAllMemberTariff()">Show</button>
        </div>
        <div id="allMemberTariffData" style="overflow-x:auto;"></div>
        <div id="allMemberUpdateBtn" style="margin-top:12px; display:none;">
            <button type="button" class="btn btn-success" onclick="updateAllMemberTariff()">Update Member Tariff</button>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    document.getElementById('panelIndividual').style.display = tab === 'individual' ? '' : 'none';
    document.getElementById('panelAll').style.display = tab === 'all' ? '' : 'none';
    document.getElementById('tabIndividual').style.borderBottom = tab === 'individual' ? '2px solid #e67e22' : 'none';
    document.getElementById('tabIndividual').style.color = tab === 'individual' ? '#e67e22' : '#333';
    document.getElementById('tabAll').style.borderBottom = tab === 'all' ? '2px solid #e67e22' : 'none';
    document.getElementById('tabAll').style.color = tab === 'all' ? '#e67e22' : '#333';
}

function loadMemberTariff(memberId) {
    if (!memberId) return;
    fetch('{{ route("society.getMemberTariffData") }}?member_id=' + memberId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('memberFlatNo').value = data.flat_no;
            if (data.effective_date) document.getElementById('effectiveDate').value = data.effective_date;
            if (data.remark) document.getElementById('tariffRemark').value = data.remark;
            var sidebar = document.getElementById('memberTariffSidebar');
            sidebar.innerHTML = '';
            var inputs = document.querySelectorAll('.tariffRate');
            for (var i = 0; i < inputs.length; i++) inputs[i].value = '';
            var tariffs = data.tariffs;
            var sidebarTotal = 0;
            for (var lhId in tariffs) {
                var input = document.getElementById('rate_' + lhId);
                if (input) input.value = tariffs[lhId].amount;
                var amt = parseFloat(tariffs[lhId].amount) || 0;
                sidebarTotal += amt;
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (input ? input.closest('tr').children[1].textContent : lhId) + '</td><td style="text-align:right;">' + tariffs[lhId].amount + '</td>';
                sidebar.appendChild(tr);
            }
            var totalTr = document.createElement('tr');
            totalTr.style.borderTop = '2px solid #333';
            totalTr.style.fontWeight = 'bold';
            totalTr.innerHTML = '<td>Total</td><td style="text-align:right;">' + sidebarTotal.toFixed(2) + '</td>';
            sidebar.appendChild(totalTr);
            calcTariffTotal();
        });
}

function calcTariffTotal() {
    var inputs = document.querySelectorAll('.tariffRate');
    var total = 0;
    for (var i = 0; i < inputs.length; i++) total += parseFloat(inputs[i].value) || 0;
    document.getElementById('totalTariff').value = total.toFixed(2);
}

function getAllBuildingWings(buildingId) {
    var wingSelect = document.getElementById('allWingId');
    wingSelect.innerHTML = '<option value="">Select Wing</option>';
    if (!buildingId) return;
    fetch('{{ route("society.getWings") }}?building_id=' + buildingId)
        .then(function(r) { return r.json(); })
        .then(function(wings) {
            for (var wId in wings) {
                var opt = document.createElement('option');
                opt.value = wId;
                opt.textContent = wings[wId];
                wingSelect.appendChild(opt);
            }
        });
}

function loadAllMemberTariff() {
    // CakePHP: no building chosen = every member of the society
    var buildingId = document.getElementById('allBuildingId').value;
    var wingId = document.getElementById('allWingId').value;
    document.getElementById('allMemberTariffData').innerHTML = '<p style="color:#999;">Loading...</p>';
    document.getElementById('allMemberUpdateBtn').style.display = 'none';

    fetch('{{ route("society.getAllMemberTariffDetails") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({building_id: buildingId, wing_id: wingId})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var heads = data.ledger_heads;
        var members = data.members;
        var totals = data.ledger_totals;
        var inactive = {};
        (data.inactive_head_ids || []).forEach(function(id) { inactive[id] = true; });

        var html = '<table style="width:100%; border-collapse:collapse; font-size:13px;">';
        html += '<thead><tr style="background:#e9ecef;">';
        html += '<th style="padding:4px; border:1px solid #ddd;">SR.</th>';
        html += '<th style="padding:4px; border:1px solid #ddd;">MEMBER NAME</th>';
        html += '<th style="padding:4px; border:1px solid #ddd;">Flat No</th>';
        for (var h = 0; h < heads.length; h++) {
            // CakePHP get_all_member_tariff_details.ctp: per-head Active toggle, + Add / - Less, = Set Actual (for ALL members)
            var hid = heads[h].id, ht = escHtml(heads[h].title);
            html += '<th style="padding:4px; border:1px solid #ddd; vertical-align:top; font-size:12px;">' + ht + '<br>';
            if (data.has_active_column) {
                html += '<label style="font-weight:normal; font-size:10px; white-space:nowrap;" title="Untick to stop billing this head for ALL members from the next bill. Already generated bills are not affected.">'
                    + '<input type="checkbox" onchange="toggleHeadActive(this, ' + hid + ')" data-title="' + ht + '"' + (inactive[hid] ? '' : ' checked') + '> Active</label><br>';
            }
            html += '<a href="#" onclick="adjustHead(' + hid + ', \'add\', this); return false;" data-title="' + ht + '" style="font-size:10px; color:#137333; text-decoration:none;" title="Add a flat amount to this head for ALL members at once">+ Add</a>'
                + ' / <a href="#" onclick="adjustHead(' + hid + ', \'less\', this); return false;" data-title="' + ht + '" style="font-size:10px; color:#c5221f; text-decoration:none;" title="Subtract a flat amount from this head for ALL members at once">&minus; Less</a><br>'
                + '<a href="#" onclick="setHead(' + hid + ', this); return false;" data-title="' + ht + '" style="font-size:10px; color:#1a73e8; text-decoration:none;" title="Set this head to one exact amount for ALL members at once">= Set Actual</a></th>';
        }
        html += '<th style="padding:4px; border:1px solid #ddd;">Total</th>';
        html += '</tr></thead><tbody>';

        for (var i = 0; i < members.length; i++) {
            var m = members[i];
            html += '<tr>';
            html += '<td style="padding:4px; border:1px solid #ddd;">' + (i + 1) + '</td>';
            html += '<td style="padding:4px; border:1px solid #ddd;">' + escHtml(m.member_name) + '</td>';
            html += '<td style="padding:4px; border:1px solid #ddd;">' + escHtml(m.flat_no) + '</td>';
            for (var h = 0; h < heads.length; h++) {
                var amt = m.ledger_data[heads[h].id] || '0.00';
                html += '<td class="tariff-cell-' + heads[h].id + '" style="padding:2px; border:1px solid #ddd;' + (inactive[heads[h].id] ? ' background:#fbe9e7;' : '') + '">';
                html += '<input type="text" name="MemberTariff[' + m.member_id + '][' + heads[h].id + ']" value="' + amt + '" style="width:70px; text-align:right; border:1px solid #ccc; padding:2px;" class="allTariffInput" data-member="' + m.member_id + '" data-ledger="' + heads[h].id + '" oninput="recalcRow(this)">';
                html += '</td>';
            }
            html += '<td style="padding:4px; border:1px solid #ddd; text-align:right; font-weight:bold;" class="rowTotal">' + m.total + '</td>';
            html += '</tr>';
        }

        if (!members.length) {
            html += '<tr><td colspan="' + (heads.length + 4) + '" style="padding:8px; text-align:center; color:#999;">No active members found'
                + (buildingId ? ' in this building / wing. Choose "Select building" to see every member.' : '.') + '</td></tr>';
        }
        html += '<tr style="background:#f5f5f5; font-weight:bold;">';
        html += '<td style="padding:4px; border:1px solid #ddd;"></td>';
        html += '<td style="padding:4px; border:1px solid #ddd;">Grand Total</td>';
        html += '<td style="padding:4px; border:1px solid #ddd;"></td>';
        var grandTotal = 0;
        for (var h = 0; h < heads.length; h++) {
            var colTotal = parseFloat(totals[heads[h].id]) || 0;
            grandTotal += colTotal;
            html += '<td style="padding:4px; border:1px solid #ddd; text-align:right;" class="colTotal" data-ledger="' + heads[h].id + '">' + colTotal.toFixed(2) + '</td>';
        }
        html += '<td style="padding:4px; border:1px solid #ddd; text-align:right;" id="grandTotal">' + grandTotal.toFixed(2) + '</td>';
        html += '</tr></tbody></table>';

        document.getElementById('allMemberTariffData').innerHTML = html;
        document.getElementById('allMemberUpdateBtn').style.display = '';
    });
}

function escHtml(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; });
}

function tariffPost(url, payload) {
    return fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify(payload)
    }).then(function(r) { return r.json(); });
}

function toggleHeadActive(box, headId) {
    var isActive = box.checked, title = box.getAttribute('data-title');
    if (!confirm((isActive ? 'Activate' : 'Deactivate') + ' "' + title + '" for ALL members?\n\n' +
        (isActive ? 'It will be billed for every member again from the next bill.' : "It will be excluded from every member's next bill. Already generated bills are not affected."))) {
        box.checked = !isActive;
        return;
    }
    box.disabled = true;
    tariffPost('{{ route("society.tariffHeadActive") }}', {ledger_head_id: headId, is_active: isActive ? 1 : 0})
        .then(function(resp) {
            box.disabled = false;
            if (resp.error === 0) {
                document.querySelectorAll('.tariff-cell-' + headId).forEach(function(td) { td.style.background = isActive ? '' : '#fbe9e7'; });
            } else {
                box.checked = !isActive;
            }
            alert(resp.error_message || resp.message || 'Could not update.');
        })
        .catch(function() { box.disabled = false; box.checked = !isActive; alert('Server error. Please try again.'); });
}

function adjustHead(headId, mode, link) {
    var title = link.getAttribute('data-title');
    var amountStr = prompt((mode === 'add' ? 'Add' : 'Subtract') + ' how much to "' + title + '" for ALL members?\n(e.g. 50)');
    if (amountStr === null) return;
    amountStr = amountStr.toString().trim().replace(/,/g, '');
    var amount = parseFloat(amountStr);
    if (amountStr === '' || isNaN(amount) || amount <= 0) { alert('Enter a valid amount greater than 0.'); return; }
    if (!confirm((mode === 'add' ? 'Add Rs.' + amount + ' to' : 'Subtract Rs.' + amount + ' from') + ' "' + title + '" for all members who already have a rate for it?\n\nMembers currently at Rs.0 for this head will be left untouched.')) return;
    tariffPost('{{ route("society.tariffHeadAdjust") }}', {ledger_head_id: headId, mode: mode, amount: amount})
        .then(function(resp) { alert(resp.error_message || resp.message || 'Could not update.'); if (resp.error === 0) loadAllMemberTariff(); })
        .catch(function() { alert('Server error. Please try again.'); });
}

function setHead(headId, link) {
    var title = link.getAttribute('data-title');
    var amountStr = prompt('Set "' + title + '" to what exact amount for ALL members?\n(e.g. 500)');
    if (amountStr === null) return;
    amountStr = amountStr.toString().trim().replace(/,/g, '');
    var amount = parseFloat(amountStr);
    if (amountStr === '' || isNaN(amount) || amount < 0) { alert('Enter a valid amount (0 or more).'); return; }
    if (!confirm('Set "' + title + '" to Rs.' + amount + ' for ALL members?\n\nThis overwrites the current rate for everyone who has this head - it does not add or subtract.')) return;
    tariffPost('{{ route("society.tariffHeadSet") }}', {ledger_head_id: headId, amount: amount})
        .then(function(resp) { alert(resp.error_message || resp.message || 'Could not update.'); if (resp.error === 0) loadAllMemberTariff(); })
        .catch(function() { alert('Server error. Please try again.'); });
}

function recalcRow(input) {
    var row = input.closest('tr');
    var inputs = row.querySelectorAll('.allTariffInput');
    var total = 0;
    for (var i = 0; i < inputs.length; i++) total += parseFloat(inputs[i].value) || 0;
    row.querySelector('.rowTotal').textContent = total.toFixed(2);
    recalcColumns();
}

function recalcColumns() {
    var table = document.getElementById('allMemberTariffData').querySelector('table');
    if (!table) return;
    var colTotals = {};
    var inputs = table.querySelectorAll('.allTariffInput');
    for (var i = 0; i < inputs.length; i++) {
        var lid = inputs[i].getAttribute('data-ledger');
        if (!colTotals[lid]) colTotals[lid] = 0;
        colTotals[lid] += parseFloat(inputs[i].value) || 0;
    }
    var grandTotal = 0;
    var colCells = table.querySelectorAll('.colTotal');
    for (var i = 0; i < colCells.length; i++) {
        var lid = colCells[i].getAttribute('data-ledger');
        var val = colTotals[lid] || 0;
        colCells[i].textContent = val.toFixed(2);
        grandTotal += val;
    }
    var gt = document.getElementById('grandTotal');
    if (gt) gt.textContent = grandTotal.toFixed(2);
}

function updateAllMemberTariff() {
    var inputs = document.querySelectorAll('.allTariffInput');
    var postData = {};
    for (var i = 0; i < inputs.length; i++) {
        var mid = inputs[i].getAttribute('data-member');
        var lid = inputs[i].getAttribute('data-ledger');
        if (!postData[mid]) postData[mid] = {};
        postData[mid][lid] = inputs[i].value;
    }

    fetch('{{ route("society.updateAllMemberTariffDetails") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({MemberTariff: postData})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        alert(data.error_message || data.message || 'Could not save.');
    })
    .catch(function() { alert('Server error. Please try again.'); });
}
</script>
@endsection
