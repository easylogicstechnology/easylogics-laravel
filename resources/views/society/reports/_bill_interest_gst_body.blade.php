{{-- Carried over from CakePHP app/View/AccountReports/account_bill_with_interest_gst.ctp lines 21-281: only the helper calls changed --}}
                                    <?php
                                    if(isset($monthlyBillsSummaryDetails) && !empty($monthlyBillsSummaryDetails)){
                                    foreach($monthlyBillsSummaryDetails as $memberbillReport) {
                                    ?>
                                <div class="print-int-with-gst-bill">
                                    <div class="bill-outer-border">
                                        <div class="row">
                                            <h5 class="text-center"><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></h5>
                                            <div class="address-heading">Registration No. <?php echo isset($memberbillReport['Society']['registration_no']) ? $memberbillReport['Society']['registration_no'] : ''; ?> Dated: <?php echo isset($societyDetails['Society']['registration_date']) ? $utilObj->getFormatDate($societyDetails['Society']['registration_date'],'d/m/y') : ''; ?></div>
                                            <div class="address-heading"><?php echo isset($memberbillReport['Society']['address']) ? $memberbillReport['Society']['address'] : ''; ?></div>
                                        </div>
                                        <div class="row1">
                                            <div class="bill">Maintenance Bill</div>
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
                                                        <div class="col-md-5 col-xs-5">
                                                            <div class="">Bill For &nbsp; : &nbsp;<?php echo isset($memberbillReport['MemberBillSummary']['monthName']) ? $memberbillReport['MemberBillSummary']['monthName'] :'' ; ?> <?php echo isset($memberbillReport['Society']['financial_year']) ? $memberbillReport['Society']['financial_year'] :'' ; ?></div>
                                                        </div>
                                                        <div class="col-md-3 col-sm-3 col-xs-3">
                                                            <div class="">Wing : <?php echo isset($wingDetails[$memberbillReport['Member']['wing_id']]) ? $wingDetails[$memberbillReport['Member']['wing_id']] : '' ;?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Floor No : <?php echo isset($memberbillReport['Member']['floor_no']) ? $memberbillReport['Member']['floor_no'] :'' ; ?></div>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="col-md-5 col-sm-5 col-xs-5">
                                                            <div class="">Member GSTIN &nbsp; : &nbsp;<?php echo isset ($memberbillReport['Member']['gstin_no']) ? $memberbillReport['Member']['gstin_no'] : '' ;?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="hide-in-print">Parking No : <?php echo isset($memberbillReport['Member']['member_parking_no'])? $memberbillReport['Member']['member_parking_no'] :'-' ; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-xs-3">
                                                    <div class="row">
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_no']) ? $memberbillReport['MemberBillSummary']['bill_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_generated_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_generated_date'],'d/m/y') :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_due_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_due_date'],'d/m/y') :'' ; ?></strong></div>
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
                                                                    <td colspan="2" rowspan="9" style="width:55% !important;"><?php if(trim($memberbillReport['MemberBillSummary']['remarks']) != ''){ ?>&nbsp;&nbsp;<?php echo $memberbillReport['MemberBillSummary']['remarks'];?><?php } ?></td>
                                                                    <td colspan="2">&nbsp; Total</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['monthly_amount']-$memberbillReport['MemberBillSummary']['tax_total'],2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Add : Interest</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['interest_on_due_amount'],2);  ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Discount</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['principal_adjusted']+$memberbillReport['MemberBillSummary']['interest_adjusted'],2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; <strong>Total Taxable Amount</strong></td>
                                                                    <td class="text-right"><strong><?php echo number_format($memberbillReport['MemberBillSummary']['total_taxable_amt'],2);  ?></strong></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Add : IGST @ <?php echo $societyParameters['SocietyParameter']['igst_tax_per']; ?>%</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['igst_total'],2);  ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Add : CGST @ <?php echo $societyParameters['SocietyParameter']['cgst_tax_per']; ?>%</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['cgst_total'],2);  ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; Add : SGST @ <?php echo $societyParameters['SocietyParameter']['sgst_tax_per']; ?>%</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['sgst_total'],2);  ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>&nbsp; Principal Arrears</td>
                                                                    <td class="text-right"><?php echo $memberbillReport['MemberBillSummary']['op_principal_arrears'] ? $utilObj->CreditDebitAmountCheck($memberbillReport['MemberBillSummary']['op_principal_arrears']):'';?></td>
                                                                    <td class="text-right" rowspan="2">
                                                                        <?php echo number_format($memberbillReport['MemberBillSummary']['op_due_amount'],2);?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>&nbsp; Interest Arrears</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['op_interest_arrears'],2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" >&nbsp; Rupees <?php echo $utilObj->convertToWords(abs($memberbillReport['MemberBillSummary']['amount_payable']));?> Only</td>
                                                                    <td colspan="2" class="font-weight-bold"><?php  if($memberbillReport['MemberBillSummary']['amount_payable'] < 0) { echo "<span>&nbsp; Excess Amount Received</span>";  } else { echo "<strong>&nbsp; Total Due & Payable Amount</strong>"; } ?></td>
                                                                    <td class="text-right"><?php  if($memberbillReport['MemberBillSummary']['amount_payable'] < 0) { echo number_format(str_replace('-','',$memberbillReport['MemberBillSummary']['amount_payable']),2).' Cr'; }
                                                                            else {echo number_format($memberbillReport['MemberBillSummary']['amount_payable'],2).' Dr'; } ?>
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
                                                <div><?php echo $societyParameters['SocietyParameter']['bill_note']; ?> </div>  
                                                <div>GSTIN : <strong><?php echo isset($memberbillReport['Society']['gstin_no']) ? $memberbillReport['Society']['gstin_no'] : ''; ?></strong> </div>                                                      
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12 col-xs-12">
                                                <div class="pull-right"><strong>For <?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></strong></div><br>
                                                <br><div class="pull-right"><?php echo isset($memberbillReport['Society']['authorised_person']) ? $memberbillReport['Society']['authorised_person'] : 'Authorised Signature'; ?></div>
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12 col-xs-12">
                                                <div class="special-field-text"><?php echo $societyParameters['SocietyParameter']['special_field']; ?></div>                                                
                                            </div>
                                        </div>
                                            <div class="clearfix"></div>
                                        <br>

                                        
                                            <?php  if(isset($memberbillReport['receipts']) && !empty($memberbillReport['receipts'])){?>
                                        <div class="row simple-border-dotted" style="padding:0px;"></div>
                                        <div class="row bill-padding-10">
                                            <div class="receipt-title">Maintenance Receipt</div>
                                            <div class="col-md-8 col-xs-8">Received with thanks from <strong><?php echo $memberbillReport['Member']['member_prefix'].' '.$memberbillReport['Member']['member_name'];?></strong></div>
                                            <div class="col-md-4 col-xs-4 text-right">Unit No :<strong> <?php echo $memberbillReport['Member']['flat_no'];?></strong></div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12">Details of payments received are as under : <strong>Period : 01/04/2022 To 23/08/2022</strong></div>
                                        </div>
                                        <div class="row1">
                                            <div style="padding: 0px;">
                                                <div class="table-wrap">
                                                    <div class="table-responsive">
                                                        <table  class="table padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width:6%;" class="text-center">Receipt</th>
                                                                    <th style="width:10%;" class="text-center">Date</th>
                                                                    <th style="width:8%;" class="text-center">Chq No  Neft.</th>
                                                                    <th style="width:10%;" class="text-center">Chq Date</th>
                                                                    <th class="text-center">Narration & Bank & Branch</th>
                                                                    <th style="width:18%;" class="text-center">Towards bill No.</th>
                                                                    <th style="width:8%;" class="text-center">Amount</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $receiptSr = 1;
                                                                $memberTotalPaid = 0;
                                                                
                                                                //echo '<pre>';
                                                                //print_r($receiptBillInfoArray);
                                                                //exit;
                                                                foreach($memberbillReport['receipts'] as $key=>$receipt) {
                                                                    $receiptDetails = $receipt['MemberPayment'];
                                                                    //echo 'Id->'.$receiptDetails['id'].'<br>';
                                                                    $paymentDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    $entryDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    //$billFor = $societyObj->monthWordFormatByBillingFrequency($receiptDetails['bill_month']);
                                                                    
                                                                    $bill_no = $receiptBillInfoArray[$receiptDetails['id']]['bill_no'];
                                                                    $billFor = $societyObj->monthWordFormatByBillingFrequency($receiptBillInfoArray[$receiptDetails['id']]['bill_month']);
                                                                    
                                                                    echo "<tr id=\"\">
                                                                            <td class=\"text-center\">$receiptDetails[receipt_id]</td>
                                                                            <td class=\"text-center\">$entryDate</td>
                                                                            <td class=\"text-center\">$receiptDetails[payment_mode],$receiptDetails[cheque_reference_number]</td>
                                                                            <td class=\"text-center\">$paymentDate</td>
                                                                            <td>$receiptDetails[member_bank_id], $receiptDetails[member_bank_branch]</td>
                                                                            <td class=\"text-center\">$bill_no For  $billFor </td>
                                                                            <td class=\"text-right\">$receiptDetails[amount_paid]</td>
                                                                           </tr>";

                                                                    $memberTotalPaid += $receiptDetails['amount_paid'];
                                                                    $receiptSr++;
                                                                } ?>
                                                                <tr id="">
                                                                    <td colspan="5" >&nbsp; Rupees <?php ;?> only</td>
                                                                    <td class="font-weight-600">Total</td>
                                                                    <td class="text-right"><?php  ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="bill-padding-10">(Subject to Realisation of Cheque)</div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="pull-right"><strong>For <?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></strong></div><br>
                                            <br><div class="pull-right"><?php echo isset($memberbillReport['Society']['authorised_person']) ? $memberbillReport['Society']['authorised_person'] : 'Authorised Signature'; ?></div>
                                        </div>
                                    <?php }?>

                                    </div>
                                </div>
                                    <?php } }?>
