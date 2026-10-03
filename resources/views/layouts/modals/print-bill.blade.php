@php
    $__sid = Auth::id();
    $__printBuildings = \App\Models\Building::where('society_id', $__sid)->where('status',1)->orderBy('building_name')->get();
@endphp
<div id="printBillModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:700px;">
    <div class="modal-header-bar">Print Bill <span class="modal-close" onclick="closeModal('printBillModal')">&times;</span></div>
    <div class="modal-body-content">
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Building</label>
                <select class="form-control" id="pb_building_id" onchange="loadWings(this.value,'pb_wing_id')">
                    <option value="">Select building</option>
                    @foreach($__printBuildings as $b)
                    <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Wing Name</label>
                <select class="form-control" id="pb_wing_id"><option value="">Select Wing</option></select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Bill For</label>
                <select class="form-control" id="pb_month">
                    <option value="4">April</option><option value="5">May</option><option value="6">June</option>
                    <option value="7">July</option><option value="8">August</option><option value="9">September</option>
                    <option value="10">October</option><option value="11">November</option><option value="12">December</option>
                    <option value="1">January</option><option value="2">February</option><option value="3">March</option>
                </select>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Flat No</label>
                <input type="text" class="form-control" id="pb_flat_no">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Bill Format</label>
                <select class="form-control" id="pb_format">
                    <option value="1">Format 1</option><option value="2">Format 2</option><option value="3">Format 3</option>
                    <option value="4">Format 4</option><option value="5">Format 5</option>
                </select>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Bill No From</label>
                <input type="text" class="form-control" id="pb_bill_from">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Bill No To</label>
                <input type="text" class="form-control" id="pb_bill_to">
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Receipt From</label>
                <input type="date" class="form-control" id="pb_receipt_from">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Receipt To</label>
                <input type="date" class="form-control" id="pb_receipt_to">
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
            <div class="form-group" style="flex:1;">
                <label>Unit Type</label>
                <select class="form-control" id="pb_unit_type">
                    <option value="Unit No">Unit Number</option><option value="Flat-Shop">Flat-Shop</option>
                    <option value="Flat No">Flat Number</option><option value="Shop No">Shop Number</option>
                </select>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Bill Type</label>
                <select class="form-control" id="pb_bill_type">
                    <option value="reg">Regular</option><option value="sup">Supplementary</option>
                </select>
            </div>
        </div>
        <div style="margin-top:16px; display:flex; gap:8px;">
            <button class="btn btn-success" onclick="printMemberBill()">Print Bill</button>
            <button class="btn" style="background:#6c757d; color:#fff;" onclick="closeModal('printBillModal')">Cancel</button>
        </div>
    </div>
</div>
</div>
