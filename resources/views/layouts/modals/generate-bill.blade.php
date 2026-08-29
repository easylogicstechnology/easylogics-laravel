@php
    $__gbSid = Auth::id();
    $__gbBuildings = \App\Models\Building::where('society_id', $__gbSid)->where('status',1)->orderBy('building_name')->get();
    $__gbFyId = session('fy.year_id');
    $__gbFy = \App\Models\FinancialYearMaster::find($__gbFyId);
    $__gbFyLabel = $__gbFy ? $__gbFy->year : (session('fy.start_year','') . '-' . session('fy.end_year',''));
    $__gbParam = \App\Models\SocietyParameter::where('society_id', $__gbSid)->first();
    $__gbFreqId = $__gbParam->billing_frequency_id ?? 1;
    $__gbFreqMonths = [
        1 => ["4"=>"April","5"=>"May","6"=>"June","7"=>"July","8"=>"August","9"=>"September","10"=>"October","11"=>"November","12"=>"December","1"=>"January","2"=>"February","3"=>"March"],
        2 => ["4"=>"Apr-May","6"=>"Jun-Jul","8"=>"Aug-Sep","10"=>"Oct-Nov","12"=>"Dec-Jan","2"=>"Feb-Mar"],
        3 => ["4"=>"Apr-May-Jun","7"=>"Jul-Aug-Sep","10"=>"Oct-Nov-Dec","1"=>"Jan-Feb-Mar"],
        4 => [],
        5 => ["4"=>"Apr-Sep","10"=>"Oct-Mar"],
        6 => ["4"=>"April-March"],
    ];
    $__gbMonths = $__gbFreqMonths[$__gbFreqId] ?? $__gbFreqMonths[1];
@endphp
<div id="generateBillModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:700px;">
    <div class="modal-header-bar">Generate Bill <span class="modal-close" onclick="closeModal('generateBillModal')">&times;</span></div>
    <div class="modal-body-content">
        <div id="show_bill_notify_error"></div>
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end;">
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Building<span style="color:red;">*</span></label>
                <select class="form-control" id="gb_building_id" onchange="loadWings(this.value,'gb_wing_id')">
                    <option value="">Select building</option>
                    @foreach($__gbBuildings as $b)
                    <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Wing Name</label>
                <select class="form-control" id="gb_wing_id"><option value="">Select Wing</option></select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Bill For<span style="color:red;">*</span></label>
                <select class="form-control" id="gb_month">
                    <option value="">Select Month</option>
                    @foreach($__gbMonths as $mId => $mName)
                    <option value="{{ $mId }}">{{ $mName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Year<span style="color:red;">*</span></label>
                <select class="form-control" id="gb_year">
                    <option value="{{ $__gbFyId }}" selected>{{ $__gbFyLabel }}</option>
                </select>
            </div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-top:8px;">
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Bill Date<span style="color:red;">*</span></label>
                <input type="date" class="form-control" id="gb_bill_date" value="{{ date('Y-m-d') }}">
            </div>
            <div class="form-group" style="flex:1; min-width:160px;">
                <label>Due Date <span style="color:red;">*</span></label>
                <input type="date" class="form-control" id="gb_due_date">
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label>Starting Bill#</label>
                <input type="text" class="form-control" id="gb_starting_no">
            </div>
            <div class="form-group" style="flex:1; min-width:120px;">
                <label style="display:flex; align-items:center; gap:4px;">
                    <input type="checkbox" id="gb_supplementary"> Supplementary Bill
                </label>
            </div>
        </div>
        <div style="margin-top:16px; display:flex; gap:8px;">
            <button class="btn btn-success" onclick="generateRegularBill()">Generate Regular Bill</button>
            <button class="btn" style="background:#6c757d; color:#fff;" onclick="closeModal('generateBillModal')">Cancel</button>
        </div>
        <div id="gb_message" style="margin-top:8px;"></div>
    </div>
</div>
</div>
