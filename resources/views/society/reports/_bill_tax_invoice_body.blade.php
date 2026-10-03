{{-- Carried over from CakePHP app/View/AccountReports/account_tax_invoice_bill_gst.ctp lines 21-286: only the helper calls changed --}}
                                    <?php 
                                    if(isset($taxBillsSummaryDetails) && !empty($taxBillsSummaryDetails)){
                                    foreach($taxBillsSummaryDetails as $memberbillReport) {
                                    ?> 
                                <div class="print-gst-bill">  
                                    <div class="bill-outer-border">
                                        <div class="row">
                                            <h5 class="text-center"><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></h5>
                                            <div class="address-heading">Registration No. <?php echo isset($memberbillReport['Society']['registration_no']) ? $memberbillReport['Society']['registration_no'] : ''; ?> Dated: <?php echo isset($memberbillReport['Society']['registration_date']) ? $utilObj->getFormatDate($memberbillReport['Society']['registration_date'],'d/m/y') : ''; ?></div>
                                            <div class="address-heading"><?php echo isset($memberbillReport['Society']['address']) ? $memberbillReport['Society']['address'] : ''; ?></div>
                                        </div>
                                        <div class="row1">
                                            <div class="bill">Tax Invoice/</div>
                                            <div class="simple-border"></div>
                                            <div class="row bill-member-details-section">
                                                <div class="col-md-8 col-xs-8">
                                                    <div class="row">
                                                        <div class="col-md-6 col-sm-6 col-xs-6">
                                                            <div class="">Unit No : <strong><?php echo isset($memberbillReport['Member']['flat_no']) ? $memberbillReport['Member']['flat_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-6 col-sm-6 col-xs-6">
                                                            <div class="">Unit Area : <strong><?php echo isset($memberbillReport['Member']['area']) ? $memberbillReport['Member']['area'] :''; ?></strong> <?php echo $unitArea;?></div>
                                                        </div>

                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Name &nbsp;&nbsp; : &nbsp;<?php echo $memberbillReport['Member']['member_prefix'].' '.$memberbillReport['Member']['member_name'];?></div>
                                                        </div>

                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill For &nbsp; : &nbsp;<?php echo isset($memberbillReport['MemberBillSummary']['monthName']) ? $memberbillReport['MemberBillSummary']['monthName'] :'' ; ?> <?php echo isset($memberbillReport['Society']['financial_year']) ? $memberbillReport['Society']['financial_year'] :'' ; ?></div>
                                                        </div>
                                                    </div>                                       
                                                </div> 
                                                <div class="col-md-4 col-xs-4">
                                                    <div class="row">
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_no']) ? $memberbillReport['MemberBillSummary']['bill_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_generated_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_generated_date'],'d/m/Y') :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_due_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_due_date'],'d/m/Y') :'' ;  ?></strong></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="">&nbsp;&nbsp; Member GSTIN : <?php echo isset($memberbillReport['Member']['gstin_no']) ? $memberbillReport['Member']['gstin_no'] :'-' ; ?></div>
                                            <div class="row1">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="text-center" style="width: 5% !important;" >Sr.</th>
                                                                        <th class="text-center" colspan="5" >Particulars of Charges</th>
                                                                        <th style="width: 12% !important;" class="text-center">Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                <?php 
                                                                    $tariffSr = 1;
                                                                    $memberBillTariff = array();
                                                                    $noGstTaxAmt = $gstTaxAmt = 0;
                                                                    foreach($memberbillReport['noTaxGst']['MemberTariff'] as $key => $indTariff) {
                                                                    if($indTariff['amount'] > 0) {	
                                                                            $memberBillTariff[] = $indTariff['ledger_head_id'];
                                                                            $ledgerHead = $indTariff['title'];
                                                                            $ledgerAmount = $indTariff['amount'];
                                                                            $noGstTaxAmt += $ledgerAmount;
                                                                            echo "<tr>
                                                                                    <td style=\"text-align:center;width:5% !important;\">$tariffSr</td>
                                                                                    <td colspan=\"5\" >&nbsp; $ledgerHead</td>
                                                                                    <td style=\"width:12%;\" class=\"text-right\">$ledgerAmount</td>
                                                                                    </tr>";
                                                                            $tariffSr++;
                                                                    }
                                                                } 
                                                                ?>
                                                                <tr>
                                                                    <th class="text-center" style="width: 5% !important;"></th>
                                                                    <td colspan="5"><span style="margin-left:50%;">Total</span></td>
                                                                    <td class="text-right" style="border-top:2px solid #000 !important;border-bottom:2px solid #000 !important;"><?php echo number_format($utilObj->NumberFormat2Decimal($noGstTaxAmt),2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:5% !important;"> </td>
                                                                    <td colspan="5">&nbsp;&nbsp;&nbsp; TAXABLE</td>
                                                                </tr>
                                                                <?php 
                                                                    $tariffSr = 1 ;                                                                    
                                                                    if(isset($memberbillReport['taxGst']['MemberTariff']) && count($memberbillReport['taxGst']['MemberTariff']) > 0){
                                                                    foreach($memberbillReport['taxGst']['MemberTariff'] as $key => $indTariff) {
                                                                    if($indTariff['amount'] > 0) {
                                                                            $memberBillTariff[] = $indTariff['ledger_head_id'];	
                                                                            $ledgerHead = $indTariff['title'];
                                                                            $ledgerAmount = number_format($indTariff['amount'],2);
                                                                            $gstTaxAmt += $indTariff['amount'];
                                                                            echo "<tr>
                                                                                    <td style=\"text-align:center;width: 5% !important;\">$tariffSr</td>
                                                                                   <td colspan=\"5\" > &nbsp; $ledgerHead</td>
                                                                                    <td style=\"width:10%;\" class=\"text-right\">$ledgerAmount</td>
                                                                                    </tr>";
                                                                            $tariffSr++;
                                                                    }   
                                                                    }}
                                                                                                                                        
                                                                    if(isset($societyParameters['SocietyParameter']['show_all_tariff_name']) && $societyParameters['SocietyParameter']['show_all_tariff_name'] == 1){                                                                            
                                                                        if(!empty($societyLedgerHeadIds)) {
                                                                            $tariffsNotInBill = array_diff($societyLedgerHeadIds,$memberBillTariff);
                                                                            if(!empty($tariffsNotInBill)) {
                                                                                foreach($tariffsNotInBill as $headId) {                                                                        
                                                                                        $ledgerHead = $societyLedgerHeadTitleList[$headId];
                                                                                        $ledgerAmount = '0.00';
                                                                                        echo "<tr>
                                                                                                <td style='text-align:center;width: 5% !important;'>$tariffSr</td>
                                                                                               <td  colspan=\"5\" >&nbsp; $ledgerHead</td>
                                                                                                <td style='width:14%;' class='text-right'>$ledgerAmount</td>
                                                                                                </tr>";
                                                                                        $tariffSr++;
                                                                                 }
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                <tr>
                                                                    <th class="text-center" style="width: 5% !important;" ></th>
                                                                    <td colspan="5"><span style="margin-left:50%;">Total</span></td>
                                                                    <td style="border-top:2px solid #000 !important;border-bottom:2px solid #000 !important;" class="text-right"><?php echo number_format($utilObj->NumberFormat2Decimal($gstTaxAmt),2); ?></td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td  colspan="3" rowspan="8" style="width:40% !important;"> </td>
                                                                    <td  colspan="3">&nbsp; Add: IGST @ <?php echo $societyParameters['SocietyParameter']['igst_tax_per']; ?>%</td>
                                                                    <td style="width:10%;" class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['igst_total'],2);?></td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td colspan="3">&nbsp; Add: CGST @ <?php echo $societyParameters['SocietyParameter']['cgst_tax_per']; ?>%</td>
                                                                    <td style="width:10%;" class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['cgst_total'],2);?></td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td colspan="3">&nbsp; Add: SGCT @ <?php echo $societyParameters['SocietyParameter']['sgst_tax_per']; ?>%</td>
                                                                    <td style="width:10%;" class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['sgst_total'],2);?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3">&nbsp; <strong>Total Bill Amount</strong></td>
                                                                    <td class="text-right" style="border-top:2px solid #000 !important;border-bottom:2px solid #000 !important;">
                                                                        <?php echo number_format($utilObj->NumberFormat2Decimal($noGstTaxAmt + $gstTaxAmt + $memberbillReport['MemberBillSummary']['tax_total']),2); ?>
                                                                    </td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td colspan="3">&nbsp; ARREARS / CREDIT </td> 
                                                                    <td style="width:10%;" class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['op_due_amount'],2); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3">&nbsp; INTEREST ON ARREARS @<?php echo number_format($societyParameters['SocietyParameter']['interest_rate'],2); ?>% P.A.</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['interest_on_due_amount'],2); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3">&nbsp; Discount</td>
                                                                    <td class="text-right"><?php echo number_format($memberbillReport['MemberBillSummary']['discount'],2); ?></td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td colspan="3"><?php  if($memberbillReport['MemberBillSummary']['amount_payable'] < 0) { echo "<strong>&nbsp; Excess Amount Received</strong>";  } else { echo "<strong>&nbsp; Total Due & Payable Amount</strong>"; } ?></td>                                                                
                                                                    <td style="width:10%;" class="text-right"><?php  if($memberbillReport['MemberBillSummary']['amount_payable'] < 0) { echo number_format(str_replace('-','',$memberbillReport['MemberBillSummary']['amount_payable']),2).' Cr'; }
                                                                            else {echo number_format($memberbillReport['MemberBillSummary']['amount_payable'],2).' Dr'; } ?>
                                                                    </td>
                                                                </tr>
                                                                <tr id="">
                                                                    <td colspan="7" >&nbsp; Rupees <?php echo $utilObj->convertToWords(abs($memberbillReport['MemberBillSummary']['amount_payable']));?> Only</td>
                                                                </tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>     

                                    <?php  if(isset($memberbillReport['receipts']) && !empty($memberbillReport['receipts'])){?>                                        
                                        <div class="row1">
                                            <div class="row simple-border-dotted" style="padding:0px;"></div>
                                            <div class="row bill-padding-10">
                                                <div class="receipt-title">Maintenance Receipt</div>
                                                <div class="col-md-8 col-xs-8">Received with thanks from <strong><?php echo $memberbillReport['Member']['member_prefix'].' '.$memberbillReport['Member']['member_name'];?></strong></div>
                                                <div class="col-md-4 col-xs-4 text-right">Unit No :<strong> <?php echo $memberbillReport['Member']['flat_no'];?></strong></div>
                                                <div class="clearfix"></div>
                                                <div class="col-md-12">Details of payments received are as under : <strong>Period : <?= date('d-m-Y',strtotime($postData['MemberBillSummary']['from_date'])).' To '. date('d-m-Y',strtotime($postData['MemberBillSummary']['to_date']))?></strong></div>
                                            </div>                                            
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
                                                                
                                                                foreach($memberbillReport['receipts'] as $key=>$receipt) {
                                                                    $receiptDetails = $receipt['MemberPayment'];
                                                                    $paymentDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    $entryDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    //$billFor = $societyObj->monthWordFormatByBillingFrequency($receiptDetails['bill_month']);
                                                                    
                                                                    $bill_no = $receiptBillInfoArray[$receiptDetails['id']]['bill_no'];
                                                                    $billFor = $societyObj->monthWordFormatByBillingFrequency($receiptBillInfoArray[$receiptDetails['id']]['bill_month']);
                                                                    
                                                                    echo "<tr id=\"\">
                                                                            <td class=\"text-center\">$receiptDetails[receipt_id]</td>
                                                                            <td class=\"text-center\">$entryDate</td>
                                                                            <td class=\"text-center\">$receiptDetails[cheque_reference_number]</td>
                                                                            <td class=\"text-center\">$paymentDate</td>
                                                                            <td>$receiptDetails[member_bank_id], $receiptDetails[member_bank_branch]</td>
                                                                            <td class=\"text-center\">$bill_no For  $billFor </td>
                                                                            <td class=\"text-right\">$receiptDetails[amount_paid]</td>
                                                                           </tr>";

                                                                    $memberTotalPaid += $receiptDetails['amount_paid'];
                                                                    $receiptSr++;
                                                                } ?>
                                                                <tr id="">
                                                                    <td colspan="5" >&nbsp; Rupees <?php echo $utilObj->convertToWords($memberTotalPaid);?> only</td>
                                                                    <td class="font-weight-600">Total</td>
                                                                    <td class="text-right"><?= $memberTotalPaid; ?></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="bill-padding-10">(Subject to Realisation of Cheque)</div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php }?>
                                            <div class="clearfix"></div>
                                            <div class="bill-padding-10">
                                                <strong>Notes:&nbsp;&nbsp;&nbsp;E & O. E</strong>
                                               <ul>
                                                    <li><?php echo isset($societyParameters['SocietyParameter']['bill_note']) ? $societyParameters['SocietyParameter']['bill_note'] : ''; ?> </li>  
                                                    <li class="special-field-text"><?php echo isset($societyParameters['SocietyParameter']['special_field']) ? $societyParameters['SocietyParameter']['special_field'] : ''; ?></li>
                                                </ul>
                                            </div>   
                                            <br>
                                            <div class="clearfix"></div>
                                            <div class="col-md-5 col-xs-5">
                                                <div>GSTIN : <strong><?php echo isset($memberbillReport['Society']['gstin_no']) ? $memberbillReport['Society']['gstin_no'] : '-'; ?></strong></div>
                                                <div>State : Maharastra</div>
                                                <div>S. A .C : 9995</div>
                                            </div> 
                                            <div class="col-md-7 col-xs-7">
                                                <div class="pull-right"><strong>For&nbsp;&nbsp;&nbsp;<?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></strong></div><br>
                                                <br><br><div class="pull-right"><?php echo isset($memberbillReport['Society']['authorised_person']) ? $memberbillReport['Society']['authorised_person'] : 'Authorised Signature'; ?></div>
                                            </div>
                                        <br><br><br><br>
                                        </div>
                                    </div>

                                </div>
                                <?php } }?>
