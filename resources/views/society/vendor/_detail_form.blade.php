<form method="post" id="vendorDetailForm" name="vendorDetailForm" autocomplete="off" onsubmit="return false;">
    <div id="vendor_detail_notify_error"></div>

    @if (empty($ledgerHeadId))
        <div class="vf-row" style="align-items:flex-end;">
            <div class="vf-col" style="flex:5;">
                <label>Select Ledger Head<span class="required">*</span></label>
                <select class="form-control" id="vendor_ledger_head_id" name="VendorDetail[ledger_head_id]" onchange="onVendorLedgerHeadChange(this.value);" required>
                    <option value="">Select</option>
                    @foreach ($availableLedgerHeadLists as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vf-col" style="flex:0 0 auto; min-width:0;">
                <button type="button" class="btn btn-success" title="Add New Ledger Head" onclick="openQuickLedgerHeadModal('#vendor_detail_modal');">+</button>
            </div>
        </div>
    @else
        <input type="hidden" id="vendor_ledger_head_id" name="VendorDetail[ledger_head_id]" value="{{ $ledgerHeadId }}">
        <div class="vf-row">
            <div class="vf-col"><label>Vendor / Ledger Head</label><input type="text" class="form-control" value="{{ $ledgerHeadTitle }}" readonly></div>
        </div>
    @endif

    <div class="vf-row">
        <div class="vf-col"><label>Contact Person Name</label><input type="text" class="form-control" name="VendorDetail[contact_person_name]" value="{{ $v['contact_person_name'] ?? '' }}"></div>
        <div class="vf-col"><label>Phone Number</label><input type="text" class="form-control" name="VendorDetail[phone_number]" value="{{ $v['phone_number'] ?? '' }}"></div>
    </div>
    <div class="vf-row">
        <div class="vf-col"><label>PAN No</label><input type="text" class="form-control" name="VendorDetail[pan_no]" value="{{ $v['pan_no'] ?? '' }}"></div>
        <div class="vf-col"><label>GST No</label><input type="text" class="form-control" name="VendorDetail[gst_no]" value="{{ $v['gst_no'] ?? '' }}"></div>
    </div>
    <div class="vf-row">
        <div class="vf-col"><label>Company Email Address</label><input type="email" class="form-control" name="VendorDetail[company_email]" value="{{ $v['company_email'] ?? '' }}"></div>
        <div class="vf-col"><label>Comments</label><input type="text" class="form-control" name="VendorDetail[comments]" value="{{ $v['comments'] ?? '' }}"></div>
    </div>
    <div class="vf-row">
        <div class="vf-col"><label>AMC Start date</label><input type="date" class="form-control" name="VendorDetail[amc_start_date]" value="{{ $v['amc_start_date'] ?? '' }}"></div>
        <div class="vf-col"><label>AMC End date</label><input type="date" class="form-control" name="VendorDetail[amc_end_date]" value="{{ $v['amc_end_date'] ?? '' }}"></div>
    </div>
    <div class="vf-row">
        <div class="vf-col" style="flex:4;">
            <label>Select Facility</label>
            <select class="form-control" id="vendor_facility_select" name="VendorDetail[facility_id]" onchange="onVendorFacilitySelectChange(this.value);">
                <option value="">Select</option>
                @foreach ($vendorFacilityLists as $id => $title)
                    <option value="{{ $id }}" @selected(isset($v['facility_id']) && $v['facility_id'] == $id)>{{ $title }}</option>
                @endforeach
                <option value="new">+ Add New Facility</option>
            </select>
            <input type="text" class="form-control" id="vendor_facility_text" style="display:none;" placeholder="Enter new facility name" onkeyup="if(this.value===''){ $('#vendor_facility_select').show(); $(this).hide(); }">
        </div>
        <div class="vf-col" style="flex:1; min-width:100px;">
            <label>CR/DR</label>
            <select class="form-control" name="VendorDetail[cr_dr]">
                <option value="Cr" @selected(($v['cr_dr'] ?? '') == 'Cr')>Cr</option>
                <option value="Dr" @selected(($v['cr_dr'] ?? '') == 'Dr')>Dr</option>
            </select>
        </div>
    </div>
    <div class="vf-row">
        <div class="vf-col" style="flex:2;"><label>List of Sub Committee</label><textarea class="form-control" name="VendorDetail[sub_committee_list]" rows="3">{{ $v['sub_committee_list'] ?? '' }}</textarea></div>
        <div class="vf-col">
            <label>Rating</label>
            <select class="form-control" name="VendorDetail[rating]">
                <option value="">Select</option>
                @foreach (['Excellent', 'Very Good', 'Good', 'Average', 'Poor'] as $ratingOption)
                    <option value="{{ $ratingOption }}" @selected(($v['rating'] ?? '') == $ratingOption)>{{ $ratingOption }}</option>
                @endforeach
            </select>
        </div>
    </div>
</form>
