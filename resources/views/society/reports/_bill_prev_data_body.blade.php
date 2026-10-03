{{-- Carried over from CakePHP app/View/AccountReports/account_bill_summary_with_prev_data.ctp lines 17-352: only the helper calls changed --}}
                                    <?php

                                    if(!empty($billsSummaryDetails)){
                                    foreach($billsSummaryDetails as $memberbillReport) { 
                                    ?>
                                <div class="bill-full-page">
                                    <div class="bill-outer-border">
                                        <div class="row">
                                            <h5 class="text-center"><?php echo isset($memberbillReport['member_information']['Society']['society_name']) ? $memberbillReport['member_information']['Society']['society_name'] : ''; ?></h5>
                                            <div class="address-heading">Registration No. <?php echo isset($memberbillReport['member_information']['Society']['registration_no']) ? $memberbillReport['Society']['registration_no'] : ''; ?> Dated: <?php echo  isset($societyDetails['member_information']['Society']['registration_date']) ? $utilObj->getFormatDate($societyDetails['member_information']['Society']['registration_date'],'d/m/Y') : ''; ?></div>
                                            <div class="address-heading"><?php echo isset($memberbillReport['member_information']['Society']['address']) ? $memberbillReport['member_information']['Society']['address'] : ''; ?></div>
                                        </div>
                                        <div class="row1">
                                            <div class="bill">BILL</div>
                                            <div class="simple-border"></div>
                                            <div class="row bill-member-details-section">
                                                <div class="col-md-9 col-xs-9">
                                                    <div class="row">
                                                        <div class="col-md-3 col-sm-3 col-xs-3">
                                                            <div class="">Unit No: <strong><?php echo isset($memberbillReport['member_information']['Member']['flat_no']) ? $memberbillReport['member_information']['Member']['flat_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Unit Area: <strong><?php echo isset($memberbillReport['member_information']['Member']['area']) ? $memberbillReport['member_information']['Member']['area'] :'' ; ?></strong> SqFt</div>
                                                        </div>                                                        
                                                        <div class="col-md-5 col-sm-5 col-xs-5">
                                                            <div class="">Unit type : <?php if($memberbillReport['member_information']['Member']['unit_type'] == 'C') { echo 'Commercial';} else if($memberbillReport['member_information']['Member']['unit_type'] == 'R') { echo 'Residential';} else if($memberbillReport['Member']['unit_type'] == 'B') { echo 'Both';} else { echo $memberbillReport['Member']['unit_type']; }?> </div>
                                                        </div>

                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Name &nbsp;&nbsp; : &nbsp;<?php echo $memberbillReport['member_information']['Member']['member_prefix'].' '.$memberbillReport['member_information']['Member']['member_name'];?></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill For &nbsp; : &nbsp;<?php echo isset($requestData['reqeust']['month_text']) ? $requestData['reqeust']['month_text']:''; echo isset($memberbillReport['member_information']['Society']['financial_year']) ? " ".$memberbillReport['member_information']['Society']['financial_year'] : ''; ?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Wing &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;<?php echo isset($memberbillReport['member_information']['Member']['wing_name']) ? $memberbillReport['member_information']['Member']['wing_name'] : ''; ?></div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-4 col-xs-4">
                                                            <div class="">Floor No : <?php echo isset($memberbillReport['member_information']['Member']['floor_no']) ? $memberbillReport['member_information']['Member']['floor_no'] :'' ; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3 col-xs-3">
                                                    <div class="row">
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill No &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['bill_current_prev_data']['current_bill_data']['bill_no']) ? $memberbillReport['bill_current_prev_data']['current_bill_data']['bill_no'] :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Bill Date &nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['bill_current_prev_data']['current_bill_data']['bill_generated_date']) ? $utilObj->getFormatDate($memberbillReport['bill_current_prev_data']['current_bill_data']['bill_generated_date'],'d/m/Y') :'' ; ?></strong></div>
                                                        </div>
                                                        <div class="col-md-12 col-xs-12">
                                                            <div class="">Due Date &nbsp;&nbsp;: &nbsp;&nbsp;<strong><?php echo isset($memberbillReport['bill_current_prev_data']['current_bill_data']['bill_due_date']) ? $utilObj->getFormatDate($memberbillReport['bill_current_prev_data']['current_bill_data']['bill_due_date'],'d/m/Y') :'' ; ?></strong></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php
                                                $totalPaidTillBilDate = isset($memberbillReport['total_paid_till_bill_date']) ? $memberbillReport['total_paid_till_bill_date'] : 0;
                                                
                                                $currentBillPrincipalAmt = isset($memberbillReport['bill_current_prev_data']['current_bill_data']['principle']) ? $memberbillReport['bill_current_prev_data']['current_bill_data']['principle'] :0 ;
                                                $currentBillTaxAmt = isset($memberbillReport['bill_current_prev_data']['current_bill_data']['tax']) ? $memberbillReport['bill_current_prev_data']['current_bill_data']['tax'] :0 ; 
                                                $currentBillInterest = isset($memberbillReport['bill_current_prev_data']['current_bill_data']['intetest']) ? $memberbillReport['bill_current_prev_data']['current_bill_data']['intetest'] :0 ; 
                                                
                                                $prevBillPrincipalAmt = isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['principle']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['principle'] :0;
                                                $prevBillTaxAmt = isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['tax']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['tax'] :0;
                                                $prevBillInterestAmt = isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['intetest']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['intetest'] :0;
                                                
                                                $prevBillPrincipalAmtPaid = isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['principle_paid']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['principle_paid'] :0;
                                                $prevBillTaxAmtPaid =  0;// isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['tax_paid']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['tax_paid'] :0;
                                                $prevBillInterestAmtPaid =0;//  isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['interest_paid']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['interest_paid'] :0;
                                                $balanceAmt = isset($memberbillReport['bill_current_prev_data']['prev_bill_data']['total_bal_amt']) ? $memberbillReport['bill_current_prev_data']['prev_bill_data']['total_bal_amt'] :0;
                                                
                                                $prevBillPrincipalAmtPaid = $totalPaidTillBilDate;
                                                
                                                $totalBillAmount =  $prevBillPrincipalAmt+$prevBillInterestAmt+$prevBillTaxAmt;
                                                $totalPaidAmount = $prevBillPrincipalAmtPaid+$prevBillTaxAmtPaid+$prevBillInterestAmtPaid;
                                                
                                                $principalBal = $prevBillPrincipalAmt - $prevBillPrincipalAmtPaid;
                                                $interestBal  = $prevBillInterestAmt-$prevBillInterestAmtPaid;
                                                $taxBal= $prevBillTaxAmt-$prevBillTaxAmtPaid;
                                                
                                                $currentBillTotal = $currentBillPrincipalAmt+$currentBillTaxAmt+$currentBillInterest;
                                                $totalBal = $principalBal+$interestBal+$taxBal;
                                                $totalDueAmt = $totalBal+$currentBillTotal ;
                                                
                                            ?>

                                            <div class="row1">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th></th>
                                                                        <th>Previous Bill Amount(Rs)</th>
                                                                        <th>Received Amount(Rs)</th>
                                                                        <th>Balance Amount(Rs)</th>
                                                                        <th>Current Bill Amount(Rs)</th>
                                                                        <th class="text-center">Total Due Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>Principal</td>
                                                                        <td><?= $prevBillPrincipalAmt;?></td>
                                                                        <td><?= $prevBillPrincipalAmtPaid;?></td>
                                                                        <td><?= $principalBal;?></td>
                                                                        <td><?= $currentBillPrincipalAmt; ?></td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Interest</td>
                                                                        <td><?= $prevBillInterestAmt; ?></td>
                                                                        <td><?= $prevBillInterestAmtPaid;?></td>
                                                                        <td><?= $interestBal;?></td>
                                                                        <td><?= $currentBillInterest;?></td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tax</td>
                                                                        <td><?= $prevBillTaxAmt;?></td>
                                                                        <td><?= $prevBillTaxAmtPaid; ?></td>
                                                                        <td><?= $taxBal;?></td>
                                                                        <td><?= $currentBillTaxAmt; ?></td>
                                                                        <td></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Total</td>
                                                                        <td><?= $totalBillAmount;?></td>
                                                                        <td><?= $totalPaidAmount;?></td>
                                                                        <td><?= $totalBal;  ?></td>
                                                                        <td><?= $currentBillTotal; ?></td>
                                                                        <td rowspan="4" ><?= ($totalDueAmt < 0) ? abs($totalDueAmt).' Cr': abs($totalDueAmt).' Dr'; ?> </td>
                                                                    </tr>
                                                                    <tr>
                                                                    <td colspan="4" >&nbsp; Rupees <?php echo $utilObj->convertToWords(abs($totalDueAmt));?> Only</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row1">
                                                <div class="col-md-6">
                                                    <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="text-center" >Sr.</th>
                                                                        <th class="text-center">Particular of Charges</th>
                                                                        <th class="text-center">Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php 
                                                                          if(!empty($memberbillReport['generated_bill_tariffs'])){
                                                                              $totalTariffs = 0;
                                                                              $i = 1;
                                                                              foreach($memberbillReport['generated_bill_tariffs'] as $tarrifdata){ 
                                                                                  $tariffAmt = $tarrifdata['MemberBillGenerate']['amount'];
                                                                                if($tariffAmt > 0){
                                                                                    $totalTariffs = $totalTariffs + $tariffAmt;
                                                                          ?>
                                                                          <tr>
                                                                              <td><?= $i++;?></td>
                                                                              <td><?= $tarrifdata['SocietyLedgerHeads']['title'];?></td>
                                                                              <td><?= $tariffAmt;?></td>
                                                                          </tr>
                                                                    <?php 
                                                                                }
                                                                            } ?>
                                                                          <tr>
                                                                              <td></td>
                                                                              <td>Added Interest</td>
                                                                              <td><?=  $currentBillInterest ;?></td>
                                                                          </tr>
                                                                          <tr>
                                                                              <td></td>
                                                                              <td>Added Tax</td>
                                                                              <td><?=  $currentBillTaxAmt ;?></td>
                                                                          </tr>
                                                                          <tr>
                                                                              <td></td>
                                                                              <td>Total Current Charges</td>
                                                                              <td><?=  $currentBillInterest+ $currentBillTaxAmt + $totalTariffs;?></td>
                                                                          </tr>                                                                                                          
                                                                         <?php  
                                                                          }
                                                                        
                                                                    ?>
                                                                </tbody>

                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div style="padding: 0px;">
                                                        <div class="table-wrap">
                                                            <div class="table-responsive1">
                                                                <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                    <thead>
                                                                        <tr>
                                                                            <th colspan="4" class="text-center">Annual Summary</th>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Period</th>
                                                                            <th>Bill Amount</th>
                                                                            <th>Received Amount</th>
                                                                            <th>Balance Amount  </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php 
                                                                          if(!empty($memberbillReport['bill_ledger_data'])){
                                                                              if(!$memberbillReport['member_transfer']){
                                                                                $openingBalance = $memberbillReport['member_information']['Member']['op_principal'] + $memberbillReport['member_information']['Member']['op_interest']+ $memberbillReport['member_information']['Member']['op_interest']+$memberbillReport['Member']['supplementary_principal'] + $memberbillReport['member_information']['Member']['supplementary_penality']+ $memberbillReport['member_information']['Member']['supplementary_interest']+ $memberbillReport['member_information']['Member']['supplementary_tax'];
                                                                              }
                                                                              else {
                                                                                $openingBalance = 0;
                                                                              }
                                                                                
                                                                              $i = 0;
                                                                              $billAmtSum= 0;
                                                                              $paidSum=0;
                                                                              $total = 0;
                                                                              foreach($memberbillReport['bill_ledger_data'] as $data){ 
                                                                                  
                                                                                  $billAmount = $data['bill_amount'];
                                                                                  $paidAmount = $data['paid_amount'];
                                                                                  $creditedAmt = $data['jv_credited'];
                                                                                  $debitedAmt = $data['jv_debited'];
                                                                                  
                                                                                  $billAmtSum = $billAmtSum+$billAmount;
                                                                                  $paidSum = $paidSum+$paidAmount+$creditedAm-$debitedAmtt;
                                                                                  if($i == 0){
                                                                                    $total = $openingBalance+$billAmount-$paidAmount;    
                                                                                    $billAmount = $billAmount+$openingBalance;
                                                                                  }
                                                                                  else{
                                                                                    $total = ($total+$billAmount)-$paidAmount;
                                                                                  }
                                                                                  $sign = ($total < 0) ? 'Cr':'Dr';
                                                                              ?>
                                                                                    <tr>
                                                                                        <td><?=  $data['month'] ?></td>
                                                                                        <td><?= $billAmount;?></td>
                                                                                        <td><?= $paidAmount;?></td>
                                                                                        <td><?= abs($total).' '.$sign ?></td>
                                                                                    </tr>
                                                                              <?php
                                                                                    $i++;
                                                                                }
                                                                                $sign = ($total < 0) ? 'Cr':'Dr';
                                                                                ?>
                                                                                    <tr>
                                                                                        <td><?=  'Total' ?></td>
                                                                                        <td><?= $billAmtSum+$openingBalance;?></td>
                                                                                        <td><?= $paidSum;?></td>
                                                                                        <td><?= abs($total).' '.$sign ?></td>
                                                                                    </tr>                                                                                    
                                                                          <?php }
                                                                          ?>
                                                                        <tr>
                                                                            
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                            </div>      
                                            <br><br>
                                            <div class="row1">
                                                <div class="col-md-6">
                                                    <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table table-print-all-bill padding-th-none-zero padding-td-none-zero table-bordered print-custom-table-border">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Receipt No.</th>
                                                                        <th>Amount</th>
                                                                        <th>Cheque No.</th>
                                                                        <th>Cheque Date</th>
                                                                        <th>Bank Name</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php 
                                                                    if(!empty($memberbillReport['member_information']['MemberPayment'])){
                                                                        foreach($memberbillReport['member_information']['MemberPayment'] as $paymentData) {
                                                                    ?>
                                                                    <tr>
                                                                        <td><?= $paymentData['receipt_id'];?></td>
                                                                        <td><?= $paymentData['amount_paid'];?></td>
                                                                        <td><?= $paymentData['cheque_reference_number'];?></td>
                                                                        <td><?= isset($paymentData['cheque_date']) ? date('d-m-Y',strtotime($paymentData['cheque_date'])) : '';?></td>
                                                                        <td><?= '';?></td>
                                                                    </tr>
                                                                <?php   }
                                                                } ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                            <br><br><br>
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
