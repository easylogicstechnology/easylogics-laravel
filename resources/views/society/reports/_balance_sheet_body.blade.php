{{-- Carried over from CakePHP app/View/AccountReports/account_balance_sheet.ctp lines 95-639: only the helper calls changed --}}
                            <div id="print_account_balancesheet">
                                <div class="print-account-balancesheet">
                                    <div class="row">
                                        <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                    </div>
                                    <div class="row1">
                                        <div class="report-bill">Balance Sheet </div>
                                        <?php
                                            // Shows the period the report covers, inside the print block so it
                                            // carries into Print Friendly and the PDF. Falls back to the selected
                                            // financial year when a run left an end blank.
                                            $bsFrom = !empty($postData['from_date']) ? $postData['from_date'] : $session->read('Auth.year_start_date');
                                            $bsTo   = !empty($postData['to_date'])   ? $postData['to_date']   : $session->read('Auth.year_end_date');
                                            if (!empty($bsFrom) && !empty($bsTo)) {
                                                echo '<div class="report-bill">' . date('d-M-Y', strtotime($bsFrom)) . ' to ' . date('d-M-Y', strtotime($bsTo)) . '</div>';
                                            }
                                        ?>
                                        <div class="text-center"></div>
                                        <div class="report-bill-outer-section">
                                            <div class="table-wrap1"><table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                <tr>
                                                    <td width="50%" valign="top">
                                                        <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                            <thead>
                                                                    <tr>
                                                                        <th>Previous Yr</th>
                                                                        <th rowspan="2">Liabilities</th>
                                                                        <th colspan="2">Current Yr</th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>RS. PS.</td>
                                                                        <td>RS. PS.</td>
                                                                        <td>RS. PS.</td>
                                                                    </tr>
                                                                </thead>                                                                
                                                         <?php
                                                         
                                                            $diffLiability = 0;
                                                            $diffAsset = 0;
                                              
                                                            $countLiability = 0;
                                                            foreach($societyHeadSubCategory['Liability'] as $headCategory=>$headCategoryDetails) {
                                                                $countLiability += (is_countable($headCategoryDetails['heads'] ?? null) ? count($headCategoryDetails['heads']) : 0)+2;
                                                            }
                                                            
                                                            $countAsset = 0;
                                                            foreach($societyHeadSubCategory['Asset'] as $headCategory=>$headCategoryDetails) {
                                                                $countAsset += (is_countable($headCategoryDetails['heads'] ?? null) ? count($headCategoryDetails['heads']) : 0)+2;
                                                            }
                                                            
                                                            if($countLiability > $countAsset) {
                                                                $diffAsset = $countLiability-$countAsset;
                                                            } else if($countLiability < $countAsset) {
                                                                $countLiability = $countAsset-$countLiability;
                                                            }
                                                            
                                                         $totalLiaOpening = 0;
                                                         $totalLiaTxn = 0;
                                                         $totalLiaBalance = 0;


                                                         foreach($societyHeadSubCategory['Liability'] as $headCategory=>$headCategoryDetails) {
                                                             echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                             
                                                             echo "<tr>
                                                                        <td></td>
                                                                        <td style='font-weight:600;'>$headCategory</td>
                                                                        <td></td>
                                                                        <td></td>
                                                                    </tr>";
                                                             
                                                             $ledgerCnt = (is_countable($headCategoryDetails['heads'] ?? null) ? count($headCategoryDetails['heads']) : 0);
                                                             $ledgerSr = 1;
                                                             
                                                             $ledgerHeadTotal = 0;
                                                             
                                                             foreach($headCategoryDetails['heads'] as $ledgerHeadId=>$ledgerHeadDetails) {
                                                                 $openFlag = 'Cr';
                                                                 if($ledgerHeadDetails['openingAmount'] < 0) {
                                                                     $openFlag = 'Dr';
                                                                 }
                                                                 
                                                                 $txnFlag = 'Cr';
                                                                 
                                                                 $txnAmount = $ledgerHeadDetails['openingAmount']+$ledgerHeadDetails['txnAmount'];
                                                                 $ledgerHeadTotal += $txnAmount;
                                                                 
                                                                 if($txnAmount < 0) {
                                                                     $txnFlag = 'Dr';
                                                                 }
                                                                 
                                                                 $lastFlag = 'Cr';
                                                                 if($forYearBalance < 0) {
                                                                     $lastFlag = 'Dr';
                                                                 }
                                                                 
                                                                 /* Share Capital used to be skipped here, so the head printed its
                                                                    own row but its opening balance and movement were left out of the
                                                                    liability totals - the sheet then came up short by exactly that
                                                                    head and could not agree with the asset side. It is a liability
                                                                    like any other and belongs in the total; $totalLiaOpening and
                                                                    $totalLiaTxn are added to in only two places (here and Advance
                                                                    From Members), so counting it here cannot double it. */
                                                                 $totalLiaOpening += $ledgerHeadDetails['openingAmount'];
                                                                 $totalLiaTxn += $txnAmount;
                                                                                                                                  
                                                                 $openingPr = '';
                                                                 if(!empty($ledgerHeadDetails['openingAmount'])) {
                                                                     $openingPr = number_format($ledgerHeadDetails['openingAmount'],2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($txnAmount)) {
                                                                     $txnPr = number_format(abs($txnAmount),2);
                                                                 }
                                                                 
                                                                 
                                                                 echo "<tr>
                                                                        <td>$openingPr</td>
                                                                        <td>"; ?>
                                                                 
                                                                <a href="javascript:void(0);" onclick="
  window.open('<?php echo route('society.reports.ledgerHeadDetails', $ledgerHeadId); ?>', 'window name', 'width=600,height=400,scrollbars=yes');">
                                                                <?php echo $ledgerHeadDetails["title"]; ?></a>    
                                                                 <?php echo "</td>
                                                                        <td>$txnPr</td>";
                                                                 
                                                                 if($ledgerSr == $ledgerCnt) {
                                                                     $headFlag = 'Cr';
                                                                     if($ledgerHeadTotal < 0) {
                                                                        $headFlag = 'Dr';
                                                                     }
                                                                 
                                                                    $headPr = '';
                                                                    if(!empty($ledgerHeadTotal)) {
                                                                        //echo $ledgerHeadTotal.'<br>';
                                                                        $headPr = number_format(abs($ledgerHeadTotal),2);
                                                                    }
                                                                 
                                                                    echo "<td style='font-weight:600;'>$headPr</td>";
                                                                 } else {
                                                                     echo "<td>&nbsp;</td>";
                                                                 }
                                                                 
                                                                 echo "</tr>";
                                                                 
                                                                 $ledgerSr++;
                                                             }
                                                         }
                                                         
                                                         // Advance From members
                                                         echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                         
                                                         if(!empty($accountDuesMemberSummary['advance'])) {
                                                          
                                                             $advanceOpening = abs($accountDuesMemberSummary['advance']['opening_amount']);
                                                             $advanceTxn = abs($accountDuesMemberSummary['advance']['txn_amount']);
                                                             
                                                             //echo $advanceTxn.'<br>';
                                                             
                                                             $totalLiaOpening += $advanceOpening;
                                                             $totalLiaTxn += $advanceTxn;
                                                                                                                              
                                                                 if($advanceOpening >= 0) {
                                                                     $openFlag = 'Cr';
                                                                 } else {
                                                                    $openFlag = 'Dr'; 
                                                                 }
                                                                 
                                                                 
                                                                 if($advanceTxn >= 0) {
                                                                     $txnFlag = 'Cr';
                                                                 } else {
                                                                    $txnFlag = 'Dr'; 
                                                                 }
                                                                 
                                                                 $lastFlag = 'Cr';
                                                                 if($advanceBal < 0) {
                                                                     $lastFlag = 'Dr';
                                                                 }
                                                                 
                                                                 $openingPr = '';
                                                                 if(!empty($advanceOpening)) {
                                                                     $openingPr = number_format(abs($advanceOpening),2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($advanceTxn)) {
                                                                     $txnPr = number_format(abs($advanceTxn),2);
                                                                 }
                                                                 
                                                             echo "<tr>
                                                                        <td>$openingPr</td>
                                                                        <td style='font-weight:600;'>";
                                                             ?>
                                                             <a href="javascript:void(0);" onclick="
  window.open('<?php echo route('society.reports.balanceDuesAdvance'); ?>', 'window name', 'width=600,height=400,scrollbars=yes');">Advance From Members</a>
                                                        
                                                             <?php echo "</td>
                                                                        <td>$txnPr</td>
                                                                        <td style='font-weight:600;'>$txnPr</td>
                                                                    </tr>";
                                                         } else {
                                                             echo "<tr>
                                                                        <td>0</td>
                                                                        <td style='font-weight:600;'>Advance From Members</td>
                                                                        <td>0</td>
                                                                        <td>0</td>
                                                                    </tr>";
                                                         }
                                                         
                                                         for($i=0;$i<$diffLiability;$i++) {
                                                                echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
                                                         }
                                                         
                                                         
                                                                 if($totalLiaOpening >= 0) {
                                                                     $openFlag = 'Cr';
                                                                 } else {
                                                                     $openFlag = 'Dr';
                                                                 }
                                                                 
                                                                 
                                                                 if($totalLiaTxn >= 0) {
                                                                     $txnFlag = 'Cr';
                                                                 } else {
                                                                     $txnFlag = 'Dr';
                                                                 }
                                                                 
                                                                 if($totalLiaBalance >= 0) {
                                                                     $lastFlag = 'Cr';
                                                                 } else {
                                                                     $lastFlag = 'Dr';
                                                                 }
                                                         
                                                         echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                         
                                                                 $openingPr = '';
                                                                 if(!empty($totalLiaOpening)) {
                                                                     $openingPr = number_format(abs($totalLiaOpening),2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($totalLiaTxn)) {
                                                                     $txnPr = number_format(abs($totalLiaTxn),2);
                                                                 }
                                                                 
                                                         echo "<tr>
                                                                        <td style='font-weight:600;'>$openingPr</td>
                                                                        <td style='font-weight:600;'>Total</td>
                                                                        <td style='font-weight:600;'></td>
                                                                        <td style='font-weight:600;'>$txnPr</td>";
                                                         ?>
                                                        </table>
                                                    </td>
                                                    <td width="50%" valign="top">
                                                        <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                            <thead>
                                                                    <tr>
                                                                        <th>Previous Yr</th>
                                                                        <th rowspan="2">Assets</th>
                                                                        <th colspan="2">Current Yr</th>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>RS. PS.</td>
                                                                        <td>RS. PS.</td>
                                                                        <td>RS. PS.</td>
                                                                    </tr>
                                                                </thead>
                                                                
                                                        <?php
                                                         $totalAssetOpening = 0;
                                                         $totalAssetTxn = 0;
                                                         $totalAssetBalance = 0;

                                                         foreach($societyHeadSubCategory['Asset'] as $headCategory=>$headCategoryDetails) {
                                                             echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                             
                                                             echo "<tr>
                                                                        <td></td>
                                                                        <td style='font-weight:600;'>$headCategory</td>
                                                                        <td></td>
                                                                        <td></td>
                                                                    </tr>";
                                                             
                                                             $ledgerCnt = (is_countable($headCategoryDetails['heads'] ?? null) ? count($headCategoryDetails['heads']) : 0);
                                                             $ledgerSr = 1;
                                                             
                                                             $ledgerHeadTotal = 0;
                                                             
                                                             foreach($headCategoryDetails['heads'] as $ledgerHeadId=>$ledgerHeadDetails) {
                                                                 $openFlag = 'Dr';
                                                                 
                                                                 $ledgerHeadDetails['openingAmount'] = empty($ledgerHeadDetails['openingAmount']) ? 0 : trim($ledgerHeadDetails['openingAmount']);
                                                                 $ledgerHeadDetails['txnAmount'] = empty($ledgerHeadDetails['txnAmount']) ? 0 : trim($ledgerHeadDetails['txnAmount']);

                                                                 $forYearBalance = $ledgerHeadDetails['txnAmount']-$ledgerHeadDetails['openingAmount'];
                                                                   // echo "<br>".$ledgerHeadDetails['txnAmount']."-".$ledgerHeadDetails['openingAmount']." ".$forYearBalance;

                                                                 if(!empty($societyHeadSubCategory['Asset']['Cash Balance']['heads'][$ledgerHeadId]['title'])) {
                                                                           $txnAmount = $ledgerHeadDetails['txnAmount'] - $ledgerHeadDetails['openingAmount'];
                                                                           $ledgerHeadTotal += $txnAmount;
                                                                  }  else {
                                                                           $txnAmount = $ledgerHeadDetails['openingAmount']-$ledgerHeadDetails['txnAmount'];
                                                                           $ledgerHeadTotal += $txnAmount;
                                                                  	
                                                                  }     
                                                                 
                                                                 if($txnAmount >= 0) {
                                                                     $txnFlag = 'Dr';
                                                                 } else {
                                                                     $txnFlag = 'Cr';
                                                                 }
                                                                 
                                                                 if($forYearBalance >= 0) {
                                                                     $lastFlag = 'Dr';
                                                                 } else {
                                                                     $lastFlag = 'Cr';
                                                                 }
                                                                 
                                                                  $totalAssetOpening += $ledgerHeadDetails['openingAmount'];
                                                                 $totalAssetTxn += $txnAmount;
                                                                                                                                  
                                                                 $openingPr = '';
                                                                 if(!empty($ledgerHeadDetails['openingAmount'])) {
                                                                     $openingPr = number_format($ledgerHeadDetails['openingAmount'],2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($txnAmount)) {
                                                                     $txnPr = number_format(abs($txnAmount),2);
                                                                 }
                                                                 
                                                                 echo "<tr>
                                                                        <td>$openingPr</td>
                                                                        <td>"; ?>
                                                                 
                                                                <a href="javascript:void(0);" onclick="window.open('<?php echo route('society.reports.ledgerHeadDetails', $ledgerHeadId); ?>', 'window name', 'width=600,height=400,scrollbars=yes');">
                                                                <?php echo $ledgerHeadDetails['title']; ?></a>    
                                                                 <?php echo "</td>
                                                                        <td>$txnPr</td>";
                                                                 
                                                                 if($ledgerSr == $ledgerCnt) {
                                                                     if($ledgerHeadTotal >= 0) {
                                                                        $headFlag = 'Dr';
                                                                     } else {
                                                                         $headFlag = 'Cr';
                                                                     }
                                                                 
                                                                    $headPr = '';
                                                                    if(!empty($ledgerHeadTotal)) {
                                                                        $headPr = number_format(abs($ledgerHeadTotal),2);
                                                                    }
                                                                 
                                                                    echo "<td style='font-weight:600;'>$headPr</td>";
                                                                 } else {
                                                                     echo "<td>&nbsp;</td>";
                                                                 }
                                                                 
                                                                 echo "</tr>";
                                                                 
                                                                 $ledgerSr++;
                                                             }
                                                         }
                                                         
                                                         
                                                         // Dues from members
                                                         echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                    
                                                         if(!empty($accountDuesMemberSummary['dues'])) {
                                                             $duesOpening = $accountDuesMemberSummary['dues']['opening_amount'];
                                                             $duesTxn = $accountDuesMemberSummary['dues']['txn_amount'];
                                                           
                                                             $duesBal = $duesTxn;
                                                             
                                                             
                                                             $totalAssetOpening += $duesOpening;
                                                             $totalAssetTxn += $duesTxn;
                                                                 
                                                                $openFlag = 'Dr';
                                                                 
                                                                 $txnFlag = 'Cr';
                                                                 if($duesTxn >= 0) {
                                                                     $txnFlag = 'Dr';
                                                                 }
                                                                 
                                                                 
                                                                 $openingPr = '';
                                                                 if(!empty($duesOpening)) {
                                                                     $openingPr = number_format(abs($duesOpening),2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($duesTxn)) {
                                                                     $txnPr = number_format(abs($duesTxn),2);
                                                                 }
                                                             
                                                             
                                                             echo "<tr>
                                                                        <td>$openingPr</td>
                                                                        <td style='font-weight:600;'>";
                                                             ?>
                                                             <a href="javascript:void(0);" onclick="
  window.open('<?php echo route('society.reports.balanceDueFromMembers'); ?>', 'window name', 'width=600,height=400,scrollbars=yes');">Dues From Members</a>
                                                        
                                                             <?php echo "</td>
                                                                        <td id='hi'>$txnPr</td>
                                                                        <td style='font-weight:600;'>$txnPr</td>
                                                                    </tr>";
                                                         }  else {
                                                             echo "<tr>
                                                                        <td>0</td>
                                                                        <td style='font-weight:600;'>Dues From Members</td>
                                                                        <td>0</td>
                                                                        <td>0</td>
                                                                    </tr>";
                                                         }
                                                         
                                                         for($i=0;$i<$diffAsset;$i++) {
                                                                echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
                                                         }
                                                         
                                                                $openFlag = 'Dr';
                                                                
                                                                
                                                                 if($totalAssetTxn >= 0) {
                                                                     $txnFlag = 'Dr';
                                                                 } else {
                                                                     $txnFlag = 'Cr';
                                                                 }
                                                                 
                                                                 $lastFlag = 'Dr';
                                                                 if($forYearBalance >= 0) {
                                                                     $lastFlag = 'Cr';
                                                                 }
                                                         
                                                         echo "<tr>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                    </tr>";
                                                         
                                                                 $openingPr = '';
                                                                 if(!empty($totalAssetOpening)) {
                                                                     $openingPr = number_format(abs($totalAssetOpening),2);
                                                                 }
                                                                 
                                                                 $txnPr = '';
                                                                 if(!empty($totalAssetTxn)) {
                                                                     $txnPr = number_format(abs($totalAssetTxn),2);
                                                                 }
                                                                 
                                                         echo "<tr>
                                                                        <td style='font-weight:600;'>$openingPr</td>
                                                                        <td style='font-weight:600;'>Total</td>
                                                                        <td style='font-weight:600;'></td>
                                                                        <td style='font-weight:600;'>$txnPr</td>";
                                                         ?>                                                                
                                                        </table>
                                                    </td>
                                                </tr>
                                                </table>
                                                <?php if (isset($balanceSheetTally)) { ?>
                                                <?php /* Read-only diagnostic row - reports whether the two columns above
                                                         actually add up. Never changes any figure shown above; purely
                                                         informational so a mismatch is visible here instead of only
                                                         being found by manually adding both columns. */ ?>
                                                <div style="margin-top:6px; font-size:12px; color:#777;">
                                                    Balance Sheet Check: Assets <?php echo number_format($balanceSheetTally['totalAsset'], 2); ?>
                                                    | Liabilities <?php echo number_format($balanceSheetTally['totalLiability'], 2); ?>
                                                    | Difference <?php echo number_format($balanceSheetTally['difference'], 2); ?>
                                                    | Tallies: <?php echo $balanceSheetTally['tallies'] ? 'Yes' : 'No'; ?>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                <?php
                                    // Built the signature block as a table rather than a Bootstrap row.
                                    // The col-md-* columns collapsed to full width in the print window,
                                    // stacking the three signatures; a table keeps them side by side and
                                    // evenly spaced on screen and in print alike.
                                    $resellerName = trim((isset($resellerDetails['firstname']) ? $resellerDetails['firstname'] : '') . ' '
                                                       . (isset($resellerDetails['lastname']) ? $resellerDetails['lastname'] : ''));
                                    $resellerRole = isset($resellerDetails['job_role']) ? trim($resellerDetails['job_role']) : '';
                                    // Only add the parentheses when there is a role - an empty reseller was
                                    // printing a bare "()".
                                    if ($resellerName !== '' && $resellerRole !== '') { $resellerName .= ' (' . $resellerRole . ')'; }
                                    elseif ($resellerRole !== '') { $resellerName = '(' . $resellerRole . ')'; }
                                ?>
                                <?php /* report-signature is what the PDF and Excel exporters look for, so the
                                         signed-off block travels with them the way it does with print. */ ?>
                                <table class="report-signature" style="width:100%; margin-top:15px; border:0;">
                                    <?php if ($resellerName !== '') { ?>
                                    <tr><td colspan="3" style="text-align:left; border:0;"><?php echo e($resellerName); ?></td></tr>
                                    <?php } ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center; border:0;"><?php echo isset($societyDetails['Society']['society_name']) ? e($societyDetails['Society']['society_name']) : ''; ?></td>
                                    </tr>
                                    <tr><td colspan="3" style="border:0;">&nbsp;</td></tr>
                                    <tr><td colspan="3" style="border:0;">&nbsp;</td></tr>
                                    <tr>
                                        <?php /* Chairman to the right of its third, Treasurer to the left of its
                                                 own, Secretary centred between them - so the three sit as a group
                                                 with an equal gap either side of Secretary instead of being
                                                 stretched to the page edges. */ ?>
                                        <?php /* The gap either side of Secretary is half the middle column, so
                                                 the spacing is tuned by that width alone - keep the outer two
                                                 equal and the group stays centred. */ ?>
                                        <td style="text-align:right; border:0; width:42%;">Chairman</td>
                                        <td style="text-align:center; border:0; width:16%;">Secretary</td>
                                        <td style="text-align:left; border:0; width:42%;">Treasurer</td>
                                    </tr>
                                </table>
                                </div> 
                            </div> 
