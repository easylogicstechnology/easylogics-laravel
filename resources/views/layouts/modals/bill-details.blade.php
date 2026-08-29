@php
    $__sid = Auth::id();
    $__billMembers = \App\Models\Member::where('society_id', $__sid)->where('status',1)->orderBy('flat_no')->get();
    $__billBuildings = \App\Models\Building::where('society_id', $__sid)->where('status',1)->orderBy('building_name')->get();
    $__societyParam = \App\Models\SocietyParameter::where('society_id', $__sid)->first();
    $__billingFreqId = $__societyParam ? $__societyParam->billing_frequency_id : 1;
    $__billingFrequenciesArray = [
        1 => [4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',1=>'January',2=>'February',3=>'March'],
        2 => [4=>'Apr-May',6=>'Jun-Jul',8=>'Aug-Sep',10=>'Oct-Nov',12=>'Dec-Jan',2=>'Feb-Mar'],
        3 => [4=>'Apr-May-Jun',7=>'Jul-Aug-Sep',10=>'Oct-Nov-Dec',1=>'Jan-Feb-Mar'],
        4 => [],
        5 => [4=>'Apr-Sep',10=>'Oct-Mar'],
        6 => [4=>'April-March'],
    ];
    $__monthOptions = $__billingFrequenciesArray[$__billingFreqId] ?? $__billingFrequenciesArray[1];
    $__fyStartYear = session('fy.start_year', date('Y'));
    $__fyEndYear = session('fy.end_year', date('Y') + 1);
@endphp
<style>
#billModal .modal-box{position:relative;}
#billModal .pager1{list-style:none;margin:0;padding:0;}
#billModal .previous-btn-list{display:block;overflow:hidden;position:absolute;top:250px;left:0px;height:50px;background:#f2b701;border:0px solid green;color:#000;font-weight:900;padding-top:12px;cursor:pointer;text-decoration:none;z-index:10;}
#billModal .next-btn-list{display:block;overflow:hidden;position:absolute;top:250px;right:0px;height:50px;background:#f2b701;border:0px solid blue;color:#333;font-weight:600;padding-top:12px;cursor:pointer;text-decoration:none;z-index:10;}
#billModal .previous-btn-list:hover,#billModal .next-btn-list:hover{background:#e6a800;}
#billModal .modal-body-content{margin:8px;padding:6px;}
#billModal .form-group{margin-bottom:4px !important;}
#billModal label.control-label{font-size:11px;margin-bottom:1px;}
#billModal .form-control{font-size:12px;padding:2px 4px;height:26px;}
#billModal .table th,#billModal .table td{padding:3px 5px;font-size:12px;}
#billModal .nav-tabs>li>a{font-size:12px;padding:5px 10px;}
</style>
<div id="billModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:780px;position:relative;">
    <div class="modal-header-bar">
        <h6 style="color:#fff;margin:0;display:inline;font-size:16px;">Member Bill</h6>
        <span class="modal-close" onclick="closeModal('billModal')">&times;</span>
    </div>
    <div class="pager1">
        <a class="previous-btn-list" id="society_member_bill_previous_button" href="javascript:void(0);" onclick="societyMemberBillPrevious();" style="display:none;">&lt;&lt;</a>
        <a class="next-btn-list" id="society_member_bill_next_button" href="javascript:void(0);" onclick="societyMemberBillNext();">&gt;&gt;</a>
    </div>
    <div class="modal-body-content">
        <form id="society_member_bill_summary" name="society_member_bill_summary">
            <input type="hidden" id="society_member_summary_id" value="">
            <input type="hidden" id="society_member_bill_previous" value="">
            <input type="hidden" id="society_member_bill_next" value="">
            <input type="hidden" id="society_member_bill_previous_month" value="">
            <input type="hidden" id="society_member_bill_next_month" value="">
            <input type="hidden" id="bill_id" value="">

            <div class="row">
                <div class="col-md-2" style="padding:0 2px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Bill For<span style="color:red;">*</span></label>
                        <select class="form-control" id="member_bill_summary_month" onchange="billMonthChanged(this.value);">
                            <option value="">Select Month</option>
                            @foreach($__monthOptions as $monthId => $monthName)
                            <option value="{{ $monthId }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-1" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Bill No.<span style="color:red;">*</span></label>
                        <input type="text" id="member_bill_summary_bill_no" class="form-control input-height-30" onblur="getAllMembersBillSummaryDetails('bill_no=' + this.value);" value="">
                    </div>
                </div>
                <div class="col-md-2" style="padding:0 4px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Manual Bill No.</label>
                        <input type="text" id="member_bill_summary_manual_bill_no" class="form-control input-height-30" value="">
                    </div>
                </div>
                <div class="col-md-3" style="padding:0 2px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Bill Date</label>
                        <input type="date" id="member_bill_summary_bill_generated_date" class="form-control input-height-30" onblur="getAllMembersBillSummaryDetails('bill_generated_date=' + this.value);" value="">
                    </div>
                </div>
                <div class="col-md-3" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Due Date</label>
                        <input type="date" id="member_bill_summary_bill_due_date" class="form-control input-height-30" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Rebate Date</label>
                        <input type="date" id="member_bill_summary_rebate_date" class="form-control input-height-30" value="">
                    </div>
                </div>
                <div class="col-md-2" style="padding:0 4px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Member</label>
                        <select class="form-control" id="member_bill_summary_member_id" onchange="getAllMembersBillSummaryDetails('member_id=' + this.value);">
                            <option value="">Select Members</option>
                            @foreach($__billMembers as $m)
                            <option value="{{ $m->id }}">{{ $m->member_name }} ({{ $m->flat_no }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3" style="padding:0 2px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Building<span style="color:red;">*</span></label>
                        <select class="form-control" id="member_bill_summary_building_id">
                            <option value="">Select building</option>
                            @foreach($__billBuildings as $b)
                            <option value="{{ $b->id }}">{{ $b->building_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-1" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Wing</label>
                        <select class="form-control" id="member_bill_summary_bill_wing_id"><option value="">Select</option></select>
                    </div>
                </div>
                <div class="col-md-1" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Unit No.</label>
                        <input type="text" id="member_bill_summary_bill_flat_no" class="form-control input-height-30" value="">
                    </div>
                </div>
                <div class="col-md-1" style="padding:0;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Area</label>
                        <input type="text" id="member_bill_summary_area" class="form-control input-height-30" value="">
                    </div>
                </div>
                <div class="col-md-1" style="padding:0 4px;">
                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="control-label">Rate</label>
                        <input type="text" id="member_bill_summary_rate" class="form-control input-height-30" value="" placeholder="Factor">
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom:4px;">
                <div class="col-md-12" style="padding:0 2px;">
                    <input type="text" id="bill_search_member" class="form-control" placeholder="Search by Unit No. or Member Name..." onkeyup="billSearchMember(this.value);" style="font-size:12px;height:28px;">
                    <div id="bill_search_results" style="display:none;position:absolute;z-index:100;background:#fff;border:1px solid #ddd;max-height:150px;overflow-y:auto;width:calc(100% - 4px);box-shadow:0 2px 6px rgba(0,0,0,.15);"></div>
                </div>
            </div>

            <div class="row">
                <ul role="tablist" class="nav nav-tabs" id="billMainTabsNav">
                    <li class="active" role="presentation"><a href="#billChargesTab" onclick="switchTab('billMainTabContent','billChargesTab'); return false;">Charges</a></li>
                    <li role="presentation"><a href="#billInterestTab" onclick="switchTab('billMainTabContent','billInterestTab'); return false;">Interest</a></li>
                    <li role="presentation"><a href="#billSuppTab" onclick="switchTab('billMainTabContent','billSuppTab'); return false;">Supplementary Bill</a></li>
                </ul>
                <div class="tab-content" id="billMainTabContent">
                    <div id="billChargesTab" class="tab-pane active" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" style="margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>SR.</th>
                                        <th>PARTICULARS</th>
                                        <th style="text-align:right;">AMOUNT</th>
                                    </tr>
                                </thead>
                                <tbody id="bill_member_tariff_details">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="billInterestTab" class="tab-pane" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sr.</th>
                                        <th>Particulars</th>
                                        <th>Due Date</th>
                                        <th>Bill Date</th>
                                        <th>Days</th>
                                        <th>Rate(%)</th>
                                        <th class="text-right">Interest Amt</th>
                                    </tr>
                                </thead>
                                <tbody id="bill_interest_details">
                                    <tr><td colspan="7" style="text-align:center;">Select a bill to view interest details</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div id="billSuppTab" class="tab-pane" role="tabpanel">
                        <p></p>
                    </div>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <ul role="tablist" class="nav nav-tabs" id="billSubTabsNav">
                        <li class="active" role="presentation"><a href="#billReceipts" onclick="switchTab('billSubTabContent','billReceipts'); return false;">Receipts</a></li>
                        <li role="presentation"><a href="#billRemarks" onclick="switchTab('billSubTabContent','billRemarks'); return false;">Remark</a></li>
                        <li role="presentation"><a href="#billServiceTax" onclick="switchTab('billSubTabContent','billServiceTax'); return false;">Service Tax</a></li>
                    </ul>
                    <div class="tab-content" id="billSubTabContent">
                        <div id="billReceipts" class="tab-pane active" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6" style="padding:0 2px;">
                                    <div class="form-group">
                                        <label class="control-label">Principle Amount</label>
                                        <input type="text" id="member_bill_summary_bill_monthly_principal_amount" class="form-control" value="">
                                    </div>
                                </div>
                                <div class="col-md-6" style="padding:0;">
                                    <div class="form-group">
                                        <label class="control-label">Interest Free</label>
                                        <input type="text" id="member_bill_summary_bill_interest_on_due_amount" class="form-control" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <p>&nbsp;&nbsp; Settlement status of Current Bill</p>
                                <div class="col-md-12" style="padding:0 4px;">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead>
                                                <tr style="background:#fff3cd;">
                                                    <th>Title</th>
                                                    <th>Receipt</th>
                                                    <th>Adjustment</th>
                                                    <th>Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Principal</td>
                                                    <td id="bill_principle_paid">0.00</td>
                                                    <td id="bill_principal_adjusted">0.00</td>
                                                    <td id="bill_principal_bal">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Interest</td>
                                                    <td id="bill_interest_paid">0.00</td>
                                                    <td id="bill_interest_adjusted">0.00</td>
                                                    <td id="bill_interest_balance">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Tax</td>
                                                    <td id="bill_tax_paid">0.00</td>
                                                    <td id="bill_tax_adjusted">0.00</td>
                                                    <td id="bill_tax_balance">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td style="border:none !important;"></td>
                                                    <td style="border:none !important;"></td>
                                                    <td>Total Rs.</td>
                                                    <td id="bill_balance_amount">0.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="billRemarks" class="tab-pane" role="tabpanel">
                            <div id="remarkmsg"></div>
                            <p><textarea class="form-control" cols="20" id="bill_remarks" name="bill_remarks"></textarea><br><button id="saveRemarkSum" type="button" class="btn btn-success btn-sm" onclick="saveBillRemark();">Save</button></p>
                        </div>
                        <div id="billServiceTax" class="tab-pane" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr style="background:#fff3cd;">
                                            <th>Title</th>
                                            <th>Tax</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>IGST</td>
                                            <td id="bill_igst_total">0.00</td>
                                        </tr>
                                        <tr>
                                            <td>CGST</td>
                                            <td id="bill_cgst_total">0.00</td>
                                        </tr>
                                        <tr>
                                            <td>SGST</td>
                                            <td id="bill_sgst_total">0.00</td>
                                        </tr>
                                        <tr>
                                            <td>Total Rs.</td>
                                            <td id="bill_tax_total">0.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="row">
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Total</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_monthly_principal_amount" readonly="">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Interest</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_interest_on_due_amount" value="0.00">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Less Rebate</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_discount" value="0.00">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Other Adjustments(Principal)</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_principal_adjusted" value="0.00">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Other Adjustments(Interest)</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_interest_adjusted" value="0.00">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px; color:blue;">Bill Amount(Rs)</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_monthly_bill_amount" readonly="">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Principal Arrears</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_principal_arrears" readonly="">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Interest Arrears</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_interest_balance" readonly="">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">S.Tax Arrears</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_op_tax_arrears" value="0.00" readonly="">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px;">Total Arrears</label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_op_total_errears" readonly="">
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="form-group" style="margin-bottom:6px;">
                            <label class="control-label col-sm-5" style="padding-top:4px;font-size:11px; color:blue;"><span id="amtPayableExcess">Amount Payable</span></label>
                            <div class="col-sm-6">
                                <input type="text" class="form-control text-right" id="member_bill_summary_bill_amount_payable" readonly="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <center>
                <button id="updateMemSum" type="button" class="btn btn-success btn-sm" onclick="updateMemberBillSummaryById();">Update</button>
                <button id="InterestMemSum" type="button" class="btn btn-success btn-sm" onclick="updateMemberBillSummaryById();">Interest menualy</button>
                <button id="delMemSum" type="button" class="btn btn-danger btn-sm" onclick="deleteMemberBillSummaryById();" ifpayment="0" style="display:none;">Delete</button>
                <button type="button" class="btn btn-success btn-sm" onclick="closeModal('billModal');">Cancel</button>
                <button id="updateManualSum" type="button" class="btn btn-warning btn-sm" onclick="updateManualInterest();">Manual Update Interest</button>
            </center>
        </form>
    </div>
</div>
</div>
<script>
var billFyStartYear = {{ $__fyStartYear }};
var billFyEndYear = {{ $__fyEndYear }};
var billMembersList = @json($__billMembers->map(function($m){ return ['id'=>$m->id,'name'=>$m->member_name,'flat_no'=>$m->flat_no]; }));

function billMonthChanged(monthVal) {
    if (!monthVal) return;
    var m = parseInt(monthVal);
    var year = (m >= 4) ? billFyStartYear : billFyEndYear;
    var lastDay = new Date(year, m, 0).getDate();
    var mm = m < 10 ? '0' + m : '' + m;
    document.getElementById('member_bill_summary_bill_generated_date').value = year + '-' + mm + '-01';
    document.getElementById('member_bill_summary_bill_due_date').value = year + '-' + mm + '-' + (lastDay < 10 ? '0' + lastDay : lastDay);
    getAllMembersBillSummaryDetails('month=' + monthVal);
}

function billSearchMember(query) {
    var resultsDiv = document.getElementById('bill_search_results');
    if (!query || query.length < 1) { resultsDiv.style.display = 'none'; return; }
    var q = query.toLowerCase();
    var matches = billMembersList.filter(function(m) {
        return (m.flat_no && m.flat_no.toLowerCase().indexOf(q) !== -1) || (m.name && m.name.toLowerCase().indexOf(q) !== -1);
    });
    if (matches.length === 0) { resultsDiv.style.display = 'none'; return; }
    var html = '';
    matches.forEach(function(m) {
        html += '<div style="padding:4px 8px;cursor:pointer;font-size:12px;border-bottom:1px solid #eee;" onmouseover="this.style.background=\'#e8f0fe\'" onmouseout="this.style.background=\'#fff\'" onclick="billSelectSearchMember(' + m.id + ',\'' + m.flat_no + '\',\'' + m.name.replace(/'/g, "\\'") + '\')">' + m.flat_no + ' - ' + m.name + '</div>';
    });
    resultsDiv.innerHTML = html;
    resultsDiv.style.display = 'block';
}

function billSelectSearchMember(memberId, flatNo, name) {
    document.getElementById('bill_search_results').style.display = 'none';
    document.getElementById('bill_search_member').value = flatNo + ' - ' + name;
    document.getElementById('member_bill_summary_member_id').value = memberId;
    var month = document.getElementById('member_bill_summary_month').value;
    if (month) {
        getAllMembersBillSummaryDetails('member_id=' + memberId + '&month=' + month);
    } else {
        getAllMembersBillSummaryDetails('member_id=' + memberId);
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#bill_search_results') && e.target.id !== 'bill_search_member') {
        document.getElementById('bill_search_results').style.display = 'none';
    }
});
</script>
