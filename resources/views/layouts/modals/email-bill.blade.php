@php
    $__sid = Auth::id();
    $__emailBuildings = \App\Models\Building::where('society_id', $__sid)->where('status',1)->orderBy('building_name')->get();
@endphp
<div id="emailBillModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:700px;">
    <div class="modal-header-bar">Email Bill <span class="modal-close" onclick="closeModal('emailBillModal')">&times;</span></div>
    <div class="modal-body-content">
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Building</label>
                <select class="form-control" id="eb_building_id" onchange="loadWings(this.value,'eb_wing_id')">
                    <option value="">Select building</option>
                    @foreach($__emailBuildings as $b)
                    <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Wing Name</label>
                <select class="form-control" id="eb_wing_id"><option value="">Select Wing</option></select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Bill For</label>
                <select class="form-control" id="eb_month">
                    <option value="April">April</option><option value="May">May</option><option value="June">June</option>
                    <option value="July">July</option><option value="August">August</option><option value="September">September</option>
                    <option value="October">October</option><option value="November">November</option><option value="December">December</option>
                    <option value="January">January</option><option value="February">February</option><option value="March">March</option>
                </select>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Flat No</label>
                <input type="text" class="form-control" id="eb_flat_no">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Bill Format</label>
                <select class="form-control" id="eb_format">
                    <option value="1">Format 1</option><option value="2">Format 2</option><option value="3">Format 3</option>
                    <option value="4">Format 4</option><option value="5">Format 5</option>
                </select>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Bill No From</label>
                <input type="text" class="form-control" id="eb_bill_from">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Bill No To</label>
                <input type="text" class="form-control" id="eb_bill_to">
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Receipt From</label>
                <input type="date" class="form-control" id="eb_receipt_from">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Receipt To</label>
                <input type="date" class="form-control" id="eb_receipt_to">
            </div>
        </div>
        <div class="form-group">
            <label>Email Id</label>
            <textarea class="form-control" id="eb_email" rows="2" placeholder="Enter email addresses"></textarea>
        </div>
        <div style="margin-top:16px; display:flex; gap:8px;">
            <button class="btn btn-success" onclick="emailMemberBill()">Send Email</button>
            <button class="btn" style="background:#6c757d; color:#fff;" onclick="closeModal('emailBillModal')">Cancel</button>
        </div>
    </div>
</div>
</div>
