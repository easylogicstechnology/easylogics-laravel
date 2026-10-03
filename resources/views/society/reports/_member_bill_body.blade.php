{{-- Carried over from CakePHP app/View/SocietyBills/print_member_bills.ctp (lines 1-18 and 36-431): only helper calls changed - $_GET is $q, $this->Html->image is $html->image, the three unquoted array keys are quoted --}}
<?php
$paymentModeArray = array(1=>'Cash',3=>'Cheque',2=>'NEFT',4=>'Other',5=>'upipayment');
$societyLedgerHeadIds = array_keys($societyLedgerHeadTitleList);
$monthArray = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December',);
$showBillsInReceipt = 0;
if(isset($societyParameters['SocietyParameter']['show_bills_in_receipt']) && $societyParameters['SocietyParameter']['show_bills_in_receipt'] == 1){
	$showBillsInReceipt = 1;
}

$showAllBillInTarrif = $societyParameters['SocietyParameter']['show_all_tariff_name'];

$signatureImagePath = '';
if(!empty($societyParameters['SocietyParameter']['signature_image_path'])){
	$signatureImagePath = strchr($societyParameters['SocietyParameter']['signature_image_path'],'society_signature');
}

//  echo numberTowordsEnglish('12312.00');exit;
?>
                            <div id="society_print_bill">
                                <div id="page-break1">

                                    <?php
                                    
                                    foreach($printBillDetails as $memberId=>$printBill) {
                                        $societyDetails = $printBill['memberDetails']['Society'];
                                        $memberDetails = $printBill['Member'];
                                        $buildingDetails = $printBill['memberDetails']['Building'];
                                        $wingDetails = $printBill['memberDetails']['Wing'];
                                        $billSummary = $printBill['MemberBillSummary'];
                                        $tariff = $printBill['tariff'];
                                        $memberReceipts = $printBill['receipts'];
                                        $jointMember = $printBill['Member']['joint_member_name'];
                                        $printHeader = ($billSummary['bill_type'] == "reg") ? "Maintenance":"Supplementary";
                                    ?>
                                    <div class="<?php echo ($billFormat == 'half') ? 'society-print-bill-half' : 'society-print-bill'; ?>">
                                        <div class="bill-outer-border">
                                            <div class="row">
                                                <h5 class="text-center"><?php echo $societyDetails['society_name'];?></h5>
                                                <div class="address-heading">Registration Number. <?php echo $societyDetails['registration_no'];?> Dated: <?php echo (!empty($societyDetails['registration_date']) ? date('d/m/Y',strtotime($societyDetails['registration_date'])):'');?></div>
                                                <div class="address-heading"><?php echo $societyDetails['address'];?></div>
                                            </div>
                                        <div class="bill"><?php echo $printHeader; ?> Bill</div>
                                            <div class="simple-border"></div>
                                            <div class="col-md-9 col-xs-9">
                                                <div class="row">
                                                    <div class="col-md-3 col-sm-3 col-xs-3">
                                                    <?php
                                                    if($q['unit_type']=='Unit No'){
                                                    $q['unit_type']='Unit Number';
                                                    }
                                                    ?>
                                                        <div class=""><?php echo ($q['unit_type'] == 'Flat-Shop') ? (($memberDetails['commercial'] != '0' && $memberDetails['commercial'] != '') ? 'Shop Number' : 'Flat Number') : $q['unit_type']; ?> : <strong><?php echo $memberDetails['flat_no'];?></strong> </div>
                                                    </div>
                                                    <?php if(!in_array('unit-area',$q['remove_details'])){ ?>
                                                    <div class="col-md-4 col-sm-4 col-xs-4">
                                                        <div class="">Unit Area : <?php echo $memberDetails['area'];?> Sq.Ft.</div>
                                                    </div>
                                                    <?php } ?>
                                                    <?php if(!in_array('unit-type',$q['remove_details'])){ ?>
                                                    <div class="col-md-5 col-sm-5 col-xs-5">
                                                        <div class="">Unit type : <?php if($memberDetails['unit_type'] == 'C') { echo 'Commercial';} else if($memberDetails['unit_type'] == 'R') { echo 'Residential';} else if($memberDetails['unit_type'] == 'B') { echo 'Both';} else { echo $memberDetails['unit_type']; }?></div>
                                                    </div>
                                                    <?php } ?>
                                                </div>                                                    
                                                <div class="row">
                                                    <div class="col-md-12 col-xs-12">
                                                        <div class="">Name &nbsp;&nbsp; : &nbsp;<?php echo $memberDetails['member_prefix'].' '.$memberDetails['member_name'];?></div>
                                                    </div>
                                                <?php  if(!empty($jointMember)){ ?>
                                                    <div class="row">                                                
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Joint Member : <?php echo $jointMember;?></div>
                                                        </div>
                                                    </div>
                                                       <?php  } ?>                                                    
                                                    <div class="col-md-12 col-xs-12">
                                                        <div class="">Bill For &nbsp; : &nbsp;<?php echo $societyObj->monthWordFormatByBillingFrequency($billSummary['month']);?> <?php echo date('Y',strtotime($billSummary['bill_generated_date']));?></div>
                                                    </div>                                                    
                                                </div>
                                                <div class="row">
                                                <?php if(!in_array('Building No',$q['remove_details']) && !empty($wingDetails['wing_name'])){ ?>
                                                    <div class="col-md-4 col-sm-4 col-xs-4">
                                                        <div class="">WING : <?php echo $wingDetails['wing_name'];?></div>
                                                    </div>
                                                    <?php  } ?>
                                                    <?php if(!in_array('floor-number',$q['remove_details'])){ ?>                                                
                                                    <div class="col-md-4 col-sm-4 col-xs-4">
                                                        <div class="">Floor Number : <?php echo $memberDetails['floor_no'];?></div>
                                                    </div>
                                                    <?php  } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-xs-3">
                                                <div class="row">
                                                    <div class="col-md-12 col-xs-12">
                                                        <div class=""><strong>Bill Number &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<?php echo $billSummary['bill_no'];?></strong></div>
                                                    </div>
                                                    <div class="col-md-12 col-xs-12">
                                                        <div class=""><strong>Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<?php echo date('d/m/Y',strtotime($billSummary['bill_generated_date']));?></strong></div>
                                                    </div>
                                                    <div class="col-md-12 col-xs-12">
                                                        <div class=""><strong>Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<?php echo date('d/m/Y',strtotime($billSummary['bill_due_date']));?></strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!--<div class="col-md-3 col-xs-3">-->
                                            <!--    <div class="row">-->
                                            <!--        <div class="col-md-12 col-xs-12">-->
                                            <!--            <div class=""><strong>Buiding No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<?php echo $billSummary['bill_no'];?></strong></div>-->
                                            <!--        </div>-->
                                            <!--        <div class="col-md-12 col-xs-12">-->
                                                        
                                            <!--        </div>-->
                                            <!--        <div class="col-md-12 col-xs-12">-->
                                                        
                                            <!--        </div>-->
                                            <!--    </div>-->
                                            <!--</div>                                            -->
                                            <div class="row1">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="">
                                                            <table class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
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
                                                                    foreach($tariff as $key=>$indTariff) {
                                                                    if(empty($showAllBillInTarrif) && abs($indTariff['MemberBillGenerate']['amount']) == 0) {
                                                                        continue;
                                                                    }
                                                                    // if($indTariff['MemberBillGenerate']['amount'] > 0) {
                                                                            $memberBillTariff[] = $indTariff['MemberBillGenerate']['ledger_head_id'];
                                                                            $ledgerHead =  $ledgerHeadDetails[$indTariff['MemberBillGenerate']['ledger_head_id']];
                                                                            $ledgerAmount = $indTariff['MemberBillGenerate']['amount'];
                                                                            echo "<tr>
                                                                                    <td style='text-align:center;'>$tariffSr</td>
                                                                                    <td  colspan=\"3\" >&nbsp; $ledgerHead</td>
                                                                                    <td style='width:10%;' class='text-right'>";
                                                                            echo number_format($ledgerAmount,2);
                                                                            echo "</td>
                                                                                    </tr>";
                                                                            $tariffSr++;
                                                                    // }
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
                                    <td colspan="2" rowspan="<?php echo ($q['bill_format_type'] != '1' ? "5" : "4") ?>" style="width:50% !important;"><?php if(trim($billSummary['remarks']) != ''){ ?>&nbsp;&nbsp;<?php echo $billSummary['remarks'];?><?php } ?></td>
                                                                    <td colspan="2" >&nbsp; Total</td>
                                                                    <td class="text-right" style="font-weight:bold;"><b>
                                                                        <?php 
                                                                            $totalTariffAmount =  $billSummary['monthly_amount'] - $billSummary['tax_total'];
                                                                            echo number_format($totalTariffAmount,2);
                                                                        ?>
                                                                        </b></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" >&nbsp; Interest</td>
                                                                    <td class="text-right"><?php echo number_format($billSummary['interest_on_due_amount'],2); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" >&nbsp; Less: Adjustment</td>
                                                                    <td class="text-right"><?php echo number_format($billSummary['principal_adjusted']+$billSummary['interest_adjusted']+$billSummary['discount'],2);?></td>
                                                                </tr>
                                                                <?php if($q['bill_format_type'] != '1') { ?>                                                                
                                                                <tr>
                                                                    <td >&nbsp; Principal Arrears</td>                                                                    
                                                                    <td class="text-right" >
                                                                        <?php
                                                                        $totalArrears = $billSummary['op_principal_arrears'];
                                                                        if($totalArrears < 0) { $posNeg = '<span class="notranslate">Cr</span>'; }
                                                                        else { $posNeg = '<span class="notranslate">Dr</span>'; }
                                                                        $totalArrears = abs($totalArrears);
                                                                        echo number_format($totalArrears,2).' '.$posNeg;
                                                                        ?>                                                                      
                                                                    </td>  
                                                                    <td class="text-right" rowspan="2">
                                                                        <?php if($billSummary['op_due_amount'] < 0) { $posNeg = '<span class="notranslate">Cr</span>'; }
                                                                        else { $posNeg = '<span class="notranslate">Dr</span>'; }
                                                                        $billSummary['op_due_amount'] = abs($billSummary['op_due_amount']);
                                                                        echo number_format($billSummary['op_due_amount'],2).' '.$posNeg;
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>&nbsp; Interest Arrears</td>
                                                                    <td class="text-right"><?php echo $utilObj->CreditDebitAmountCheck($billSummary['op_interest_arrears']) ;?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" >&nbsp; <span class='font-weight-bold'>Rupees <?php echo ucwords($wordsFunction(abs($billSummary['amount_payable'])));?> Only</span></td>
                                                                    <td colspan="2" >
                                                                    <?php  if($billSummary['amount_payable'] < 0) { echo "<span class='font-weight-bold'>&nbsp; Excess Amount Received</span>";  } else { echo "<span class='font-weight-bold'>&nbsp; Total Due Amount & Payable</span>"; } ?>
                                                                    </td>
                                                                    <td class="text-right">
                                                                    <?php  if($billSummary['amount_payable'] < 0) { $posNeg = '<span class="notranslate">Cr</span>'; }
                                                                    else { $posNeg = '<span class="notranslate">Dr</span>'; }
                                                                    $billSummary['amount_payable'] = abs($billSummary['amount_payable']);

                                                                    echo "<span class='font-weight-bold'>".number_format($billSummary['amount_payable'],2).' '.$posNeg.'</span>';
                                                                    ?>
                                                                    </td>
                                                                </tr>
                                                                <?php }  else {?>
                                                                 <tr>
                                                                    <td colspan="2" >&nbsp; Arrears/Credit</td>                                                                    
                                                                    <td  class="text-right">
                                                                        <?php if($billSummary['op_due_amount'] < 0) { $posNeg = '<span class="notranslate">Cr</span>'; }
                                                                        else { $posNeg = '<span class="notranslate">Dr</span>'; }
                                                                        $billSummary['op_due_amount'] = abs($billSummary['op_due_amount']);
                                                                        echo number_format($billSummary['op_due_amount'],2).' '.$posNeg;
                                                                        ?>
                                                                    </td>                                                                     
                                                                 </tr>
                                                                <tr>
                                                                    <td colspan="2">&nbsp; <span class='font-weight-bold'>Rupees <?php echo ucwords($wordsFunction($billSummary['amount_payable']));?> Only</span></td>
                                                                    <td colspan="2">
                                                                    <?php  
                                                                        if($billSummary['amount_payable'] < 0) {
                                                                            echo "<span class='font-weight-bold'>&nbsp; Excess Amount Received</span>"; 
                                                                        }
                                                                        else {
                                                                            echo "<span class='font-weight-bold'>&nbsp; Total Due Amount & Payable</span>"; 
                                                                        } ?>
                                                                    </td>
                                                                    <td class="text-right">
                                                                    <?php  if($billSummary['amount_payable'] < 0) { $posNeg = '<span class="notranslate">Cr</span>'; }
                                                                    else { $posNeg = '<span class="notranslate">Dr</span>'; }
                                                                    $billSummary['amount_payable'] = abs($billSummary['amount_payable']);
                                                                    echo "<span class='font-weight-bold'>".number_format($billSummary['amount_payable'],2).' '.$posNeg.'</span>';
                                                                    ?>
                                                                    </td>
                                                                </tr>                                                                 
                                                                <?php  } ?>                                                                            

                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-12 col-xs-12">
                                                <strong>Notes: </strong>
                                                <div><?php echo $societyParameters['SocietyParameter']['bill_note']; ?> </div> 
                                            </div>
                                            <div class="clearfix"></div>

<?php
$check_div = empty($memberReceipts)? 'none':'block';
?>



                                            <div class="col-md-12 col-xs-12">
                                                <p class="pull-right">For <?php echo $societyDetails['society_name'];?>&nbsp;&nbsp; </p>
                                                <br> <br>
                                                <?php if (!empty($signatureImagePath)) { ?>
                                                <div style="clear:both;text-align:right;">
                                                    <?php echo $html->image($signatureImagePath, array('height' => '45', 'style' => 'display:inline-block;', 'fullBase' => true, 'plugin' => false)); ?>&nbsp;&nbsp;
                                                </div>
                                                <?php } ?>
                                                <p class="pull-right" style="margin-top:<?php echo !empty($signatureImagePath) ? '0' : '20px'; ?>;" ><?php echo isset($societyDetails['authorised_person']) ? $societyDetails['authorised_person'] : 'Authorised Signature'; ?>&nbsp;&nbsp;</p>
                                            </div>
                                            <div class="clearfix"></div>
                                            <div class="col-md-12 col-xs-12 special-field-text"><?php echo $societyParameters['SocietyParameter']['special_field']; ?></div>
                                            <br>
                                            <div class="row simple-border-dotted" style="padding:0px;"></div>

                                            <div class="row bill-padding-5" style="display:<?php echo  $check_div; ?>">
                                                <div class="receipt-title"><strong>Receipt</strong></div>
                                                <div class="col-md-8 col-xs-8">Received with thanks from <strong><?php echo $memberDetails['member_prefix'].' '.$memberDetails['member_name'];?></strong></div>
                                                <div class="col-md-4 col-xs-4 text-right"><?php echo ($q['unit_type'] == 'Flat-Shop') ? (($memberDetails['commercial'] != '0' && $memberDetails['commercial'] != '') ? 'Shop Number' : 'Flat Number') : $q['unit_type']; ?> :<strong> <?php echo $memberDetails['flat_no'];?></strong></div>
                                                <div class="clearfix"></div>
                                                <?php if(isset($receiptPeriod['From']) && !empty($receiptPeriod['From']) && isset($receiptPeriod['To']) && !empty($receiptPeriod['To'])) { ?>
                                                <div class="col-md-12">Details of payments received are as under : <strong>Period : <?php echo date('d/m/Y',strtotime($receiptPeriod['From']));?> To <?php echo date('d/m/Y',strtotime($receiptPeriod['To']));?></strong></div>
                                                <?php } else { ?>
                                                <div class="col-md-12">Details of payments received are as under : <strong>Period : 01/04/<?php echo date('Y'); ?> To Till date</strong></div>
                                                <?php } ?>
                                            </div>
                                            <div class="row1" style="display:<?php echo  $check_div; ?>">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive">
                                                            <table  class="table padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:6%;" class="text-center">Receipt</th>
                                                                        <th style="width:10%;" class="text-center">Date</th>
                                                                         <th style="width:8%;" class="text-center">Payment Mode.</th>
                                                                        <th style="width:8%;" class="text-center">Cheqe No.</th>
                                                                        <th style="width:10%;" class="text-center">Chq Date</th>
                                                                        <th class="text-center">Bank & Branch</th>
                                                                        <th class="text-center">Narration</th>                                                                        
                                                                        <?php if($showBillsInReceipt == 1) {?><th style="width:18%;" class="text-center">Towards bill Number.</th><?php } ?>
                                                                        <th style="width:8%;" class="text-center">Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                <?php
                                                                $receiptSr = 1;
                                                                $memberTotalPaid = 0;

                                                                foreach($memberReceipts as $key=>$receipt) {
//if ($receiptSr > 1) 
//break;                                                            
                                                        
                                                                    $receiptDetails = $receipt['MemberPayment'];
                                                                    $paymentMode = $paymentModeArray[$receiptDetails['payment_mode']];
                                                                    $paymentDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    $entryDate = date('d/m/Y',strtotime($receiptDetails['payment_date']));
                                                                    $chequeDate = (!is_null($receiptDetails['payment_date']) && !empty($receiptDetails['payment_date'])) ? date('d/m/Y',strtotime($receiptDetails['payment_date'])) : '';
                                                                    if (!is_null($receiptDetails['payment_date']) && !empty($receiptDetails['payment_date'])){
                                                                        
                                                                        if ($chequeDate > $paymentDate) {
                                                                          $chequeDate = $paymentDate;
                                                                        }
                                                                    }
                                                                    $billFor = $societyObj->monthWordFormatByBillingFrequency($billSummary['month']);	// for $billFor removed from the bill generated id
								    if (!empty($receiptDetails['cheque_reference_number']) && !empty($receiptDetails['credited_date']))
									$rcpt = '';
									if($showBillsInReceipt == 1) { 
										$rcpt = "<td class=\"text-center\">".$receiptDetails['bill_generated_id']."</td>"; 
									} 
                                                                    echo "<tr id=\"\">
                                                                            <td class=\"text-center\">$receiptDetails[receipt_id]</td>
                                                                            <td class=\"text-center\">$paymentDate</td>
                                                                             <td class=\"text-center\">$paymentMode</td>
                                                                            <td class=\"text-center\">$receiptDetails[cheque_reference_number]</td>
                                                                            <td class=\"text-center\">$chequeDate</td>
                                                                            <td>".$banks[$receiptDetails['member_bank_id']].", $receiptDetails[member_bank_branch]</td>
                                                                            ".$rcpt."
                                                                            <td class=\"text-right\">".$receiptDetails['narration']."</td>                                                                            
                                                                            <td class=\"text-right\">".number_format($receiptDetails['amount_paid'])."</td>
                                                                           </tr>";

                                                                    $memberTotalPaid += $receiptDetails['amount_paid'];
                                                                    $receiptSr++;
                                                                } ?>
                                                                    <tr id="">

                                                                        <td colspan="4" >&nbsp; Rupees <?php echo ucwords($wordsFunction($memberTotalPaid));?> only</td>
                                                                        <td class="font-weight-600">Total</td>
                                                                        <td class="text-right"><?php echo number_format($memberTotalPaid); ?></td>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row" style="display:<?php echo  $check_div; ?>">
                                                <div class="col-md-5">
                                                    <div class="bill-padding-10">(Subject to Realisation of Cheque)</div>
                                                </div>
                                                <div class="col-md-7">
                                                    <p class="pull-right">For <?php echo $societyDetails['society_name'];?>&nbsp;&nbsp; </p>
                                                    <br> <br> <br>
                                                    <?php if (!empty($signatureImagePath)) { ?>
                                                    <div style="clear:both;text-align:right;">
                                                        <?php echo $html->image($signatureImagePath, array('height' => '45', 'style' => 'display:inline-block;', 'fullBase' => true, 'plugin' => false)); ?>&nbsp;&nbsp;
                                                    </div>
                                                    <?php } ?>
                                                    <p class="pull-right" style="marigin-top:20px;" ><?php echo isset($societyDetails['authorised_person']) ? $societyDetails['authorised_person'] : 'Authorised Signature'; ?>&nbsp;&nbsp;</p>
                                                </div>
                                            </div>
                                            <div class="row">
                                           
</div><?php /* Payment QR moved from the right to the left, opposite the
             signature which stays on the right. */ ?>
<div style="width:100%; text-align:left;">
<?php
if (!empty($societyParameters['SocietyParameter']['scanner_image_path'])) {
    echo $html->image(
        strchr($societyParameters['SocietyParameter']['scanner_image_path'], 'payment_scanner'),
        array(
            'height'   => '100',
            'width'    => '250',
            'style'    => 'display:inline-block;',
            'fullBase' => true,
            'plugin'   => false
        )
    );
}
?>

                                                </span>
                                            </div>                                             
                                        </div>
                                    </div>
                                <?php } ?>
                                </div>
                            </div>
                        </div>
