{{-- The three modals of Vendor Detail / Vendor Billing (CakePHP vendor_detail_modal, vendor_billing_modal, quick_ledger_head_modal) --}}
<style>
    .vf-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
    .vf-col { flex: 1; min-width: 180px; }
    .vf-col label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
    .vf-col .required { color: #e74c3c; }
    #vendor_billing_modal .form-control.text-right { text-align: right; }
    .vf-actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
</style>

<div id="vendor_detail_modal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:820px;">
        <div class="modal-header-bar">Vendor Detail <span class="modal-close" onclick="cancelVendorDetailModal();">&times;</span></div>
        <div class="modal-body-content">
            <div id="vendor_detail_modal_body"><!-- populated via AJAX: openVendorDetailModal() --></div>
            <div class="vf-actions">
                <button type="button" class="btn btn-success" onclick="saveVendorDetail();">Save</button>
                <a class="btn btn-success" href="javascript:void(0);" onclick="openVendorDetailModal($('#vendor_ledger_head_id').val());">Refresh</a>
                <a class="btn btn-success" href="javascript:void(0);" onclick="cancelVendorDetailModal();">Cancel</a>
            </div>
        </div>
    </div>
</div>

<div id="vendor_billing_modal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:1100px;">
        <div class="modal-header-bar">Vendor Billings <span class="modal-close" onclick="cancelVendorBillingModal();">&times;</span></div>
        <div class="modal-body-content">
            <div id="vendor_billing_modal_body"><!-- populated via AJAX: openVendorBillingModal() --></div>
            <div class="vf-actions">
                <button type="button" class="btn btn-success" onclick="saveVendorBill();">Save</button>
                <button type="button" class="btn btn-success" onclick="saveVendorBill(true);">Save &amp; Print</button>
                <button type="button" class="btn btn-primary" onclick="printVendorBill();">Print</button>
                <a class="btn btn-success" href="javascript:void(0);" onclick="openVendorBillingModal($('#vendor_bill_id').val());">Refresh</a>
                <a class="btn btn-success" href="javascript:void(0);" onclick="cancelVendorBillingModal();">Cancel</a>
            </div>
        </div>
    </div>
</div>

<div id="quick_ledger_head_modal" class="modal-overlay" style="display:none; z-index:1100;">
    <div class="modal-box" style="max-width:560px;">
        <div class="modal-header-bar">Add New Ledger Head <span class="modal-close" onclick="cancelQuickLedgerHead();">&times;</span></div>
        <div class="modal-body-content">
            <div id="quick_ledger_head_notify_error"></div>
            <form method="post" id="quickLedgerHeadForm" autocomplete="off" onsubmit="return false;">
                <div class="vf-row"><div class="vf-col"><label>Particulars<span class="required">*</span></label><input type="text" class="form-control" id="quick_ledger_title" required></div></div>
                <div class="vf-row">
                    <div class="vf-col"><label>Short Code</label><input type="text" class="form-control" id="quick_ledger_short_code"></div>
                    <div class="vf-col"><label>Opening Balance</label><input type="text" class="form-control" style="text-align:right;" id="quick_ledger_opening_amount" value="0"></div>
                </div>
                <div class="vf-row">
                    <div class="vf-col"><label>Subgroup<span class="required">*</span></label>
                        <select class="form-control" id="quick_ledger_sub_category_id" onchange="onQuickLedgerSubCategoryChange(this.value);" required><option value="">Select</option></select></div>
                </div>
                <div class="vf-row">
                    <div class="vf-col"><label>Category</label><select class="form-control" id="quick_ledger_category_id" disabled><option value="">Select Subgroup first</option></select></div>
                    <div class="vf-col"><label>Group</label><select class="form-control" id="quick_ledger_head_id" disabled><option value="">Select Subgroup first</option></select></div>
                </div>
            </form>
            <div class="vf-actions">
                <button type="button" class="btn btn-success" onclick="saveQuickLedgerHead();">Save</button>
                <a class="btn btn-success" href="javascript:void(0);" onclick="cancelQuickLedgerHead();">Cancel</a>
            </div>
        </div>
    </div>
</div>
