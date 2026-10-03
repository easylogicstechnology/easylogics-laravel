{{-- Carried over from CakePHP app/View/AccountReports/account_bill_half_page.ctp lines 31-209: only the helper calls changed --}}
                                    <?php
                                    if(isset($monthlyBillsSummaryDetails) && !empty($monthlyBillsSummaryDetails)){
                                    foreach($monthlyBillsSummaryDetails as $memberbillReport) {
                                    ?>
                                <div class="bill-half-page">
                                    <div class="bill-outer-border">
                                        <div class="row">
                                            <h5 class="text-center"><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></h5>
                                            <div class="address-heading">Registration No. <?php echo isset($memberbillReport['Society']['registration_no']) ? $memberbillReport['Society']['registration_no'] : ''; ?> Dated: <?php echo isset($societyDetails['Society']['registration_date']) ? $utilObj->getFormatDate($societyDetails['Society']['registration_date'],'d/m/y') : ''; ?></div>
                                            <div class="address-heading"><?php echo isset($memberbillReport['Society']['address']) ? $memberbillReport['Society']['address'] : ''; ?></div>
                                        </div>
                                        <div class="row1">
                                            <div class="bill">BILL</div>
                                            <div class="simple-border"></div>
                                            <div class="row bill-member-details-section">
                                                <div class="col-md-9 col-xs-9">
                                                    <div class="row">
                                                        <div class="col-md-3 col-sm-3 col-xs-3">
                                                            <div class="">Unit No: <strong><?php echo isset($memberbillReport['Member']['flat_no']) ? $memberbillReport['Member']['flat_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Unit Area: <strong><?php echo isset($memberbillReport['Member']['area']) ? $memberbillReport['Member']['area'] :'' ; ?></strong> SqFt</div>
                                                        </div>                                                        
                                                        <div class="col-md-5 col-sm-5 col-xs-5">
                                                            <div class="">Unit type : <?php if($memberbillReport['Member']['unit_type'] == 'C') { echo 'Commercial';} else if($memberbillReport['Member']['unit_type'] == 'R') { echo 'Residential';} else if($memberbillReport['Member']['unit_type'] == 'B') { echo 'Both';} else { echo $memberbillReport['Member']['unit_type']; }?> </div>
                                                        </div>

                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Name &nbsp;&nbsp; : &nbsp;<?php echo $memberbillReport['Member']['member_prefix'].' '.$memberbillReport['Member']['member_name'];?></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill For &nbsp; : &nbsp;<?php echo isset($memberbillReport['MemberBillSummary']['monthName']) ? $memberbillReport['MemberBillSummary']['monthName'] :'' ; ?> <?php echo isset($memberbillReport['Society']['financial_year']) ? $memberbillReport['Society']['financial_year'] :'' ; ?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Wing &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;<?php $memberWingId = isset($memberbillReport['Member']['wing_id']) ? $memberbillReport['Member']['wing_id'] : ''; echo isset($wingList[$memberWingId]) ? $wingList[$memberWingId] : ''; ?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Floor No : <?php echo isset($memberbillReport['Member']['floor_no']) ? $memberbillReport['Member']['floor_no'] :'' ; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-xs-3">
                                                    <div class="row">
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill Number &nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_no']) ? $memberbillReport['MemberBillSummary']['bill_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_generated_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_generated_date'],'d/m/Y') :''; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_due_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_due_date'],'d/m/Y') :''; ?></strong></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row1">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="text-center" style="width: 5% !important;" >Sr.</th>
                                                                        <th colspan="3" class="text-center">Particular of Charges</th>
                                                                        <th class="text-center">Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                    <?php
                                                                        $tariffSr = 1;
                                                                        $memberBillTariff = array();
                                                                        foreach($memberbillReport['MemberTariff'] as $key => $indTariff) {
                                                                            if($indTariff['amount'] > 0) {
                                                                                $memberBillTariff[] = $indTariff['ledger_head_id'];
                                                                                $ledgerHead = $indTariff['title'];
                                                                                $ledgerAmount = number_format($indTariff['amount'],2);
                                                                                echo "<tr>
                                                                                        <td style='text-align:center;width: 5% !important;'>$tariffSr</td>
                                                                                       <td  colspan=\"3\" >&nbsp; $ledgerHead</td>
                                                                                        <td style='width:14%;' class='text-right'>$ledgerAmount</td>
                                                                                        </tr>";
                                                                                $tariffSr++;
                                                                            }
                                                                        }
                                                                    
                                                                    if(isset($societyParameters['SocietyParameter']['show_all_tariff_name']) && $societyParameters['SocietyParameter']['show_all_tariff_name'] == 1){                                                                            
                                                                        if(!empty($societyLedgerHeadIds)) {
                                                                            $tariffsNotInBill = array_diff($societyLedgerHeadIds,$memberBillTariff);
                                                                            if(!empty($tariffsNotInBill)) {
                                                                                foreach($tariffsNotInBill as $headId) {                                                                        
                                                                                        $ledgerHead = $societyLedgerHeadTitleList[$headId];
                                                                                        $ledgerAmount = '0.00';
                                                                                        echo "<tr>
                                                                                                <td style='text-align:center;width: 5% !important;'>$tariffSr</td>
                                                                                               <td  colspan=\"3\" >&nbsp; $ledgerHead</td>
                                                                                                <td style='width:14%;' class='text-right'>$ledgerAmount</td>
                                                                                                </tr>";
                                                                                        $tariffSr++;
                                                                                 }
                                                                            }
                                                                        }
                                                                    }                                                                    
                                                                    ?>

                                                                <tr>
                                                                    <td colspan="2" rowspan="5" style="width:55% !important;"></td>
                                                                    <td colspan="2">&nbsp; Total</td>
                                                                    <td class="text-right">
                                                                        <?php echo number_format($utilObj->NumberFormat2Decimal($memberbillReport['MemberBillSummary']['monthly_amount']-$memberbillReport['MemberBillSummary']['tax_total']),2);?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Add : Interest</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['interest_balance'],2);  ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Less : Adjustment</td>
                                                                    <td class="text-right">
                                                                        <?php 
                                                                            $totalAdjustment =  $memberbillReport['MemberBillSummary']['principal_adjusted'] + $memberbillReport['MemberBillSummary']['interest_adjusted'];
                                                                            echo number_format($totalAdjustment,2);
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>&nbsp; Principal Arrears</td>
                                                                    <td class="text-right">
                                                                        <?php if($memberbillReport['MemberBillSummary']['op_due_amount'] < 0) { $posNeg = 'Cr'; }
                                                                        else { $posNeg = 'Dr'; }
                                                                        $memberbillReport['MemberBillSummary']['op_due_amount'] = abs($memberbillReport['MemberBillSummary']['op_due_amount']);
                                                                        echo number_format($memberbillReport['MemberBillSummary']['op_due_amount'],2).' '.$posNeg;
                                                                        ?>
                                                                    </td>
                                                                    <td class="text-right" rowspan="2">
                                                                        <?php
                                                                        $totalArrears = $memberbillReport['MemberBillSummary']['op_principal_arrears'];
                                                                        if($totalArrears < 0) { $posNeg = 'Cr'; }
                                                                        else { $posNeg = 'Dr'; }
                                                                        $totalArrears = abs($totalArrears);
                                                                        echo number_format($totalArrears,2).' '.$posNeg;
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>&nbsp; Interest Arrears</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['op_interest_arrears'],2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" >&nbsp; Rupees <?php echo $utilObj->convertToWords(abs($memberbillReport['MemberBillSummary']['balance_amount']));?> Only</td>
                                                                    <td colspan="2" class="font-weight-bold"><?php  if($memberbillReport['MemberBillSummary']['balance_amount'] < 0) { echo "<span>&nbsp; Excess Amount Received</span>";  } else { echo "<strong>&nbsp; Total Due & Payable Amount</strong>"; } ?></td>
                                                                    <td class="text-right"><?php  if($memberbillReport['MemberBillSummary']['balance_amount'] < 0) { echo number_format(str_replace('-','',$memberbillReport['MemberBillSummary']['balance_amount']),2).' Cr'; }
                                                                             else {echo number_format($memberbillReport['MemberBillSummary']['balance_amount'],2).' Dr'; } ?>
                                                                    </td>
                                                                </tr>


                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12 col-xs-12">
                                                <strong>Notes: </strong>
                                                <div><?php echo isset($societyParameters['SocietyParameter']['bill_note']) ? $societyParameters['SocietyParameter']['bill_note'] : ''; ?> </div>                                                                                                    
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12 col-xs-12">
                                                <div class="pull-right"><strong><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></strong></div><br>
                                                <br><div class="pull-right"><?php echo isset($memberbillReport['Society']['authorised_person']) ? $memberbillReport['Society']['authorised_person'] : 'Authorised Signature'; ?></div>
                                            </div>
                                            <div class="clearfix"></div>                                            
                                            <div class="col-md-12 col-xs-12">
                                                <div class="special-field-text"><?php echo isset($societyParameters['SocietyParameter']['special_field']) ? $societyParameters['SocietyParameter']['special_field'] : ''; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <?php } }?>
