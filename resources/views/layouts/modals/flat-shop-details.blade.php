@php
    $__sid = Auth::id();
    $__members = \App\Models\Member::where('society_id', $__sid)->where('status',1)->orderBy('flat_no')->get();
    $__buildings = \App\Models\Building::where('society_id', $__sid)->where('status',1)->orderBy('building_name')->get();
    $__wings = \App\Models\Wing::where('society_id', $__sid)->where('status',1)->get();
@endphp
<style>
/* modal window society_flat_shop_details form field design */
.modal-flat-shop-form-box{border:0px solid red;height:auto;overflow:hidden;display:block;}
.modal-flat-shop-form-field-box{border:0px solid #000;margin-bottom:2px;overflow:hidden;padding:2px;width:100%;float:left;}
.modal-flat-shop-form-box-label{border:0px solid green;color:#000;float:left;text-align:left;}
.modal-flat-shop-form-box-field-85{width:85%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-70{width:70%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-60{width:60%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-50{width:50%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-40{width:40%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-30{width:30%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-20{width:20%;color:#333;float:left;height:30px !important;}
.modal-flat-shop-form-box-field-dropdown{width:65%;color:#333;float:left;height:30px !important;}
.readonly{background:#cfcfcf;border:none;padding:2px;}
.opening-bal{color:#0066cc !important;font-weight:500;}
.line-break{margin-top:10px;margin-bottom:10px;border:1px solid #ccc;border-width:100%;}
.required{color:red;}
#flat_shop_details .modal-header-bar{background:#dc0030;padding:10px;color:#fff;}
#flat_shop_details .modal-header-bar h6{color:#fff;margin:0;display:inline;}
#flat_shop_details .modal-box{overflow:hidden;margin-bottom:20px;position:relative;}
#flat_shop_details .pager1{list-style:none;margin:0;padding:0;}
#flat_shop_details .previous-btn-list{display:block;overflow:hidden;position:absolute;top:250px;left:0px;height:50px;background:#f2b701;border:0px solid green;color:#000;font-weight:900;padding-top:12px;cursor:pointer;text-decoration:none;z-index:10;}
#flat_shop_details .next-btn-list{display:block;overflow:hidden;position:absolute;top:250px;right:0px;height:50px;background:#f2b701;border:0px solid blue;color:#333;font-weight:600;padding-top:12px;cursor:pointer;text-decoration:none;z-index:10;}
#flat_shop_details .previous-btn-list:hover,#flat_shop_details .next-btn-list:hover{background:#e6a800;}
#flat_shop_details .tab-nav{list-style:none;margin:0;padding:0;display:flex;border-bottom:2px solid #ddd;}
#flat_shop_details .tab-nav li{margin:0;}
#flat_shop_details .tab-nav li a{display:block;padding:8px 16px;text-decoration:none;color:#333;background:#f8f8f8;border:1px solid #ddd;border-bottom:none;margin-right:2px;cursor:pointer;}
#flat_shop_details .tab-nav li a.active{background:#fff !important;color:#333 !important;font-weight:bold;border-bottom:2px solid #fff;margin-bottom:-2px;}
#flat_shop_details .tab-content-panel{background:#fff !important;padding:8px;border:1px solid #ddd;border-top:none;}
#flat_shop_details .tab-content-panel .tab-pane{display:none;}
#flat_shop_details .tab-content-panel .tab-pane.active{display:block;}
#flat_shop_details .btn-success{background:#1abc9c;color:#fff;border:none;padding:8px 18px;cursor:pointer;font-size:14px;border-radius:3px;}
#flat_shop_details .btn-success:hover{background:#16a085;}
#flat_shop_details .btn-print{background:#1abc9c;color:#fff;border:none;padding:6px 14px;cursor:pointer;border-radius:3px;float:right;margin-bottom:8px;}
#flat_shop_details .societyinfo-section{border:0px solid red;overflow:hidden;display:none;}
#flat_shop_details .societyinfo-section h5{margin:0;font-size:16px;font-weight:bold;text-align:center;}
#flat_shop_details .societyinfo-section .report-bill{text-align:center;font-size:16px;color:#000000;margin-top:15px;}
#flat_shop_details .report-address-heading{text-align:center;font-size:13px;}
#flat_shop_details .report-bill{text-align:center;font-size:16px;color:#000000;}
#flat_shop_details .building-name{float:left;margin-right:20px;color:#000000;}
#flat_shop_details .building-name span{color:#000000;}
#flat_shop_details .wing-name{float:left;margin-right:10px;color:#000000;}
#flat_shop_details .wing-name span{color:#000000;}
#flat_shop_details .member-name{float:left;margin-right:20px;color:#000000;}
#flat_shop_details .member-name span{color:#000000;}
#flat_shop_details .flat-no{float:left;margin-right:10px;color:#000000;}
#flat_shop_details .flat-no span{color:#000000;}
#flat_shop_details select.fs-select2{width:75% !important;float:left;height:30px !important;color:#333;}
.fs-unit-option:hover{background:#3875d7 !important;color:#fff !important;transition:background .1s;}
.fs-unit-option.active{background:#3875d7;color:#fff;}
#fs_unit_search_input:focus{outline:none;border-color:#3875d7;box-shadow:0 0 3px rgba(56,117,215,.3);}
#flat_shop_details .table th{padding:4px 6px;font-size:13px;}
#flat_shop_details .table td{padding:3px 6px;font-size:13px;}
</style>

<div id="flat_shop_details" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:900px;position:relative;">
    <div class="modal-header-bar">Flat / Shop Details <span class="modal-close" onclick="closeModal('flat_shop_details')">&times;</span></div>

    <div class="pager1">
        <a class="previous-btn-list" id="society_member_previous_button" href="javascript:void(0);" onclick="societyMemberPrevious();" style="display:none;">&lt;&lt;</a>
        <a class="next-btn-list" id="society_member_next_button" href="javascript:void(0);" onclick="societyMemberNext();">&gt;&gt;</a>
    </div>

    <div class="modal-body-content" style="background:#f8f8f8;border:1px solid #f8f8f8;margin:10px;padding:8px;">
        <div id="modal_member_notify_error"></div>
        <form method="post" id="societyMemberDetailsForm" name="societyMemberDetailsForm" autocomplete="off">

            <div class="modal-flat-shop-form-box">
                <div class="row">
                    <div class="col-md-6">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Member Name<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-70" id="society_member_name" name="member_name" value="">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Unit sr.<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="hidden" id="society_member_id" name="member_id" value="0">
                            <input type="hidden" id="society_member_previous" name="society_member_previous" value="0">
                            <input type="hidden" id="society_member_next" name="society_member_next" value="0">
                            <input type="text" class="modal-flat-shop-form-box-field-40 text-right readonly" id="member_serial_no" name="member_serial_no" value="0" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Unit No.&nbsp;&nbsp;</div>
                            <input type="hidden" id="member_unit_no" name="member_unit_no" value="{{ $__members->first()->flat_no ?? '' }}">
                            <div id="fs_unit_dropdown" style="width:75%;float:left;position:relative;">
                                <div id="fs_unit_selected" onclick="fsToggleDropdown();" style="border:1px solid #aaa;height:30px;line-height:28px;padding:0 24px 0 8px;cursor:pointer;background:#fff;font-size:13px;text-align:right;position:relative;overflow:hidden;">
                                    <span id="fs_unit_selected_text">{{ $__members->first()->flat_no ?? '' }}</span>
                                    <span style="position:absolute;right:6px;top:0;font-size:10px;">&#9660;</span>
                                </div>
                            </div>
                            <div id="fs_unit_dropdown_list" style="display:none;position:fixed;z-index:9999;background:#fff;border:1px solid #aaa;box-shadow:0 3px 8px rgba(0,0,0,.2);max-height:240px;overflow:hidden;">
                                <div style="padding:4px;border-bottom:1px solid #ddd;background:#f9f9f9;">
                                    <input type="text" id="fs_unit_search_input" placeholder="Search flat/unit..." style="width:100%;border:1px solid #ccc;padding:4px 8px;font-size:13px;height:28px;box-sizing:border-box;">
                                </div>
                                <div id="fs_unit_options" style="max-height:195px;overflow-y:auto;">
                                    @foreach($__members as $m)
                                    <div class="fs-unit-option" data-value="{{ $m->flat_no }}" data-member-id="{{ $m->id }}" style="padding:5px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;">{{ $m->flat_no }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Building Name<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <select name="building_id" id="member_building_id" onchange="getAllBuildingWings(this.value);" class="modal-flat-shop-form-box-field-dropdown">
                                <option value="">Select building</option>
                                @foreach($__buildings as $b)
                                <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Wing&nbsp;&nbsp;&nbsp;</div>
                            <select class="modal-flat-shop-form-box-field-40 text-right" id="member_wing_id" name="wing_id" style="width:75%;">
                                <option value="">Select Wing</option>
                                @foreach($__wings as $w)
                                <option value="{{ $w->id }}">{{ $w->wing_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Floor No.<span class="required">*</span>&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-60 text-right" id="member_floor_no" name="floor_no" value="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-11">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Unit Type<span class="required">*</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-85" id="member_unit_type" name="unit_type" value="">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-2">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Area<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-50 text-right" id="member_flat_area" name="area" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Carpet<span class="required">*</span>&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-50 text-right" id="member_carpet" name="carpet" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Commercial<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-40 text-right" id="member_commercial" name="commercial" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Residential<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-40 text-right" id="member_residential" name="residential" value="0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Terrace<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-40 text-right" id="member_terrace" name="terrace" value="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Op.Principal<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-40 text-right" id="member_principal" name="member_principal" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Interest<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-50 text-right" id="member_interest" name="member_Interest" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Tax<span class="required">*</span>&nbsp;&nbsp;&nbsp;</div>
                            <input type="text" class="modal-flat-shop-form-box-field-50 text-right" id="member_tax" name="member_tax" value="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Sup Op.Principal<span class="required">*</span></div>
                            <input type="text" class="modal-flat-shop-form-box-field-50 text-right" id="member_sup_principal" name="member_sup_principal" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Sup Op.Interest<span class="required">*</span></div>
                            <input type="text" class="modal-flat-shop-form-box-field-30 text-right" id="member_sup_interest" name="member_sup_Interest" value="0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Sup Op.Tax<span class="required">*</span></div>
                            <input type="text" class="modal-flat-shop-form-box-field-30 text-right" id="member_sup_tax" name="member_sup_tax" value="0">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="modal-flat-shop-form-field-box">
                            <div class="modal-flat-shop-form-box-label">Supplementary bill update</div>
                            <input type="checkbox" class="modal-flat-shop-form-box-field-20 text-right" id="checkbox_supplementary" name="member_supplementary">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button id="update_op" type="button" onclick="updateOpBill();" class="btn-success"><span>Update Bill</span></button>
                    </div>
                </div>
            </div>

            <div class="line-break"></div>

            <div class="row">
                <div class="col-md-12">
                    <a href="javascript:void(0);" onclick="printActiveLedger();" class="btn-print">Print Friendly</a>
                    <ul class="tab-nav" id="flatShopTabs">
                        <li><a href="javascript:void(0);" class="active" onclick="switchFsTab('ledger',this);">Current Member</a></li>
                        <li><a href="javascript:void(0);" onclick="switchFsTab('old_ledger',this);loadOldMemberLedger();">Old Member Bill</a></li>
                        <li><a href="javascript:void(0);" onclick="switchFsTab('tariff',this);">Tariff</a></li>
                    </ul>
                    <div class="tab-content-panel">
                        <div id="ledger" class="tab-pane active">
                            <div id="print_individual_member_ledger">
                            <div class="print-individual-member-ledger">
                                <div class="societyinfo-section" id="print_header_section">
                                    <h5 id="fs_society_name"></h5>
                                    <div class="report-address-heading" id="fs_society_reg_no"></div>
                                    <div class="report-address-heading" id="fs_society_address"></div>
                                    <div class="report-bill">Member Ledger</div>
                                    <div class="building-name"><span>Building Name : </span><span id="print_header_section_bulding_name"></span></div>
                                    <div class="wing-name"><span>Wing : </span><span id="print_header_section_bulding_wing"></span></div><br>
                                    <div class="member-name"><span>Member Name : </span><span id="print_header_section_member_name"></span></div>
                                    <div class="flat-no"><span>Unit No : </span><span id="print_header_section_unit_no"></span></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Particular</th>
                                                <th class="text-right">Dr.Amount</th>
                                                <th class="text-right">Cr.Amount</th>
                                                <th class="text-right">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="flat_details"></tbody>
                                    </table>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div id="old_ledger" class="tab-pane">
                            <div id="print_old_member_ledger">
                                <div class="report-bill" style="text-align:center;font-weight:bold;">Old Member Ledger <span id="old_ledger_name"></span></div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Particular</th>
                                                <th class="text-right">Dr.Amount</th>
                                                <th class="text-right">Cr.Amount</th>
                                                <th class="text-right">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="old_flat_details"><tr><td colspan="5" style="text-align:center;">Loading...</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div id="tariff" class="tab-pane">
                            <p>Tariff Details Here</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
<script>
(function() {
    var searchInput = document.getElementById('fs_unit_search_input');
    var optionsContainer = document.getElementById('fs_unit_options');
    var dropdownList = document.getElementById('fs_unit_dropdown_list');
    var triggerEl = document.getElementById('fs_unit_selected');
    var selectedText = document.getElementById('fs_unit_selected_text');
    var hiddenInput = document.getElementById('member_unit_no');
    var activeIndex = -1;

    function positionDropdown() {
        var rect = triggerEl.getBoundingClientRect();
        dropdownList.style.top = rect.bottom + 'px';
        dropdownList.style.left = rect.left + 'px';
        dropdownList.style.width = rect.width + 'px';
    }

    window.fsToggleDropdown = function() {
        if (dropdownList.style.display === 'none') {
            positionDropdown();
            dropdownList.style.display = 'block';
            searchInput.value = '';
            fsFilterUnits('');
            activeIndex = -1;
            searchInput.focus();
        } else {
            dropdownList.style.display = 'none';
        }
    };

    window.fsFilterUnits = function(query) {
        var options = optionsContainer.querySelectorAll('.fs-unit-option');
        var q = query.toLowerCase().trim();
        activeIndex = -1;
        options.forEach(function(opt) {
            opt.classList.remove('active');
            var val = opt.getAttribute('data-value').toLowerCase();
            if (q === '' || val.indexOf(q) !== -1) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    };

    window.fsSelectUnitOption = function(flatNo, memberId) {
        selectedText.textContent = flatNo;
        hiddenInput.value = flatNo;
        dropdownList.style.display = 'none';
        if (memberId) {
            getSocietyMemberDetails(memberId);
        } else {
            memberunitno();
        }
    };

    window.fsUpdateSelectedUnit = function(flatNo) {
        selectedText.textContent = flatNo;
        hiddenInput.value = flatNo;
    };

    searchInput.addEventListener('input', function() {
        fsFilterUnits(this.value);
    });

    searchInput.addEventListener('keydown', function(e) {
        var visibleOpts = [];
        optionsContainer.querySelectorAll('.fs-unit-option').forEach(function(opt) {
            if (opt.style.display !== 'none') visibleOpts.push(opt);
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, visibleOpts.length - 1);
            highlightOption(visibleOpts);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlightOption(visibleOpts);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && visibleOpts[activeIndex]) {
                var opt = visibleOpts[activeIndex];
                fsSelectUnitOption(opt.getAttribute('data-value'), opt.getAttribute('data-member-id'));
            } else if (visibleOpts.length === 1) {
                var opt = visibleOpts[0];
                fsSelectUnitOption(opt.getAttribute('data-value'), opt.getAttribute('data-member-id'));
            } else if (visibleOpts.length > 0 && this.value.trim() !== '') {
                var opt = visibleOpts[0];
                fsSelectUnitOption(opt.getAttribute('data-value'), opt.getAttribute('data-member-id'));
            }
        } else if (e.key === 'Escape') {
            dropdownList.style.display = 'none';
        }
    });

    function highlightOption(visibleOpts) {
        visibleOpts.forEach(function(o) { o.classList.remove('active'); });
        if (activeIndex >= 0 && visibleOpts[activeIndex]) {
            visibleOpts[activeIndex].classList.add('active');
            visibleOpts[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    optionsContainer.addEventListener('click', function(e) {
        var opt = e.target.closest('.fs-unit-option');
        if (opt) {
            fsSelectUnitOption(opt.getAttribute('data-value'), opt.getAttribute('data-member-id'));
        }
    });

    optionsContainer.addEventListener('mouseover', function(e) {
        var opt = e.target.closest('.fs-unit-option');
        if (opt) {
            optionsContainer.querySelectorAll('.fs-unit-option').forEach(function(o) { o.classList.remove('active'); });
            opt.classList.add('active');
        }
    });

    document.addEventListener('click', function(e) {
        var dd = document.getElementById('fs_unit_dropdown');
        var dl = document.getElementById('fs_unit_dropdown_list');
        if (dd && !dd.contains(e.target) && dl && !dl.contains(e.target)) {
            dropdownList.style.display = 'none';
        }
    });
})();
</script>
