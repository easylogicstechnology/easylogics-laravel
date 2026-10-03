{{-- PHP 8 patches: array_sum / max on a member with no ledger rows. Carried over from CakePHP app/View/AccountReports/account_member_ledger_default.ctp lines 169-606: only the helper calls changed --}}
                            <div id="print_member_ledger">
                                <div class="print-member-ledger">
                                    <div class="row">
                                        <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                    </div>
                                    <br>
                                    <div class="row1">
                                        <div class="report-bill"></div>
                                        <div class="report-bill">Member Ledger-<?php
                                          if($postData['MemberBillSummary']['report_type'] == 'Default') echo "Regular - Supplementary";
                                          if($postData['MemberBillSummary']['report_type'] == 'reg') echo "Regular";
                                          if($postData['MemberBillSummary']['report_type'] == 'sup') echo "Supplementary";
                                          if($postData['MemberBillSummary']['report_type'] == 'Summary') echo "Summary";
                                         ?></div>
                                        <div class="text-center"></div> 
                                        <div class="report-bill-outer-section">
                                            <div class="table-wrap1">
                                                <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                    <thead>
                                                        <tr>
                                                            <?php 
                                                            $drAmt = "rowspan='2'";
                                                            $crAmt = "rowspan='2'";
                                                            if($postData['MemberBillSummary']['report_type'] == 'Summary'){
                                                                $drAmt = "colspan='4'";
                                                                $crAmt = "colspan='4'";
                                                            }
                                                            ?>
                                                            <th rowspan="2" class="text-center">Date</th>
                                                            <th colspan="2" class="text-center">Reference</th>
                                                            <th colspan="2" class="text-center">Cheque</th>
                                                            <th <?= $drAmt;  ?> class="text-center">Dr. Amount</th>
                                                            <th <?= $drAmt;  ?> class="text-center">Cr. Amount</th>
                                                            <th rowspan="2" class="text-center">Balance</th>
                                                        </tr>
                                                        <tr>
                                                            <th class="text-center">Type.</th>
                                                            <th class="text-center">No.</th>
                                                            <th class="text-center">No.</th>
                                                            <th class="text-center">Date</th>
                                                            <?php if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
                                                            <th class="text-center">Principal</th>
                                                            <th class="text-center">Interest</th>
                                                            <th class="text-center">Tax</th>
                                                            <th class="text-center">Total</th>
                                                            <th class="text-center">Principal</th>
                                                            <th class="text-center">Interest</th>
                                                            <th class="text-center">Tax</th>
                                                            <th class="text-center">Total</th>
                                                            <?php } ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>


<!-------------------------------------------- Start Code -------------------------------------------------------->

<?php
$dueAmuntt= array();
$drAmunttsum= array();
$crAmunttsum= array();
$opBalanceSum= array();

// Counting $accountMemberLedgerDetails is not a test for "did anything render":
// the controller's uasort() takes its array by reference, so PHP auto-creates a
// stub entry for every member it looks at, bills or not. Only blocks that really
// carry a Member are drawn, so count those.
$renderedMemberBlocks = 0;
if (isset($accountMemberLedgerDetails) && count($accountMemberLedgerDetails) > 0) {
    foreach ($accountMemberLedgerDetails as $MemberLedgerData) {
    if (isset($MemberLedgerData['Member'])) {
    $renderedMemberBlocks++;
?>


<!--  1.] Member Details -->

<tr id="">
<td width="8%" colspan="1" style="border:none !important"><span style="display: block;">Unit No : <?php echo isset($MemberLedgerData['Member']['flat_no']) ? $MemberLedgerData['Member']['flat_no'] : ''; ?></span></td>
<td colspan="7" style="border:none !important"><span style="display: block;">Member Name : <?php echo isset($MemberLedgerData['Member']['member_name']) ? $MemberLedgerData['Member']['member_name'] : ''; ?></span></td>
</tr>
<tr id="">
<td colspan="8" class="border-none">Building :  <?php echo isset($MemberLedgerData['Member']['building_name']) ? $MemberLedgerData['Member']['building_name'] : ''; ?></td>
</tr>
<tr id="">
<td colspan="8" class="border-none"><span style="display: block;">Wing :<?php echo isset($MemberLedgerData['Member']['wing_name']) ? $MemberLedgerData['Member']['wing_name'] : ''; ?></span></td>
</tr>


<!--  2.] Member Opening Balance -->

<?php 

            if($postData['MemberBillSummary']['report_type'] == 'sup'){
                $op_principal = isset($memberArr[$MemberLedgerData['Member']['id']]['supplementary_principal']) ? $memberArr[$MemberLedgerData['Member']['id']]['supplementary_principal'] * 1 : 0.00;
                $op_interest = isset($memberArr[$MemberLedgerData['Member']['id']]['supplementary_interest']) ? $memberArr[$MemberLedgerData['Member']['id']]['supplementary_interest'] * 1 : 0.00;
                $op_tax = isset($memberArr[$MemberLedgerData['Member']['id']]['supplementary_tax']) ? $memberArr[$MemberLedgerData['Member']['id']]['supplementary_tax'] * 1 : 0.00;                
            }
            else{
                $op_principal = isset($memberArr[$MemberLedgerData['Member']['id']]['op_principal']) ? $memberArr[$MemberLedgerData['Member']['id']]['op_principal'] * 1 : 0.00;
                $op_interest = isset($memberArr[$MemberLedgerData['Member']['id']]['op_interest']) ? $memberArr[$MemberLedgerData['Member']['id']]['op_interest'] * 1 : 0.00;
                $op_tax = isset($memberArr[$MemberLedgerData['Member']['id']]['op_tax']) ? $memberArr[$MemberLedgerData['Member']['id']]['op_tax'] * 1 : 0.00;                
            }

            
            
            $op_balance = $op_principal + $op_interest + $op_tax;
            $indBalance = $op_balance;
        
            $paymentTotalAmount = 0;
            $monthlyTotalAmount = 0;

    $openingCr = 0;
    $openingDr = 0;

    if($op_balance < 0) {
        $openingDr = abs($op_balance);
        $posNeg = 'Cr';
        $paymentTotalAmount = abs($op_balance);
        } else {
        $openingCr = abs($op_balance);
        $posNeg = 'Dr';
        $monthlyTotalAmount = abs($op_balance);
        }

        if($op_balance==0){
           $posNeg=''; 
        }
?>

<tr id="">
    <td><?php echo $session->read('Auth.formated_year_start_date');?></td>
    <td><span style="display: block;">Opening Balance</span></td>
    <td></td>
    <td></td>
    <td></td>
    <td class="text-right total_debit"><?php echo number_format((float) $openingCr, 2, '.', ''); ?></td>
    <td class="text-right total_credit"><?php echo number_format((float) $openingDr, 2, '.', ''); ?></td>
<?php if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>    
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>    
<?php } ?>
    <td class="text-right total_balance"><?php echo number_format((float) abs($op_balance), 2, '.', '') . ' ' . $posNeg; ?></td>
</tr>




<!--  3.] Member Bill  Details -->

<?php 
$countt=0;
$totalPrincipal = 0;
$totalInterest = 0;
$totalBillTax = 0;

$settlePrincipal = 0;
$settleInterest = 0;
$settleTax = 0;
$totalBillPrincipal = 0;
$totalBillAmt= 0;
 
 foreach ($MemberLedgerData['MemberBillSummary'] as $MemberLedgerDetails) {
        $totalCredited   =0;
        $totalDebited    =0;
        $totalPaid       =0;
        $totalBillAmount =0;
        
        $MemberLedgerDetails['monthly_principal_amount'] = $MemberLedgerDetails['monthly_principal_amount'] - $MemberLedgerDetails['tax_total'];
        
        if($MemberLedgerDetails['type']=='member_bill_summary'){
            // $totalBillAmount = $MemberLedgerDetails['monthly_bill_amount'];
            $totalBillAmount = $MemberLedgerDetails['monthly_principal_amount'] + $MemberLedgerDetails['interest_on_due_amount'];
            $totalPrincipal = $totalPrincipal + $MemberLedgerDetails['monthly_principal_amount'];
            $totalInterest = $totalInterest + $MemberLedgerDetails['interest_on_due_amount'];
            $totalBillTax = $totalBillTax + $MemberLedgerDetails['tax_total'];
            $totalBillTax = $totalBillTax + $MemberLedgerDetails['tax_total']+ $MemberLedgerDetails['gst_on_interest_arrease']+ $MemberLedgerDetails['gst_on_current_interest'];
            $totalDebited = $totalDebited + $MemberLedgerDetails['tax_total'] + $MemberLedgerDetails['gst_on_interest_arrease']+ $MemberLedgerDetails['gst_on_current_interest'];            
            
        }
        if($MemberLedgerDetails['type']=='Debit'){
             $totalDebited = $MemberLedgerDetails['amount_paid'];
        }
        if($MemberLedgerDetails['type']=='Credit'){
             $totalCredited = $MemberLedgerDetails['amount_paid'];
        }
        if($MemberLedgerDetails['type']=='member_payments'){
            $totalPaid = $MemberLedgerDetails['amount_paid'];
        }
        if($countt==0){
                $totalBill  =    $totalBillAmount+ $totalDebited+$op_balance;
                $opBalanceSum[$MemberLedgerData['Member']['id']] = $op_balance;
        }else{
            $totalBill      =    $totalBillAmount+ $totalDebited+$dueAmuntt[$MemberLedgerData['Member']['id']][$countt-1];
        }
        $totalPaid = $totalPaid + $totalCredited;
        $dueAmuntt[$MemberLedgerData['Member']['id']][$countt] = $totalBill - $totalPaid;




//  3.1] Bill Generated
 if (isset($MemberLedgerDetails['bill_generated_date']) && $MemberLedgerDetails['bill_generated_date'] != '' && $MemberLedgerDetails['type']=='member_bill_summary') {




if (!isset($MemberLedgerDetails['monthly_bill_amount'])) {
$MemberLedgerDetails['monthly_bill_amount'] = 0;
}

$monthlyTotalAmount = $monthlyTotalAmount + $MemberLedgerDetails['monthly_bill_amount'];

$indBalance += $MemberLedgerDetails['monthly_bill_amount'];

if ($indBalance < 0) {
$posNeg = 'Cr';
} else {
$posNeg = 'Dr';
}

if($dueAmuntt[$MemberLedgerData['Member']['id']][$countt] ==0){
    $posNeg ='';
}



  ?>








    <tr>
        <?php
        $billType = ($MemberLedgerDetails['bill_type'] == 'sup') ? 'Supplementary':''; ?>
        <td><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : ''; ?></td>
        <td>Bill For <?php echo "$billType " ;echo isset($MemberLedgerDetails['month']) ? $MemberLedgerDetails['monthName'] : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['bill_no']) ? $MemberLedgerDetails['bill_no'] : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['cheque_reference_number']) ? $MemberLedgerDetails['cheque_reference_number'] : '0'; ?></td>
        <td width="8%" class="text-center"><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : '-'; ?></td>
        <?php if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
        <td width="8%" class="text-right"><?php $monthlyPrincipalAmt =  isset($MemberLedgerDetails['monthly_principal_amount']) ? $MemberLedgerDetails['monthly_principal_amount'] : '';echo $monthlyPrincipalAmt; ?></td>
        <td width="8%" class="text-right"><?php $interestOnDueAmt =  isset($MemberLedgerDetails['interest_on_due_amount']) ? $MemberLedgerDetails['interest_on_due_amount'] : ''; echo $totalInterest;?></td>
        <td width="8%" class="text-right"><?php $billTax =  isset($MemberLedgerDetails['tax_total']) ? $MemberLedgerDetails['tax_total'] : ''; echo $totalBillTax;?></td>        
        <td width="8%" class="text-right"><?php echo ($monthlyPrincipalAmt+$interestOnDueAmt+$billTax);
        $totalBillAmt = $totalBillAmt + ($monthlyPrincipalAmt+$interestOnDueAmt+$billTax);
        ?></td>
        <td width="4%" class="text-right">0.00</td>
        <td width="4%" class="text-right">0.00</td>
        <td width="4%" class="text-right">0.00</td>
        <td width="4%" class="text-right">0.00</td>
        <?php } else { ?>
        <td width="8%" class="text-right"><?php $monthlyPrincipalAmt =  isset($MemberLedgerDetails['monthly_bill_amount']) ? $MemberLedgerDetails['monthly_bill_amount'] : '';echo $monthlyPrincipalAmt; ?></td>
        <td width="4%" class="text-right">0.00</td>
        <?php } ?>
        <td width="8%" class="text-right"><?php echo $indBalance;//  number_format((float) abs($dueAmuntt[$MemberLedgerData['Member']['id']][$countt]), 2, '.', '') . ' ' . $posNeg; ?></td>
    </tr>
<?php  $drAmunttsum[$MemberLedgerData['Member']['id']][$countt] = $MemberLedgerDetails['monthly_bill_amount']; }



//  3.2] Member Payment
    if(isset($MemberLedgerDetails['payment_date']) && $MemberLedgerDetails['payment_date'] != '' && $MemberLedgerDetails['type']=='member_payments') { 
    
    $settlePrincipal = $settlePrincipal + (isset($MemberLedgerDetails['paid_principal']) ? $MemberLedgerDetails['paid_principal'] : 0);
    $settleInterest = $settleInterest + (isset($MemberLedgerDetails['paid_interest']) ? $MemberLedgerDetails['paid_interest'] : 0);
    $settleTax = $settleTax + (isset($MemberLedgerDetails['paid_tax']) ? $MemberLedgerDetails['paid_tax'] : 0);

    $creaditAmt = isset($MemberLedgerDetails['amount_paid']) ? $MemberLedgerDetails['amount_paid'] : '0.00';
    $paymentTotalAmount = $paymentTotalAmount + $creaditAmt;
    $indBalance -= $creaditAmt;
    if ($indBalance < 0) {
        $posNeg = 'Cr';
    } else {
        $posNeg = 'D2r';
    }




    ?>

    <tr>
        <td><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : ''; ?></td>
        <td>Receipt For <?php echo "$billType "; echo isset($MemberLedgerDetails['bill_month']) ? $utilObj->getMonthWordFormat($MemberLedgerDetails['bill_month']) : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['receipt_id']) ? $MemberLedgerDetails['receipt_id'] : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['cheque_reference_number']) ? $MemberLedgerDetails['cheque_reference_number'] : '0'; ?></td>
        <td width="8%" class="text-center"><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : '-'; ?></td>
        <?php if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['paid_principal']) ? $MemberLedgerDetails['paid_principal'] : 0; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['paid_interest']) ? $MemberLedgerDetails['paid_interest'] : 0; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['paid_tax']) ? $MemberLedgerDetails['paid_tax'] : 0; ?></td>
        <?php } else {?>
        <td width="8%" class="text-right">0.00</td>
        <?php } ?>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['amount_paid']) ? $MemberLedgerDetails['amount_paid'] : '0.00'; ?></td>
        <?php $finalAmonts =  number_format((float) abs($dueAmuntt[$MemberLedgerData['Member']['id']][$countt]), 2, '.', ''); ?>

        <td width="8%" class="text-right 12344"><?php echo $finalAmonts . ' ' . $posNeg = ($finalAmonts !=0.00)? $posNeg : '' ; ?></td>
    </tr>
<?php  $crAmunttsum[$MemberLedgerData['Member']['id']][$countt] = $MemberLedgerDetails['amount_paid']; }



//  3.3] JV Credited & Debited
if (isset($MemberLedgerDetails['payment_date']) && $MemberLedgerDetails['payment_date'] != '' && $postData['MemberBillSummary']['report_type'] != 'sup' &&  $MemberLedgerDetails['types']=='JV') { 
    // Reset both columns for every JV row, else the previous row's amount
    // leaks into the other column (a debit row showing a credit and vice versa).
    $debit = '0.00';
    $credit = '0.00';
    if($MemberLedgerDetails['type'] == 'Credit') {
        $credit = $MemberLedgerDetails['amount_paid'];
         $crAmunttsum[$MemberLedgerData['Member']['id']][$countt] = $credit;
        // Credits already reach the Cr. total through $crAmunttsum; only the
        // running balance still has to be told about them.
        $indBalance -= $credit;
    }
    if ($MemberLedgerDetails['type'] == 'Debit') {
        $debit = $MemberLedgerDetails['amount_paid'];
        // A JV debit is a debit like a bill is: it belongs in the Dr. total and
        // in the running balance, otherwise the Total row is short by this much.
        $monthlyTotalAmount = $monthlyTotalAmount + $debit;
        $indBalance += $debit;
    }
?>
    <tr>
        <td><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : ''; ?></td>
        <td><?php echo isset($MemberLedgerDetails['particulars']) ? $MemberLedgerDetails['particulars'] : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['voucher_no']) ? $MemberLedgerDetails['voucher_no'] : ''; ?></td>
        <td width="8%" class="text-right"><?php echo isset($MemberLedgerDetails['cheque_reference_number']) ? $MemberLedgerDetails['cheque_reference_number'] : '0'; ?></td>
        <td width="8%" class="text-center"><?php echo isset($MemberLedgerDetails['payment_date']) ? $MemberLedgerDetails['payment_date'] : ''; ?></td>
        <?php 
        if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <?php } ?>
        <td width="8%" class="text-right"><?php echo $debit; ?></td>
        <?php 
        if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <td width="8%" class="text-right">0.00</td>
        <?php } ?>
        <td width="8%" class="text-right"><?php echo $credit; ?></td>
        <td width="8%" class="text-right"><?php echo $indBalance;//  number_format((float) abs($dueAmuntt[$MemberLedgerData['Member']['id']][$countt]), 2, '.', '') . ' ' . $posNeg; ?></td>
    </tr>
<?php

//$DrAmount[$MemberLedgerData['Member']['id']] = $debit+$MemberLedgerDetails['amount_paid'];

}






$countt++;
}


 $max_key = max(array_keys($dueAmuntt[$MemberLedgerData['Member']['id']] ?? [0]));

?>
 <tr style="background-color: #d2d2d2;">
        <?php
        $totalBal = $indBalance;
        // echo $totalBal;

        if ($totalBal < 0) {
            $posNeg = 'Cr';
        } else {
            $posNeg = 'Dr';
        }
        ?>
    <td colspan="5" class="text-right">Total</td>
    <?php if($postData['MemberBillSummary']['report_type'] == 'Summary'){ ?>
    <td class="text-right"><?php echo $totalPrincipal; ?></td>
    <td class="text-right"><?php echo $totalInterest; ?></td>
    <td class="text-right"><?php echo $totalBillTax; ?></td>
    <td class="text-right"><?php echo $totalBillAmt; ?></td>
    <td class="text-right"><?php echo $settlePrincipal; ?></td>
    <td class="text-right"><?php echo $settleInterest; ?></td>
    <td class="text-right"><?php echo $settleTax; ?></td>
    <?php } else { ?>
    <td class="text-right"><?php echo $monthlyTotalAmount; ?></td>
    <?php }?>
    <td class="text-right"><?php echo number_format((float) abs(array_sum($crAmunttsum[$MemberLedgerData['Member']['id']] ?? [])), 2, '.', ''); ?></td>
    <td class="text-right" style="font-weight:bold;"><?php echo $totalBal;// number_format((float) abs($dueAmuntt[$MemberLedgerData['Member']['id']][$max_key]), 2, '.', '') . ' ';
        echo $posNeg; ?></td>

 </tr>

<?php
}
}
}
if ($renderedMemberBlocks === 0) {
    // Nothing matched. Say why instead of rendering an empty table: an "Old
    // Member" run only has rows for members that were actually transferred.
    $emptyColspan = (isset($postData['MemberBillSummary']['report_type']) && $postData['MemberBillSummary']['report_type'] == 'Summary') ? 14 : 8;
    $emptyMessage = (isset($postData['MemberBillSummary']['member_record']) && $postData['MemberBillSummary']['member_record'] == 'Old')
        ? 'No old member records found. A unit appears here only after its member has been transferred (Member Identification / Transfer).'
        : 'No records found for the selected criteria.';
?>
 <tr>
    <td colspan="<?php echo $emptyColspan; ?>" class="text-center"><?php echo $emptyMessage; ?></td>
 </tr>
<?php
}


?>

<!---------------------------------------------- -End Code --------------------------------------------------------->

                                                    </tbody>
                                                </table> 
                                            </div>
                                        </div>  
                                    </div>                             
                                </div>                             
                            </div>                             
