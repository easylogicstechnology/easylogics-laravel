{{-- Carried over from CakePHP app/View/AccountReports/bill_opening_balance.ctp lines 16-76: only the helper calls changed --}}
                            <div id="print_member_list">
                                <div id="print_member-list"> 
                                    <div class="bill-outer-border">
                                        <div class="row">
                                            <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                            <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                            <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                        </div>
                                        <br>
                                        <div class="row1">
                                            <div class="bill">Members' Closing Balance</div>
                                            <div class="row1">
                                                <div style="padding: 0px;">
                                                    <div class="table-wrap">
                                                        <div class="table-responsive1">
                                                            <table id="datable_1" class="table padding-td-none padding-th-none table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="2%" class="text-center">Sr.</th>
                                                                        <th width="6%" class="text-center">Unit No.</th>
                                                                        <th class="text-center">Member Name</th>
                                                                        <th class="text-center">Principal Balance</th>
                                                                        <th class="text-center">Interest Balance</th>
                                                                        <th class="text-center">Tax Balance</th>
                                                                        <th class="text-center">Total Balance</th>
                                                                    </tr>
                                                                </thead>
                                                                <?php
                                                                $cntMembers = count($societyMemberName);
                                                                for ($m=0;$m<$cntMembers;$m++){
                                                                    $total = 0;
                                                                    $total += $societyMemberName[$m]['Member']['principal_balance'];
                                                                    $total += $societyMemberName[$m]['Member']['interest_balance'];
                                                                    $total += $societyMemberName[$m]['Member']['tax_balance'];
                                                                    if($total >= 0){
                                                                        $tTotalDr += $total;
                                                                    }else{
                                                                        $tTotalCr += $total;
                                                                    }
                                                                ?>
                                                                <tr id="">
                                                                    <td><?php echo $m+1;?></td>
                                                                    <td><?php echo $societyMemberName[$m]['Member']['flat_no'];?></td>
                                                                    <td><?php echo implode(' ',array($societyMemberName[$m]['Member']['member_prefix'],$societyMemberName[$m]['Member']['member_name']));?></td>
                                                                    <td class="text-right"><?php echo ($societyMemberName[$m]['Member']['principal_balance'] >= 0) ? $societyMemberName[$m]['Member']['principal_balance'].' Dr' : abs($societyMemberName[$m]['Member']['principal_balance']).' Cr';?></td>
                                                                    <td class="text-right"><?php echo ($societyMemberName[$m]['Member']['interest_balance'] >= 0) ? $societyMemberName[$m]['Member']['interest_balance'].' Dr' : abs($societyMemberName[$m]['Member']['interest_balance']).' Cr';?></td>
                                                                    <td class="text-right"><?php echo ($societyMemberName[$m]['Member']['tax_balance'] >= 0) ? $societyMemberName[$m]['Member']['tax_balance'].' Dr' : abs($societyMemberName[$m]['Member']['tax_balance']).' Cr';?></td>
                                                                    <td class="text-right"><?php echo ($total >= 0) ? $total.' Dr' : abs($total).' Cr';?></td>
                                                                </tr>
                                                                <?php } ?>
                                                                <tr><td colspan="6" class="text-right"><b>Total</b></td><td><?php echo $tTotalDr.' Dr  and '.abs($tTotalCr).' Cr';?></td></tr>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <br>
                                        </div>
                                    </div>
                                </div>
                            </div>
