@php
    $__sid = Auth::id();
    $__ledgerHeads = \App\Models\SocietyLedgerHead::where('society_id', $__sid)->where('status',1)->orderBy('title')->get();
    $__subGroups = \App\Models\SocietyHeadSubCategory::where('society_id', $__sid)->where('status',1)->orderBy('title')->pluck('title','id');
    $__categories = \App\Models\AccountCategory::where('status',1)->orderBy('title')->pluck('title','id');
@endphp
<div id="ledgerHeadsModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:1050px;">
    <div class="modal-header-bar">Account Master / Ledger Heads <span class="modal-close" onclick="closeModal('ledgerHeadsModal')">&times;</span></div>
    <div class="modal-body-content">
        <div style="display:flex; gap:16px;">
            <div style="flex:1;">
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
                    <div class="form-group" style="flex:2; min-width:200px;">
                        <label>Particulars*</label>
                        <select class="form-control" id="lh_select" onchange="loadLedgerHeadDetails(this.value)">
                            <option value="">Select Ledger Head</option>
                            @foreach($__ledgerHeads as $lh)
                            <option value="{{ $lh->id }}">{{ $lh->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="min-width:80px;">
                        <label>Sr</label>
                        <input type="text" class="form-control" id="lh_sr" readonly style="width:80px;">
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:end;">
                    <div class="form-group" style="flex:1;">
                        <label>Short Code*</label>
                        <input type="text" class="form-control" id="lh_short_code">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Opening Balance *</label>
                        <input type="text" class="form-control" id="lh_opening_balance" value="0">
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <div class="form-group" style="flex:1;">
                        <label>Subgroup*</label>
                        <select class="form-control" id="lh_subgroup">
                            <option value="">Select</option>
                            @foreach($__subGroups as $sgId => $sgTitle)
                            <option value="{{ $sgId }}">{{ $sgTitle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Group*</label>
                        <select class="form-control" id="lh_group"><option value="">Select</option></select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Category*</label>
                        <select class="form-control" id="lh_category" onchange="loadAccountHeads(this.value)">
                            <option value="">Select</option>
                            @foreach($__categories as $cId => $cTitle)
                            <option value="{{ $cId }}">{{ $cTitle }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div style="min-width:220px;">
                <b>Bill Tariff Details</b>
                <div style="margin-top:8px;">
                    <label><input type="checkbox" id="lh_is_bill_charges"> Is this account a part of Bill Charges</label><br>
                    <label><input type="checkbox" id="lh_is_tax_applicable"> Service Tax/GST Applicable ?</label><br>
                    <label><input type="checkbox" id="lh_is_rebate_applicable"> Rebate Applicable ?</label><br>
                    <label><input type="checkbox" id="lh_is_interest_free"> Interest Free?</label>
                </div>
                <div style="margin-top:16px; display:flex; gap:8px;">
                    <button class="btn btn-primary" onclick="updateLedgerHead()">Update</button>
                    <button class="btn" style="background:#6c757d; color:#fff;" onclick="closeModal('ledgerHeadsModal')">Cancel</button>
                    <button class="btn" style="background:#5bc0de; color:#fff;" onclick="printLedgerHead()">Print Friendly</button>
                </div>
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:end; margin-top:12px; border-top:1px solid #ddd; padding-top:12px;">
            <div class="form-group" style="margin:0;">
                <label>From*</label>
                <input type="date" class="form-control" id="lh_from_date" style="width:150px;">
            </div>
            <div class="form-group" style="margin:0;">
                <label>To*</label>
                <input type="date" class="form-control" id="lh_to_date" style="width:150px;">
            </div>
            <div class="form-group" style="margin:0;">
                <label>Particulars*</label>
                <input type="text" class="form-control" id="lh_filter_particulars" style="width:180px;">
            </div>
            <div class="form-group" style="margin:0;">
                <label>Amount*</label>
                <input type="text" class="form-control" id="lh_filter_amount" readonly style="width:100px;">
            </div>
        </div>
        <div style="max-height:300px; overflow-y:auto; margin-top:8px;">
            <table>
                <thead><tr style="background:#e9ecef;"><th>DATE</th><th>PARTICULAR</th><th>DR.AMOUNT</th><th>CR.AMOUNT</th><th>BALANCE</th></tr></thead>
                <tbody id="lh_ledger_table"></tbody>
            </table>
        </div>
    </div>
</div>
</div>
