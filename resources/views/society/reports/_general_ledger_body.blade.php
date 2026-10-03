{{-- Carried over from CakePHP app/View/AccountReports/account_general_ledger.ctp lines 105-270: only the helper calls changed --}}
                            <div id="print_general_ledger">
                                <div class="print-general-ledger">
                                    <div class="row">
                                        <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                    </div>
                                    <br>
                                    <div class="row1">
                                        <div class="report-bill">General Ledger</div>
                                        <div class="report-bill"></div>
                                        <div class="text-center"></div>
                                        <div class="report-bill-outer-section">
                                            <div class="table-wrap1">
                                                <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                    <thead>
                                                        <tr>
                                                            <th width="12%" rowspan="2" class="text-center">Date</th>
                                                            <th colspan="2" class="text-center">Reference Type</th>
                                                            <th colspan="2"  class="text-center">Cheque</th>
                                                            <th rowspan="2" class="text-center">Dr. Amount</th>
                                                            <th rowspan="2" class="text-center">Cr. Amount</th>
                                                            <th rowspan="2" class="text-center">Balance</th>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-center">Type</th>
                                                            <th class="text-center">No.</th>
                                                            <th class="text-center">No.</th>
                                                            <th class="text-center">Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php //TODO
                                                        $num = count($societyLedgerHeadsData);
                                                        if(isset($societyLedgerHeadsData) && $num > 0){

                                                        foreach($societyLedgerHeadsData as $ledgerHeadId => $ledgerHeadDetail){
                                                            $societyLedgerHeadsDetails = $ledgerHeadDetail['data'];
                                                            $title = $ledgerHeadDetail['title'];
                                                            
                                                            $totalDebit = 0;
                                                            $totalCredit = 0;
        if($societyLedgerHeadsDetails['AccountHead']['title']=='Income'){
   
            $openingCredit=0;
            $societyLedgerHeadsDetails['SocietyLedgerHeads']['opening_amount']=0;
            $openingBalance=0;
        }
                                                        ?>
                                                            <tr><td colspan="8"><strong><?php echo $title;?></strong></td></tr>
                                                        <?php
                                                        $indBalance = 0;
                                                            if(isset($societyLedgerHeadsDetails['txn_type']) && $societyLedgerHeadsDetails['txn_type'] == 'debit') {
                                                            $openingDebit = !empty($societyLedgerHeadsDetails['SocietyLedgerHeads']['opening_amount']) ? $societyLedgerHeadsDetails['SocietyLedgerHeads']['opening_amount'] : 0;
                                                            $openingCredit = 0;



        if($societyLedgerHeadsDetails['AccountHead']['title']=='Expense'){
         
            $openingDebit=0;
            $openingBalance=0;
        }




                                                            $openingBalance = $openingDebit;
                                                            $posReg = ' Dr.';
                                                            $totalDebit += $openingDebit;
                                                            $indBalance += $openingBalance;
                                                        } else if(isset($societyLedgerHeadsDetails['txn_type']) && $societyLedgerHeadsDetails['txn_type'] == 'credit') {
                                                            $openingCredit = !empty($societyLedgerHeadsDetails['SocietyLedgerHeads']['opening_amount']) ? $societyLedgerHeadsDetails['SocietyLedgerHeads']['opening_amount'] : 0;
                                                            $openingDebit = 0;
                                                            $openingBalance = $openingCredit;
                                                            $posReg = ' Cr.';
                                                            $totalCredit += $openingCredit;
                                                            $indBalance -= $openingBalance;
                                                        }
                                                        ?>

                                                        <tr id="">
                                                            <td class="opening-bal"><?php echo $session->read('Auth.formated_year_start_date');//(date('m') < 4) ? '01/04/'.date('Y', strtotime('-1 year')) : '01/04'.date('Y');   ?></td>
                                                            <td class="opening-bal text-right" colspan="4">Opening Balance</td>
                                                            <td class="opening-bal text-right"><?php 
                    if($societyLedgerHeadsDetails['AccountHead']['title']!='Expense' OR $societyLedgerHeadsDetails['AccountHead']['title']!='Income'){
                          echo number_format($openingDebit,2); 
                                                             }else{
                                                                $openingDebit=0;
                                                                $openingCredit=0;

                                                                $openingBalance=0;
                                                             }




                                                           ?></td>
                                                            <td class="opening-bal text-right"><?php echo number_format($openingCredit,2);?></td>
                                                            <td class="opening-bal text-right"><?php echo number_format($openingBalance,2).''.$posReg;?></td>
                                                        </tr>
                                                        <?php if(isset($societyLedgerHeadsDetails['ledgerPaymentData'])) {
                                                        $particularNote = 'To Payment';

                                                        if($societyLedgerHeadsDetails['SocietyLedgerHeads']['is_in_bill_charges'] == 1) {
                                                            $particularNote = 'By Bill';
                                                        }

                                                        

                                                        foreach($societyLedgerHeadsDetails['ledgerPaymentData'] as $paymentData) {
                                                            $credit = 0;
                                                            $debit = 0;

                                                            $particular = isset($paymentData['particularNote']) && !empty($paymentData['particularNote']) ? $paymentData['particularNote'] : $particularNote.' '.$paymentData['flat_no'];
                                                            

                                                            if($paymentData['txn_type'] == 'credit') {
                                                               $credit = $paymentData['amount'];
                                                               $indBalance = $indBalance-$credit;
                                                               $posNeg = 'Cr';
                                                               $totalCredit += $credit;
                                                            } else if($paymentData['txn_type'] == 'debit') {
                                                               $debit = $paymentData['amount'];
                                                               $indBalance = $indBalance+$debit;
                                                               $posNeg = 'Dr';
                                                               $totalDebit += $debit;
                                                            }   
                                                            if($indBalance < 0) {
                                                                $posNeg = 'Cr';
                                                            } else { $posNeg = 'Dr';}
                                                        ?>
                                                        <tr id="">
                                                            <td><?php echo $paymentData['payment_date'];?></td>
                                                            <td><?php echo isset($particular) ? $particular."<br>".$paymentData['notes'] : ''; ?></td>
                                                            <td><?php echo isset($paymentData['reference_no']) ? $paymentData['reference_no'] : '';?></td>
                                                            <td><?php echo isset($paymentData['cheque_no']) ? $paymentData['cheque_no'] : '';?></td>
                                                            <td><?php echo isset($paymentData['cheque_date']) ? $paymentData['cheque_date'] : '';?></td>
                                                            <td class="text-right"><?php echo number_format($debit,2);?></td>
                                                            <td class="text-right"><?php echo number_format($credit,2);?></td>
                                                            <td class="text-right"><?php echo number_format((float)abs($indBalance),2,'.', '').' '.$posNeg;?></td>
                                                        </tr>
                                                        <?php }} ?>
                                                        <?php
                                                        if($indBalance < 0) {
                                                            $posNeg = 'Cr';
                                                        } else { $posNeg = 'Dr';}
                                                        ?>
                                                        <tr style="background-color: #d2d2d2;">
                                                            <td colspan="5" class="text-right">Total</td>
                                                            <td class="text-right"><?php 
                                                            echo number_format($totalDebit,2);
                                                            ?></td>
                                                            <td class="text-right"><?php echo number_format($totalCredit,2);?></td>
                                                            <td class="text-right"><?php echo number_format((float)abs($indBalance),2,'.', '').' '.$posNeg;?></td>
                                                        </tr>
                                                <?php  } }else{?>
                                                        <tr><td colspan="8"><center>No Data found. </center></td></tr>
                                                <?php }?>
                                                    </tbody> 
                                                </table> 
                                            </div>
                                        </div>  
                                    </div>  
                                </div>  
                            </div>  
