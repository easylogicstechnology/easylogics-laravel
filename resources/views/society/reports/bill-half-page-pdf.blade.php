{{-- Carried over from CakePHP app/View/AccountReports/pdf_bill_half_page.ctp lines 1-230: only the helper calls changed --}}
<?php
$societyLedgerHeadIds = array_keys($societyLedgerHeadTitleList);
?>
<!DOCTYPE html>
<html lang="en">
<head>

	<style type='text/css'>
		@page{
    margin-top:10px;
    margin-left:20px;
    margin-bottom:10px;
    margin-right:20px;
}
body{
    font-family: Helvetica, Arial, sans-serif;
    font-size:10px;
    color:#000000;
}
/* sizes/colors below mirror the compacted .bill-half-page print rules in
   custom_society.css, used by the on-screen "Print" button for this same
   report, so the PDF export looks like the same bill, not a different one */
.bill-half-page{
    page-break-inside: avoid;
    margin-bottom:15px;
}
/* The outer bill frame is plain divs (.bill-outer-border / .bill-section),
   not one big <table>, on purpose: this dompdf build's table row-group
   reflower has a page-break bug where, once a bill isn't the first thing on
   the page, it can re-emit that bill's last row (the signature) a second
   time alone on a stray extra page - even with page-break-inside:avoid set,
   and even when the trigger is something as small as a few extra
   characters of text. Divs don't hit that code path. Confirmed by
   rendering real bills through the actual bundled dompdf (not guessed from
   the CSS) and bisecting every change until the exact trigger was found.
   Small inner <table>s are still used for genuinely tabular data (the
   info/bill-no columns, the charges grid) where that risk doesn't apply. */
.bill-outer-border{
    border:1px solid #515151;
}
.bill-section{
    padding:4px;
    border-bottom:1px solid #515151;
}
.bill-section:last-child{
    border-bottom:none;
}
.society-name{text-align:center;font-size:12px;font-weight:bold;}
.address-heading{text-align:center;font-size:9px;}
.bill-title{text-align:center;font-size:12px;font-weight:bold;}
.info-row-table{width:100%;border-collapse:collapse;}
.info-row-table > tbody > tr > td{border:none;vertical-align:top;}
.info-table{width:100%;border-collapse:collapse;}
.info-table td{border:none;padding:1px 2px;font-size:10px;}
.charges-table{width:100%;border-collapse:collapse;}
.charges-table th, .charges-table td{border:1px solid #515151;padding:3px 5px;font-size:10px;}
.charges-table th{text-align:center;font-weight:bold;background:#d2d2d2;}
.text-right{text-align:right;}
.text-center{text-align:center;}
.font-weight-bold{font-weight:bold;}
.special-field-text{font-style:italic;font-weight:bold;text-align:center;}

	</style>

</head>
<body>
<?php if(isset($monthlyBillsSummaryDetails) && !empty($monthlyBillsSummaryDetails)){
foreach($monthlyBillsSummaryDetails as $memberbillReport) {
    // NOTE: this deliberately does NOT force 2 bills onto one sheet the way
    // the on-screen half-page print does (custom_society.css'
    // .bill-half-page:nth-of-type(2n)). Every way of forcing that break here
    // (page-break-after on the 2nd bill, page-break-before on the 3rd, with
    // or without :not()) reliably corrupted the PDF in this dompdf build:
    // combined with page-break-inside:avoid, it re-emitted a bill's
    // signature row a second time alone on a stray extra page. Verified by
    // rendering real bills through the actual bundled dompdf and reading
    // the output - not guessed from the CSS. One bill per PDF page is the
    // only configuration that renders every bill intact and only once.
?>
<div class="bill-half-page">
    <div class="bill-outer-border">
        <div class="bill-section">
            <div class="society-name"><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></div>
            <div class="address-heading">Registration No. <?php echo isset($memberbillReport['Society']['registration_no']) ? $memberbillReport['Society']['registration_no'] : ''; ?> Dated: <?php echo isset($societyDetails['Society']['registration_date']) ? $utilObj->getFormatDate($societyDetails['Society']['registration_date'],'d/m/y') : ''; ?></div>
            <div class="address-heading"><?php echo isset($memberbillReport['Society']['address']) ? $memberbillReport['Society']['address'] : ''; ?></div>
        </div>
        <div class="bill-section bill-title">BILL</div>
        <div class="bill-section">
            <table class="info-row-table">
                <tr>
                    <td style="width:70%;">
                        <table class="info-table">
                            <tr>
                                <td style="width:30%;">Unit No: <strong><?php echo isset($memberbillReport['Member']['flat_no']) ? $memberbillReport['Member']['flat_no'] :'' ; ?></strong></td>
                                <td style="width:35%;">Unit Area: <strong><?php echo isset($memberbillReport['Member']['area']) ? $memberbillReport['Member']['area'] :'' ; ?></strong> SqFt</td>
                                <td style="width:35%;">Unit type: <?php if($memberbillReport['Member']['unit_type'] == 'C') { echo 'Commercial';} else if($memberbillReport['Member']['unit_type'] == 'R') { echo 'Residential';} else if($memberbillReport['Member']['unit_type'] == 'B') { echo 'Both';} else { echo $memberbillReport['Member']['unit_type']; }?></td>
                            </tr>
                            <tr>
                                <td colspan="3">Name &nbsp;&nbsp;: &nbsp;<?php echo $memberbillReport['Member']['member_prefix'].' '.$memberbillReport['Member']['member_name'];?></td>
                            </tr>
                            <tr>
                                <td colspan="3">Bill For : <?php echo isset($memberbillReport['MemberBillSummary']['monthName']) ? $memberbillReport['MemberBillSummary']['monthName'] :'' ; ?> <?php echo isset($memberbillReport['Society']['financial_year']) ? $memberbillReport['Society']['financial_year'] :'' ; ?></td>
                            </tr>
                            <tr>
                                <td>Wing : <?php $memberWingId = isset($memberbillReport['Member']['wing_id']) ? $memberbillReport['Member']['wing_id'] : ''; echo isset($wingList[$memberWingId]) ? $wingList[$memberWingId] : ''; ?></td>
                                <td colspan="2">Floor No : <?php echo isset($memberbillReport['Member']['floor_no']) ? $memberbillReport['Member']['floor_no'] :'' ; ?></td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:30%;">
                        <table class="info-table">
                            <tr><td>Bill No &nbsp;&nbsp;: &nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_no']) ? $memberbillReport['MemberBillSummary']['bill_no'] :'' ; ?></strong></td></tr>
                            <tr><td>Bill Date : &nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_generated_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_generated_date'],'d/m/Y') :''; ?></strong></td></tr>
                            <tr><td>Due Date : &nbsp;<strong><?php echo isset($memberbillReport['MemberBillSummary']['bill_due_date']) ? $utilObj->getFormatDate($memberbillReport['MemberBillSummary']['bill_due_date'],'d/m/Y') :''; ?></strong></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        <div class="bill-section" style="padding:0;">
                <table class="charges-table">
                    <tr>
                        <th style="width:6%;">Sr.</th>
                        <th colspan="3">Particular of Charges</th>
                        <th style="width:15%;">Amount</th>
                    </tr>
                    <?php
                        $tariffSr = 1;
                        $memberBillTariff = array();
                        foreach($memberbillReport['MemberTariff'] as $key => $indTariff) {
                            if($indTariff['amount'] > 0) {
                                $memberBillTariff[] = $indTariff['ledger_head_id'];
                                $ledgerHead = $indTariff['title'];
                                $ledgerAmount = number_format($indTariff['amount'],2);
                                echo "<tr>
                                        <td style='text-align:center;'>$tariffSr</td>
                                       <td colspan=\"3\">&nbsp; $ledgerHead</td>
                                        <td class='text-right'>$ledgerAmount</td>
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
                                                <td style='text-align:center;'>$tariffSr</td>
                                               <td colspan=\"3\">&nbsp; $ledgerHead</td>
                                                <td class='text-right'>$ledgerAmount</td>
                                                </tr>";
                                        $tariffSr++;
                                 }
                            }
                        }
                    }
                    ?>

                    <tr>
                        <td colspan="2" rowspan="5" style="border-left:1px solid #000;border-bottom:none;"></td>
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
                        <td colspan="2">&nbsp; Rupees <?php echo $utilObj->convertToWords(abs($memberbillReport['MemberBillSummary']['balance_amount']));?> Only</td>
                        <td colspan="2" class="font-weight-bold"><?php  if($memberbillReport['MemberBillSummary']['balance_amount'] < 0) { echo "&nbsp; Excess Amount Received";  } else { echo "&nbsp; Total Due &amp; Payable Amount"; } ?></td>
                        <td class="text-right"><?php  if($memberbillReport['MemberBillSummary']['balance_amount'] < 0) { echo number_format(str_replace('-','',$memberbillReport['MemberBillSummary']['balance_amount']),2).' Cr'; }
                                 else {echo number_format($memberbillReport['MemberBillSummary']['balance_amount'],2).' Dr'; } ?>
                        </td>
                    </tr>
                </table>
        </div>
        <div class="bill-section">
            <strong>Notes: </strong><?php echo isset($societyParameters['SocietyParameter']['bill_note']) ? $societyParameters['SocietyParameter']['bill_note'] : ''; ?>
        </div>
        <div class="bill-section" style="text-align:right;">
            <div class="font-weight-bold"><?php echo isset($memberbillReport['Society']['society_name']) ? $memberbillReport['Society']['society_name'] : ''; ?></div>
            <div>&nbsp;</div>
            <div><?php echo isset($memberbillReport['Society']['authorised_person']) ? $memberbillReport['Society']['authorised_person'] : 'Authorised Signature'; ?></div>
        </div>
        <?php if(!empty($societyParameters['SocietyParameter']['special_field'])) { ?>
        <div class="bill-section special-field-text"><?php echo $societyParameters['SocietyParameter']['special_field']; ?></div>
        <?php } ?>
    </div>
</div>
<?php } } ?>
</body>
</html>
