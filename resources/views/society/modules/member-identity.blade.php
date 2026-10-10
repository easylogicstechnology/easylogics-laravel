@extends('layouts.app')
@section('title', 'Member Identity')
@section('content')
<div class="page-header">
    <h2>Member Identities Lists</h2>
</div>

<div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px;">
    <a href="{{ route('society.downloadMemberTemplate') }}" class="btn btn-success btn-sm">&#11015; Download Sample Template File</a>
    <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('downloadOBModal').style.display='flex'">&#11015; Download Member Opening Balance</button>
    <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('uploadOBModal').style.display='flex'">&#11014; Upload Member Opening Balance</button>
    <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('uploadCSVModal').style.display='flex'">&#11014; Upload Member Identities CSV</button>
    <a href="{{ route('society.addMember') }}" class="btn btn-sm" style="background:#333;color:#fff;">+ Add Member</a>
    <a href="#" class="btn btn-sm" style="background:#333;color:#fff;">+ Member Identification Lists</a>
</div>
<div style="margin-bottom:16px;">
    <form method="POST" action="{{ route('society.deleteAllMembers') }}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete ALL members? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">&#10006; Delete All Members</button>
    </form>
</div>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <div>
            Show
            <select id="entriesCount" onchange="filterTable()" style="padding:4px 8px; border:1px solid #ddd; border-radius:3px;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100" selected>100</option>
            </select>
            entries
        </div>
        <div>
            Search: <input type="text" id="searchBox" onkeyup="filterTable()" style="padding:4px 8px; border:1px solid #ddd; border-radius:3px; width:200px;">
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table id="memberTable">
            <thead>
                <tr>
                    <th style="width:40px; cursor:pointer;" onclick="sortTable(0)">#</th>
                    <th style="cursor:pointer;" onclick="sortTable(1)">Member Name</th>
                    <th style="cursor:pointer;" onclick="sortTable(2)">Flat/Shop No</th>
                    <th style="cursor:pointer;" onclick="sortTable(3)">Building Name</th>
                    <th style="cursor:pointer;" onclick="sortTable(4)">Wing Name</th>
                    <th style="cursor:pointer;" onclick="sortTable(5)">Floor No</th>
                    <th style="cursor:pointer; text-align:right;" onclick="sortTable(6)">Opening Principal</th>
                    <th style="cursor:pointer; text-align:right;" onclick="sortTable(7)">Opening Interest</th>
                    <th style="cursor:pointer; text-align:right;" onclick="sortTable(8)">Opening Tax</th>
                    <th style="width:70px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $i => $m)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $m->member_prefix }} {{ $m->member_name }}</td>
                    <td>{{ $m->flat_no }}</td>
                    <td>{{ $m->building->building_name ?? '-' }}</td>
                    <td>{{ $m->wing->wing_name ?? '-' }}</td>
                    <td>{{ $m->floor_no }}</td>
                    <td style="text-align:right;">{{ number_format($m->op_principal ?? 0, 2) }}</td>
                    <td style="text-align:right;">{{ number_format($m->op_interest ?? 0, 2) }}</td>
                    <td style="text-align:right;">{{ number_format($m->op_tax ?? 0, 2) }}</td>
                    <td style="text-align:center;">
                        <a href="{{ route('society.addMember', $m->id) }}" title="Edit" style="color:#f39c12; margin-right:6px; text-decoration:none; font-size:16px;">&#9998;</a>
                        <form method="POST" action="{{ route('society.deleteMember', $m->id) }}" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete" style="color:#e74c3c; background:none; border:none; cursor:pointer; font-size:16px;">&#10006;</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center; color:#999;">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="paginationInfo" style="margin-top:12px; font-size:13px; color:#666;"></div>
</div>

{{-- Download Opening Balance Modal: only the ticked fields are downloaded --}}
<div id="downloadOBModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:420px; max-width:90%;">
        <h3 style="margin-bottom:12px; font-size:16px;">Select fields to download</h3>
        <form method="GET" action="{{ route('society.downloadMemberOpeningBalance') }}" onsubmit="if(!this.querySelector('input[name=&quot;fields[]&quot;]:checked')){alert('Please select at least one field.');return false;} document.getElementById('downloadOBModal').style.display='none'; return true;">
            <p style="font-size:12px; color:#999; margin-bottom:12px;">Member ID and Building are always included (with Flat No, Wing and Name as reference when not ticked). Only the ticked fields are downloaded, and only those fields are updated when you upload the file back.</p>
            @foreach (['name' => 'Member Name', 'flat' => 'Flat/Shop No', 'wing' => 'Wing Name', 'mobile' => 'Mobile No', 'email' => 'Email Address', 'area' => 'Area', 'op_principal' => 'Opening Principal', 'op_interest' => 'Opening Interest', 'op_tax' => 'Opening Tax', 'op_bill_date' => 'Opening Bill Date', 'op_bill_due_date' => 'Opening Bill Due Date'] as $key => $label)
                <label style="display:block; margin-bottom:8px; font-size:14px;"><input type="checkbox" name="fields[]" value="{{ $key }}" checked> {{ $label }}</label>
            @endforeach
            <div style="display:flex; gap:8px; margin-top:12px;">
                <button type="submit" class="btn btn-success">Download</button>
                <button type="button" class="btn btn-sm" style="background:#999; color:#fff;" onclick="document.getElementById('downloadOBModal').style.display='none'">Close</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload Opening Balance Modal --}}
<div id="uploadOBModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:450px; max-width:90%;">
        <h3 style="margin-bottom:16px; font-size:16px;">Upload Member Opening Balance</h3>
        <form method="POST" action="{{ route('society.uploadMemberOpeningBalance') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Select CSV File</label>
                <input type="file" name="member_csv" class="form-control" accept=".csv" required>
            </div>
            <p style="font-size:12px; color:#999; margin-bottom:12px;">Download the file first (choose the fields), edit only those columns, then upload. Only the columns in the file are updated; blank cells are skipped.</p>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-success">Upload</button>
                <button type="button" class="btn btn-sm" style="background:#999; color:#fff;" onclick="document.getElementById('uploadOBModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload Member CSV Modal --}}
<div id="uploadCSVModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:500px; max-width:90%;">
        <h3 style="margin-bottom:16px; font-size:16px;">Upload Member Identities CSV</h3>
        <form method="POST" action="{{ route('society.uploadMemberCsv') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Building <span class="required">*</span></label>
                <select name="building_id" class="form-control" id="csvBuildingSelect" required>
                    <option value="">Select building</option>
                    @foreach($buildings as $bId => $bName)
                    <option value="{{ $bId }}">{{ $bName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Wing Name</label>
                <select name="wing_id" class="form-control" id="csvWingSelect">
                    <option value="">Select wing</option>
                    @foreach($wings as $w)
                    <option value="{{ $w->id }}" data-building="{{ $w->building_id }}">{{ $w->wing_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Client CSV</label>
                <input type="file" name="member_csv" class="form-control" accept=".csv" required>
            </div>
            <p style="font-size:11px; color:#999; margin-bottom:12px;">CSV columns: Member Prefix, Member Name, Member email, Contact no, Floor No, Unit Type(R,C), Flat No, Area, Carpet, Commercial, Residential, Terrace, Opening Principal, Opening Interest, Opening Tax, Opening Bill Date, Opening Bill Due Date, Member GSTIN No, Member Parking No</p>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-success">Upload CSV</button>
                <button type="button" class="btn btn-sm" style="background:#999; color:#fff;" onclick="document.getElementById('uploadCSVModal').style.display='none'">Close</button>
            </div>
        </form>
    </div>
</div>

<script>
var allRows = [];
var currentPage = 1;

(function() {
    var table = document.getElementById('memberTable');
    var tbody = table.querySelector('tbody');
    var rows = tbody.querySelectorAll('tr');
    for (var i = 0; i < rows.length; i++) {
        allRows.push(rows[i]);
    }
    filterTable();
})();

function filterTable() {
    var search = document.getElementById('searchBox').value.toLowerCase();
    var limit = parseInt(document.getElementById('entriesCount').value);
    var tbody = document.getElementById('memberTable').querySelector('tbody');
    var filtered = [];

    for (var i = 0; i < allRows.length; i++) {
        var text = allRows[i].textContent.toLowerCase();
        if (!search || text.indexOf(search) !== -1) {
            filtered.push(allRows[i]);
        }
    }

    tbody.innerHTML = '';
    var shown = 0;
    for (var j = 0; j < filtered.length && j < limit; j++) {
        tbody.appendChild(filtered[j]);
        shown++;
    }

    if (shown === 0) {
        var emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="10" style="text-align:center; color:#999;">No matching records found.</td>';
        tbody.appendChild(emptyRow);
    }

    document.getElementById('paginationInfo').textContent =
        'Showing ' + (shown > 0 ? 1 : 0) + ' to ' + shown + ' of ' + filtered.length + ' entries' +
        (search ? ' (filtered from ' + allRows.length + ' total)' : '');
}

// Building-Wing cascade for CSV upload modal
var csvBuildingSelect = document.getElementById('csvBuildingSelect');
if (csvBuildingSelect) {
    csvBuildingSelect.addEventListener('change', function() {
        var buildingId = this.value;
        var wingSelect = document.getElementById('csvWingSelect');
        var options = wingSelect.querySelectorAll('option');
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (!opt.value) continue;
            opt.style.display = (!buildingId || opt.getAttribute('data-building') === buildingId) ? '' : 'none';
        }
        wingSelect.value = '';
    });
}

var sortDir = {};
function sortTable(colIndex) {
    sortDir[colIndex] = !sortDir[colIndex];
    var dir = sortDir[colIndex] ? 1 : -1;

    allRows.sort(function(a, b) {
        var aVal = a.cells[colIndex] ? a.cells[colIndex].textContent.trim() : '';
        var bVal = b.cells[colIndex] ? b.cells[colIndex].textContent.trim() : '';
        var aNum = parseFloat(aVal.replace(/,/g, ''));
        var bNum = parseFloat(bVal.replace(/,/g, ''));
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return (aNum - bNum) * dir;
        }
        return aVal.localeCompare(bVal) * dir;
    });

    filterTable();
}
</script>
@endsection
