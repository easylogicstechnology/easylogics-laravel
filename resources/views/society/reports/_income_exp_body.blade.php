{{-- Carried over from CakePHP app/View/AccountReports/account_income_exp_statement_details.ctp lines 62-279: only the helper calls changed --}}
                            <div id="print_income_exp_statement">
                            <div class="print-income-exp-statement">
                                <div class="row">
                                    <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                    <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                    <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                </div>
                                <br>
                                <div class="row1">
                                    <div class="report-bill">Income & Expenditure Statement</div>
                                    <?php
                                    /*
                                     * This used to read $postData['TrialBalance']['from_date'] etc,
                                     * a key this action never sets (that nested shape belongs to the
                                     * Trial Balance report) - so it always fell through to the last
                                     * branch, which itself printed a Financial Year label hardcoded
                                     * to '2026-2027' regardless of the period actually queried below.
                                     * The figures on the report were still computed for the right
                                     * dates; only this heading lied about which period they covered.
                                     * Show the real from/to (falling back to the selected financial
                                     * year, same as the Balance Sheet's header) instead.
                                     */
                                    $ieFrom = !empty($postData['from_date']) ? $postData['from_date'] : $session->read('Auth.year_start_date');
                                    $ieTo   = !empty($postData['to_date'])   ? $postData['to_date']   : $session->read('Auth.year_end_date');
                                    ?>
                                    <?php if (!empty($ieFrom) && !empty($ieTo)) { ?>
                                <div class="report-bill">For the period <?php echo date('d/m/Y', strtotime($ieFrom)); ?> to <?php echo date('d/m/Y', strtotime($ieTo)); ?></div>
                                <?php } ?>
                                    <div class="text-center"></div>
                                    <div class="report-bill-outer-section">
                                        <div class="table-wrap1">
                                            <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                <tr>
                                                    <td style="vertical-align:baseline !important;" >
                                                        <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                            <thead>
                                                                <?php 
                                                            $overIncome = 0;
                                                            $overExpense = 0;
                                                            $diffExpense = 0;
                                                            $diffIncome = 0;
                                                        
                                                            $countExpenses = $expIndex + count($expenseHeadsWithSubCat);
                                                            $countIncome = $incomeIdex + count($incomHeadsWithSubCat);
                                                            
                                                            $totalOpeningExpenses;
                                                            $totalOpeningIncome;
                                                            
                                                            $diffAmt = $totalOpeningIncome - $totalOpeningExpenses;
                                                            if($diffAmt > 0){
                                                                $balanceIncomeOverExp = $diffAmt;    
                                                                $finalOpeningIncom = $diffAmt;  
                                                                $balExpOverIncom = 0;
                                                                $finalOpeningIncom = $totalOpeningIncome - $diffAmt;                                                                
                                                            }
                                                            else{
                                                            $balanceIncomeOverExp = 0;
                                                            $balExpOverIncom = abs($diffAmt);
                                                            $finalOpeningIncom = $balExpOverIncom + $totalOpeningIncome;                                                                
                                                            }
                                                            
                                                            ?>

                                                                    <tr>
                                                                        <th>Previous Yr.(Rs.)</th>
                                                                        <th>Expenditure</th>
                                                                        <th>Current Yr.(Rs.)</th>
                                                                        <th>Total</th>
                                                                    </tr>
                                                                </thead>
                                                            <?php 

                                                            if($countIncome > $countExpenses) {
                                                                $diffExpense = $countIncome-$countExpenses;
                                                            } else if($countIncome < $countExpenses) {
                                                                $diffIncome = $countExpenses-$countIncome;
                                                            }
                                                            // echo $diffExpense.'--'.$diffIncome;
                                                            $totalAmountExpenditure=0;
                                                            $i = 0;
                                                            foreach($expenseHeadsWithSubCat as $expenseSubCatHeads){ ?>
                                                            <tr id="">
                                                                    <td></td>
                                                                    <td><strong><h6><?php echo $expenseSubCatHeads[0]['sub_category']; ?></h6></strong></td>
                                                                    <td id="12"></td>
                                                                    <td></td>
                                                            </tr>                                                                                                                
                                                                
                                                            <?php 
                                                            $expCatWisetrnxAmt = 0;
                                                            $expEleCount = count($expenseSubCatHeads);
                                                            foreach($expenseSubCatHeads as $expHeadId => $expHeadData) { 
                                                                $totalAmountExpenditure = $totalAmountExpenditure+$expHeadData['txnAmount'];
                                                                $expCatWisetrnxAmt = $expCatWisetrnxAmt + $expHeadData['txnAmount'];
                                                                ?>
                                                                <tr id="">
                                                                        <td class='number-right'><?php echo number_format($expHeadData['openingAmount'],2); ?></td>
                                                                        <td><b></b><?php echo $expHeadData['title']; ?></b></td>
                                                                        <td class='number-right' id="12"><?php echo number_format($expHeadData['txnAmount'],2); ?></td>
                                                                        <?php  echo (($expEleCount-1) == $expHeadId) ?  "<td class='number-right'>". number_format($expCatWisetrnxAmt,2)."</td>" :  "<td></td>";  ?>
                                                                </tr>
                                                            <?php } 
                                                            }
                                                            
                                                            
                                                            for($i=0;$i<$diffExpense;$i++) { 
                                                                echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
                                                            }
                                                            ?>
                                                            <tr id="">
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                            </tr>   
                                                            <tr id="">
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td class='number-right' id="totalamountexpenditure"><?php echo number_format(abs($totalAmountExpenditure),2);?></td>
                                                            </tr>   
                                                            <tr id="">
                                                                        <td class='number-right'><?php echo number_format($balanceIncomeOverExp,2); ?></td>
                                                                        <td><strong>Excess of Income Over Expenditure</strong></td>
                                                                        <td></td>
                                                                        <td class='number-right' id="over_expenditure"></td>
                                                            </tr>
                                                            <tr id="">
                                                                        <td class='number-right'><?php echo number_format($totalOpeningExpenses,2);?></td>
                                                                        <td><strong>Total</strong></td>
                                                                        <td></td>
                                                                        <td class='number-right' id="subtotal"><?php echo number_format($totalExpenses,2);//echo number_format(($totalExpenses+$overIncome),2); ?></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td>
                                                        <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                            <thead>
                                                                    <tr>
                                                                        <th>Previous Yr.(Rs.)</th>
                                                                        <th>Income</th>
                                                                        <th>Current Yr.(Rs.)</th>
                                                                        <th>Total</th>
                                                                    </tr>
                                                                </thead>
                                                            <?php 
                                                            $totalAmountIncome=0;
                                                            $j = 0;
                                                            foreach($incomHeadsWithSubCat as $catIndex => $incomeHeadsArr){ 
                                                            ?>
                                                                <tr id="">
                                                                        <td></td>
                                                                        <td><strong><h6><?php echo $incomeHeadsArr[0]['sub_category']; ?></h6></strong></td>
                                                                        <td id="4545"></td>
                                                                        <td></td>
                                                                </tr>                                                                
                                                                <?php
                                                                $catWisetrnxAmt = 0;
                                                                $eleCount = count($incomeHeadsArr);
                                                                foreach($incomeHeadsArr as $headId => $headData) { $j++;
                                                                    $totalAmountIncome = $totalAmountIncome + $headData['txnAmount'];
                                                                    $ledgerId = $headData['sub_category_id']; 
                                                                    $catWisetrnxAmt = $catWisetrnxAmt +  $headData['txnAmount'];
                                                                    ?>
                                                                    
                                                                    <tr id="">
                                                                            <td class='number-right'><?php echo number_format($headData['openingAmount'],2); ?></td>
                                                                            <td><?php echo $headData['title']; ?></td>
                                                                            <td class='number-right' id="4545"><?php echo number_format($headData['txnAmount'],2); ?></td>
                                                                            <?php echo (($eleCount-1) == $headId) ?  "<td class='number-right'>". number_format($catWisetrnxAmt,2) ."</td>" :  "<td></td>";  ?>
                                                                    </tr>
                                                                <?php } ?>
                                                            <?php } 

                                                            for($i=0;$i<$diffIncome;$i++) {
                                                                echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
                                                            }
                                                            ?>
                                                            <tr id="">
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                            </tr>    
                                                            <tr id="">
                                                                        <td class='number-right'><?php echo number_format($totalOpeningIncome,2);?></td>
                                                                        <td>&nbsp;</td>
                                                                        <td>&nbsp;</td>
                                                            </tr>                                                               
                                                            <tr id="">
                                                                        <td class='number-right'><?php echo number_format($balExpOverIncom,2); ?></td>
                                                                        <td><strong>Excess of Expenditure Over Income</strong></td>
                                                                        <td></td>
                                                                        <td class='number-right'><b><?php if (($totalExpenses-$totalIncome) > 0) { $overExpense = abs($totalExpenses-$totalIncome); echo number_format(abs($totalExpenses-$totalIncome),2); } else { echo "0.00"; } ?></b></td>
                                                            </tr>
                                                            <tr id="">
                                                                        <td class='number-right'><?php  echo number_format($finalOpeningIncom,2);?></td>
                                                                        <td><strong>Total</strong></td>
                                                                        <td></td>                                                                        
                                                                        <td class='number-right' id="totalamountincome"><?php echo number_format($totalAmountIncome,2); //echo number_format(($totalIncome+$overExpense),2); ?></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>    
                                           
                                        </div>
                                    </div>                                
                                </div> <br>
                                <div class="row">
                                    <div class="col-md-6 col-xs-6 text-center"></div>
                                    <div class="col-md-6 col-xs-3 text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></div>
                                    <div class="clearfix"></div><br><br>
                                    <div class="col-md-6 col-xs-3 text-center"></div>
                                    <div class="col-md-2 col-xs-3 text-center">Chairman</div>
                                    <div class="col-md-2 col-xs-3 text-center">Secretary</div>
                                    <div class="col-md-2 col-xs-3 text-center">Treasurer</div>
                                </div>
                            </div>
                            </div>
