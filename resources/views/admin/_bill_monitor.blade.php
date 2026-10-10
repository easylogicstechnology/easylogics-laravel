<script>
window.BILL_MONITOR = {
    months: @json(route('admin.billMonitor.months')),
    month: @json(route('admin.billMonitor.month')),
    check: @json(route('admin.billMonitor.check')),
    reconcile: @json(route('admin.billMonitor.reconcile')),
    csrf: @json(csrf_token()),
    years: @json(collect($billYears)->map(fn ($label, $id) => ['id' => $id, 'label' => $label])->values())
};
</script>
@verbatim
<div id="bm_root" style="margin-top:16px;background:#fff;border:1px solid #ddd;border-radius:3px;padding:14px;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:10px;">
        <h4 style="margin:0;font-weight:600;">Bill Summary Monitor</h4>
        <span style="color:#777;font-size:12px;">Month-wise bills generated, reseller-wise, and Bill Summary Update correct / wrong check</span>
    </div>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:12px;">
        <label style="margin:0;">Financial Year</label>
        <select id="bm_fy" style="padding:4px 8px;"></select>
        <label style="margin:0;">Month</label>
        <select id="bm_month" style="padding:4px 8px;"></select>
        <label style="margin:0;">Show</label>
        <select id="bm_filter" style="padding:4px 8px;">
            <option value="all">All societies</option>
            <option value="wrong">Wrong only</option>
            <option value="unchecked">Not checked yet</option>
        </select>
        <button type="button" id="bm_check" style="padding:5px 14px;background:#1d4ea1;color:#fff;border:0;border-radius:3px;">Check Bills (Correct / Wrong)</button>
        <button type="button" id="bm_recheck" style="padding:5px 14px;background:#666;color:#fff;border:0;border-radius:3px;" title="Ignore saved results and check again">Re-check all</button>
        <button type="button" id="bm_stop" style="padding:5px 14px;display:none;border:1px solid #bbb;border-radius:3px;">Stop</button>
        <span id="bm_progress" style="color:#555;font-size:12px;"></span>
    </div>

    <div id="bm_tiles" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;"></div>

    <div style="display:flex;flex-wrap:wrap;gap:16px;">
        <div style="flex:1 1 320px;min-width:300px;">
            <h5 style="font-weight:600;">Month-wise (this financial year)</h5>
            <table class="table table-bordered" style="width:100%;font-size:12px;">
                <thead><tr><th>Month</th><th class="text-right">Societies</th><th class="text-right">Bills</th></tr></thead>
                <tbody id="bm_months"></tbody>
            </table>
        </div>
        <div style="flex:2 1 460px;min-width:320px;">
            <h5 style="font-weight:600;">Reseller-wise (selected month)</h5>
            <table class="table table-bordered" style="width:100%;font-size:12px;">
                <thead><tr><th>Reseller</th><th class="text-right">Societies</th><th class="text-right">Bills</th><th class="text-right">Correct</th><th class="text-right">Wrong</th></tr></thead>
                <tbody id="bm_resellers"></tbody>
            </table>
        </div>
    </div>

    <h5 style="font-weight:600;margin-top:10px;">Society-wise (selected month)</h5>
    <div style="max-height:520px;overflow:auto;">
        <table class="table table-bordered" style="width:100%;font-size:12px;">
            <thead><tr><th>#</th><th>Society</th><th>Reseller</th><th class="text-right">Bills</th><th>Bill Summary check</th><th></th></tr></thead>
            <tbody id="bm_societies"></tbody>
        </table>
    </div>
    <div style="color:#888;font-size:11px;margin-top:6px;">
        Bills = bill rows generated for the billing month. Correct / Wrong = members whose closing balance (last year + this year's bills - receipts)
        matches / does not match the balance on their latest bill - same test as Reports &rarr; Bill Summary Update. Each society's check takes a few seconds and is saved for 6 hours.
    </div>
</div>
<script>
(function () {
    var C = window.BILL_MONITOR;
    var MN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var $ = function (id) { return document.getElementById(id); };
    var state = { fy: null, month: null, data: null, open: {}, recon: {}, running: false, stop: false };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function num(n) { return Number(n || 0).toLocaleString('en-IN'); }
    function get(url, cb) {
        var x = new XMLHttpRequest();
        x.open('GET', url);
        x.onload = function () { var d = null; try { d = JSON.parse(x.responseText); } catch (e) {} cb(d); };
        x.onerror = function () { cb(null); };
        x.send();
    }
    function post(url, params, cb) {
        var body = [];
        for (var k in params) {
            if (params[k] instanceof Array) {
                for (var i = 0; i < params[k].length; i++) { body.push(encodeURIComponent(k) + '[]=' + encodeURIComponent(params[k][i])); }
            } else { body.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k])); }
        }
        var x = new XMLHttpRequest();
        x.open('POST', url);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (C.csrf) { x.setRequestHeader('X-CSRF-TOKEN', C.csrf); }
        x.onload = function () { var d = null; try { d = JSON.parse(x.responseText); } catch (e) {} cb(d); };
        x.onerror = function () { cb(null); };
        x.send(body.join('&'));
    }
    function q(url, params) {
        var s = [];
        for (var k in params) { s.push(k + '=' + encodeURIComponent(params[k])); }
        return url + (url.indexOf('?') < 0 ? '?' : '&') + s.join('&');
    }
    function yearOf(month) {
        var label = $('bm_fy').options[$('bm_fy').selectedIndex];
        var start = label ? parseInt(String(label.text).substr(0, 4), 10) : 0;
        return month >= 4 ? start : start + 1;
    }
    function monthLabel(m) { return MN[m] + ' ' + yearOf(m); }

    function initYears() {
        var sel = $('bm_fy');
        var html = '';
        for (var i = 0; i < C.years.length; i++) {
            html += '<option value="' + esc(C.years[i].id) + '">' + esc(C.years[i].label) + '</option>';
        }
        sel.innerHTML = html;
        // default: the financial year that contains today
        var today = new Date(), fyStart = today.getMonth() >= 3 ? today.getFullYear() : today.getFullYear() - 1;
        for (var j = 0; j < sel.options.length; j++) {
            if (parseInt(sel.options[j].text.substr(0, 4), 10) === fyStart) { sel.selectedIndex = j; break; }
        }
        state.fy = sel.value;
        loadMonths(true);
    }

    function loadMonths(pickDefault) {
        $('bm_months').innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
        get(q(C.months, { fy: state.fy }), function (rows) {
            rows = rows || [];
            var html = '', opts = '';
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                html += '<tr data-m="' + r.month + '" style="cursor:pointer;" class="bm_mrow"><td>' + esc(monthLabel(r.month)) + '</td><td class="text-right">' + num(r.societies) + '</td><td class="text-right">' + num(r.bills) + '</td></tr>';
                opts += '<option value="' + r.month + '">' + esc(monthLabel(r.month)) + '</option>';
            }
            $('bm_months').innerHTML = html || '<tr><td colspan="3">No bills generated in this year</td></tr>';
            $('bm_month').innerHTML = opts;
            if (rows.length) {
                var want = (new Date().getMonth() + 1), pick = rows[rows.length - 1].month;
                if (pickDefault) {
                    for (var k = 0; k < rows.length; k++) { if (rows[k].month === want) { pick = want; } }
                }
                $('bm_month').value = pick;
                loadMonth();
            } else {
                state.data = null;
                render();
            }
        });
    }

    function loadMonth() {
        state.month = $('bm_month').value;
        state.open = {};
        get(q(C.month, { fy: state.fy, month: state.month }), function (d) {
            state.data = d;
            render();
        });
    }

    function sums() {
        var d = state.data, o = { correct: 0, wrong: 0, socOk: 0, socBad: 0, socErr: 0, unchecked: 0 };
        if (!d) { return o; }
        for (var i = 0; i < d.societies.length; i++) {
            var c = d.societies[i].check;
            if (!c) { o.unchecked++; } else if (c.error) { o.socErr++; } else {
                o.correct += c.correct; o.wrong += c.wrong;
                if (c.wrong === 0) { o.socOk++; } else { o.socBad++; }
            }
        }
        return o;
    }

    function tile(label, value, color) {
        return '<div style="flex:1 1 150px;min-width:140px;border-left:4px solid ' + color + ';background:#f7f7f7;padding:8px 12px;">' +
            '<div style="font-size:20px;font-weight:700;">' + value + '</div><div style="font-size:11px;color:#666;text-transform:uppercase;">' + label + '</div></div>';
    }

    function render() {
        var d = state.data, s = sums();
        if (!d) {
            $('bm_tiles').innerHTML = '';
            $('bm_resellers').innerHTML = '<tr><td colspan="5">No data</td></tr>';
            $('bm_societies').innerHTML = '<tr><td colspan="6">No data</td></tr>';
            return;
        }
        $('bm_tiles').innerHTML =
            tile('Societies generated bills', num(d.totalSocieties), '#e8a600') +
            tile('Resellers', num(d.resellerCount), '#c8102e') +
            tile('Total bills', num(d.totalBills), '#1d4ea1') +
            tile('Correct bills (members)', num(s.correct), '#2e9e57') +
            tile('Wrong bills (members)', num(s.wrong), '#d9341f') +
            tile('Societies with errors', num(s.socBad) + ' <span style="font-size:12px;color:#888;">/ ' + num(s.socOk) + ' ok' + (s.unchecked ? ', ' + s.unchecked + ' not checked' : '') + (s.socErr ? ', ' + s.socErr + ' failed' : '') + '</span>', '#d9341f');

        // reseller-wise
        var rh = '';
        for (var i = 0; i < d.resellers.length; i++) {
            var r = d.resellers[i], ok = 0, bad = 0, un = 0;
            for (var j = 0; j < r.societyIds.length; j++) {
                var c = findSociety(r.societyIds[j]).check;
                if (!c || c.error) { un++; } else { ok += c.correct; bad += c.wrong; }
            }
            rh += '<tr><td>' + esc(r.name) + '</td><td class="text-right">' + num(r.societies) + '</td><td class="text-right">' + num(r.bills) +
                '</td><td class="text-right" style="color:#2e7d32;">' + (un === r.societyIds.length ? '-' : num(ok)) +
                '</td><td class="text-right" style="color:#c62828;font-weight:600;">' + (un === r.societyIds.length ? '-' : num(bad)) + '</td></tr>';
        }
        $('bm_resellers').innerHTML = rh || '<tr><td colspan="5">No bills in this month</td></tr>';

        // society-wise
        var f = $('bm_filter').value, sh = '', n = 0;
        for (var a = 0; a < d.societies.length; a++) {
            var so = d.societies[a], ch = so.check;
            if (f === 'wrong' && !(ch && !ch.error && ch.wrong > 0)) { continue; }
            if (f === 'unchecked' && ch) { continue; }
            n++;
            var status;
            if (!ch) { status = '<span style="color:#999;">not checked</span>'; }
            else if (ch.error) { status = '<span style="color:#c62828;">failed: ' + esc(ch.error) + '</span>'; }
            else {
                status = '<span style="color:#2e7d32;">' + num(ch.correct) + ' correct</span> | <span style="color:' + (ch.wrong ? '#c62828' : '#2e7d32') + ';font-weight:600;">' + num(ch.wrong) + ' wrong</span>' +
                    ' <span style="color:#aaa;font-size:11px;">(' + esc(ch.checkedAt) + ')</span>';
            }
            var btn = (ch && !ch.error && ch.wrong > 0) ? '<a href="#" class="bm_toggle" data-id="' + so.id + '">' + (state.open[so.id] ? 'Hide' : 'Errors') + '</a> &nbsp;' : '';
            btn += '<a href="#" class="bm_one" data-id="' + so.id + '">' + (ch ? 'Re-check' : 'Check') + '</a> &nbsp;<a href="#" class="bm_recon_open" data-id="' + so.id + '">' + (state.recon[so.id] && state.recon[so.id].open ? 'Close reconcile' : 'Reconcile') + '</a>';
            sh += '<tr><td>' + n + '</td><td>' + esc(so.name) + '</td><td>' + (so.resellers.length ? esc(so.resellers.join(', ')) : '<span style="color:#999;">Direct</span>') +
                '</td><td class="text-right">' + num(so.bills) + '</td><td>' + status + '</td><td style="white-space:nowrap;">' + btn + '</td></tr>';
            if (state.recon[so.id] && state.recon[so.id].open) { sh += reconPanel(so); }
            if (state.open[so.id] && ch && ch.rows) {
                sh += '<tr><td></td><td colspan="5"><table class="table table-bordered" style="width:100%;font-size:11px;margin:0;background:#fffbe6;"><thead><tr><th>Flat</th><th>Member</th><th class="text-right">Closing (calculated)</th><th class="text-right">Opening (last bill)</th><th class="text-right">Difference</th></tr></thead><tbody>';
                for (var w = 0; w < ch.rows.length; w++) {
                    var rw = ch.rows[w];
                    sh += '<tr><td>' + esc(rw.flat) + '</td><td>' + esc(rw.member) + '</td><td class="text-right">' + esc(rw.closing) + '</td><td class="text-right">' + esc(rw.opening) + '</td><td class="text-right" style="color:#c62828;">' + esc(rw.difference) + '</td></tr>';
                }
                sh += '</tbody></table></td></tr>';
            }
        }
        $('bm_societies').innerHTML = sh || '<tr><td colspan="6">Nothing to show</td></tr>';
    }

    function findSociety(id) {
        for (var i = 0; i < state.data.societies.length; i++) { if (state.data.societies[i].id === id) { return state.data.societies[i]; } }
        return { check: null };
    }

    /* ---- Member Balance Reconciliation (Check All & Select Wrong / Preview / Confirm & Update) ---- */
    function fmt(n) { return Number(n || 0).toFixed(2); }
    function reconState(id) {
        if (!state.recon[id]) { state.recon[id] = { open: false, members: null, results: {}, checked: {}, previewed: [], status: '', busy: false }; }
        return state.recon[id];
    }
    function checkedIds(r) {
        var ids = [];
        for (var i = 0; r.members && i < r.members.length; i++) { if (r.checked[r.members[i].id]) { ids.push(r.members[i].id); } }
        return ids;
    }
    function reconPanel(so) {
        var r = reconState(so.id), h = '<tr class="bm_recon_row"><td></td><td colspan="5"><div style="border:1px solid #ccc;background:#fafafa;padding:10px;">';
        h += '<div style="text-align:center;font-weight:600;margin-bottom:6px;">Member Balance Reconciliation (Current Financial Year vs. Last Bill)</div>';
        h += '<div style="margin-bottom:6px;"><label style="margin-right:8px;"><input type="checkbox" class="bm_r_all" data-id="' + so.id + '"> Select All Members</label>' +
            '<button type="button" class="bm_r_scan" data-id="' + so.id + '"' + (r.busy ? ' disabled' : '') + ' style="padding:4px 10px;">Check All &amp; Select Wrong</button> ' +
            '<button type="button" class="bm_r_preview" data-id="' + so.id + '"' + (r.busy ? ' disabled' : '') + ' style="padding:4px 10px;background:#1d4ea1;color:#fff;border:0;">Preview Reconciliation</button> ' +
            '<button type="button" class="bm_r_apply" data-id="' + so.id + '"' + (r.busy || !r.previewed.length ? ' disabled' : '') + ' style="padding:4px 10px;background:#2e9e57;color:#fff;border:0;">Confirm &amp; Update Selected</button></div>';
        h += '<div style="color:#888;font-size:11px;margin-bottom:6px;">"Check All &amp; Select Wrong" scans every member and ticks only the ones whose balance does not match - nothing is saved by this step. "Preview Reconciliation" computes the new Tax/Interest/Principal for whatever is ticked, without saving. Review, then Confirm to save.</div>';
        h += '<div>' + r.status + '</div>';
        if (r.members) {
            h += '<div style="display:flex;flex-wrap:wrap;margin-bottom:8px;">';
            for (var i = 0; i < r.members.length; i++) {
                var m = r.members[i];
                h += '<label style="flex:0 0 25%;min-width:200px;font-weight:normal;margin:0 0 3px;"><input type="checkbox" class="bm_r_cb" data-id="' + so.id + '" value="' + m.id + '"' + (r.checked[m.id] ? ' checked' : '') + '> ' + esc(m.label) + '</label>';
            }
            h += '</div>';
        }
        var rows = '';
        if (r.members) {
            for (var j = 0; j < r.members.length; j++) {
                var mm = r.members[j], res = r.results[mm.id];
                if (!res) { continue; }
                if (!res.success) { rows += '<tr><td>' + esc(mm.label) + '</td><td colspan="9">' + esc(res.error) + '</td></tr>'; continue; }
                var c = res.current, n = res.computed, diff = n.balance_amount - c.balance_amount;
                rows += '<tr><td>' + esc(mm.label) + '</td><td class="text-right">' + fmt(c.tax_balance) + '</td><td class="text-right">' + fmt(c.interest_balance) + '</td><td class="text-right">' + fmt(c.principal_balance) +
                    '</td><td class="text-right">' + fmt(c.balance_amount) + '</td><td class="text-right">' + fmt(n.tax_balance) + '</td><td class="text-right">' + fmt(n.interest_balance) + '</td><td class="text-right">' + fmt(n.principal_balance) +
                    '</td><td class="text-right">' + fmt(n.balance_amount) + '</td><td class="text-right" style="background:yellow;">' + fmt(diff) + '</td></tr>';
            }
        }
        h += '<table class="table table-bordered" style="width:100%;font-size:11px;margin:0;"><thead><tr><th>Member</th><th class="text-right">Current Tax</th><th class="text-right">Current Interest</th><th class="text-right">Current Principal</th><th class="text-right">Current Balance</th><th class="text-right">New Tax</th><th class="text-right">New Interest</th><th class="text-right">New Principal</th><th class="text-right">New Balance</th><th class="text-right">Diff</th></tr></thead><tbody>' + (rows || '<tr><td colspan="10" style="color:#999;">Nothing scanned yet</td></tr>') + '</tbody></table>';
        return h + '</div></td></tr>';
    }

    // calls the reconcile endpoint for ids, 8 members at a time (a big society never hits the server time limit)
    function reconCall(socId, ids, save, done) {
        var merged = {}, i = 0, size = 8;
        (function next() {
            if (i >= ids.length) { done({ success: true, results: merged }); return; }
            post(C.reconcile, { society_id: socId, fy: state.fy, save: save ? 1 : 0, member_ids: ids.slice(i, i + size) }, function (resp) {
                if (!resp || !resp.success) { done(resp || { success: false, error: 'No response from server' }); return; }
                for (var k in resp.results) { merged[k] = resp.results[k]; }
                i += size;
                next();
            });
        })();
    }
    function reconRun(socId, scanAll) {
        var r = reconState(socId);
        if (r.busy) { return; }
        var go = function (ids) {
            if (!ids.length) { r.status = '<div style="color:#a94442;">Tick at least one member first.</div>'; render(); return; }
            r.busy = true; r.status = '<div style="color:#555;">' + (scanAll ? 'Scanning ' : 'Previewing ') + ids.length + ' member(s)...</div>'; r.previewed = []; render();
            reconCall(socId, ids, false, function (resp) {
                r.busy = false;
                if (!resp.success) { r.status = '<div style="color:#a94442;">' + esc(resp.error || 'Failed') + '</div>'; render(); return; }
                var wrong = 0, okc = 0, failed = 0;
                for (var k in resp.results) {
                    var res = resp.results[k]; r.results[k] = res;
                    if (!res.success) { failed++; r.checked[k] = false; continue; }
                    var isWrong = Math.abs(res.computed.balance_amount - res.current.balance_amount) > 0.01;
                    if (scanAll) { r.checked[k] = isWrong; }
                    if (isWrong) { wrong++; } else { okc++; }
                }
                r.previewed = ids.map(String);
                r.status = '<div><span style="color:#2e7d32;font-weight:600;">' + okc + ' Correct</span> | <span style="color:#c62828;font-weight:600;">' + wrong + ' Wrong</span>' + (failed ? ' | ' + failed + ' failed' : '') + ' - nothing saved yet.</div>';
                render();
            });
        };
        if (r.members && !scanAll) { go(checkedIds(r).map(String)); return; }
        if (r.members) { go(r.members.map(function (m) { return String(m.id); })); return; }
        r.busy = true; r.status = '<div style="color:#555;">Loading members...</div>'; render();
        post(C.reconcile, { society_id: socId, fy: state.fy, list: 1 }, function (resp) {
            r.busy = false;
            if (!resp || !resp.success) { r.status = '<div style="color:#a94442;">Could not load members</div>'; render(); return; }
            r.members = resp.members;
            go(r.members.map(function (m) { return String(m.id); }));
        });
    }
    function reconApply(socId) {
        var r = reconState(socId), ids = checkedIds(r).map(String);
        if (!ids.length) { return; }
        for (var i = 0; i < ids.length; i++) {
            if (r.previewed.indexOf(ids[i]) === -1) { r.status = '<div style="color:#8a6d3b;">Selection changed - please Preview again before confirming.</div>'; render(); return; }
        }
        if (!window.confirm('This will permanently update the last bill balances for ' + ids.length + ' member(s). Continue?')) { return; }
        r.busy = true; r.status = '<div style="color:#555;">Updating...</div>'; render();
        reconCall(socId, ids, true, function (resp) {
            r.busy = false;
            if (!resp.success) { r.status = '<div style="color:#a94442;">' + esc(resp.error || 'Update failed') + '</div>'; render(); return; }
            var okc = 0, failed = [];
            for (var k in resp.results) { if (resp.results[k].success) { okc++; } else { failed.push(k + ': ' + resp.results[k].error); } }
            r.previewed = []; r.results = {}; r.checked = {};
            r.status = '<div style="color:' + (failed.length ? '#8a6d3b' : '#2e7d32') + ';">' + okc + ' member' + (okc === 1 ? '' : 's') + ' updated.' + (failed.length ? ' Failed: ' + esc(failed.join('; ')) : '') + ' Re-checking the society...</div>';
            findSociety(socId).check = null;
            render();
            checkOne(socId, true, function () { r.status = r.status.replace(' Re-checking the society...', ''); render(); });
        });
    }

    function checkOne(id, refresh, done) {
        var params = { society_id: id, fy: state.fy };
        if (refresh) { params.refresh = 1; }
        get(q(C.check, params), function (res) {
            findSociety(id).check = res || { error: 'No response from server' };
            render();
            done();
        });
    }

    function runChecks(all) {
        if (state.running || !state.data) { return; }
        var queue = [];
        for (var i = 0; i < state.data.societies.length; i++) {
            var s = state.data.societies[i];
            if (all || !s.check || s.check.error) { queue.push(s.id); }
        }
        if (!queue.length) { $('bm_progress').textContent = 'Everything is already checked.'; return; }
        state.running = true; state.stop = false;
        $('bm_stop').style.display = ''; $('bm_check').disabled = true; $('bm_recheck').disabled = true;
        var total = queue.length, done = 0;
        (function next() {
            if (state.stop || !queue.length) {
                state.running = false;
                $('bm_stop').style.display = 'none'; $('bm_check').disabled = false; $('bm_recheck').disabled = false;
                $('bm_progress').textContent = state.stop ? 'Stopped at ' + done + ' / ' + total : 'Done - ' + total + ' societies checked';
                return;
            }
            $('bm_progress').textContent = 'Checking ' + (done + 1) + ' / ' + total + ' ...';
            checkOne(queue.shift(), all, function () { done++; next(); });
        })();
    }

    $('bm_fy').onchange = function () { state.fy = this.value; loadMonths(false); };
    $('bm_month').onchange = loadMonth;
    $('bm_filter').onchange = render;
    $('bm_check').onclick = function () { runChecks(false); };
    $('bm_recheck').onclick = function () { runChecks(true); };
    $('bm_stop').onclick = function () { state.stop = true; };
    $('bm_months').onclick = function (e) {
        var tr = e.target; while (tr && tr.tagName !== 'TR') { tr = tr.parentNode; }
        if (tr && tr.getAttribute('data-m')) { $('bm_month').value = tr.getAttribute('data-m'); loadMonth(); }
    };
    $('bm_societies').onclick = function (e) {
        var a = e.target;
        var rid = parseInt(a.getAttribute ? a.getAttribute('data-id') : '', 10);
        if (a.className === 'bm_recon_open') {
            e.preventDefault(); var rs = reconState(rid); rs.open = !rs.open; render(); return;
        } else if (a.className === 'bm_r_scan') { reconRun(rid, true); return; }
        else if (a.className === 'bm_r_preview') { reconRun(rid, false); return; }
        else if (a.className === 'bm_r_apply') { reconApply(rid); return; }
        else if (a.className === 'bm_r_all') {
            var ra = reconState(rid);
            for (var z = 0; ra.members && z < ra.members.length; z++) { ra.checked[ra.members[z].id] = a.checked; }
            render(); return;
        } else if (a.className === 'bm_r_cb') {
            reconState(rid).checked[a.value] = a.checked; return;
        }
        if (a.className === 'bm_toggle') {
            e.preventDefault(); var id = a.getAttribute('data-id'); state.open[id] = !state.open[id]; render();
        } else if (a.className === 'bm_one') {
            e.preventDefault();
            if (state.running) { return; }
            a.textContent = 'Checking...'; checkOne(parseInt(a.getAttribute('data-id'), 10), true, function () {});
        }
    };

    initYears();
})();
</script>

@endverbatim
