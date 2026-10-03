{{-- Carried over from CakePHP app/View/AccountReports/comparison_2018closing_2019opening.ctp lines 22-173: only the helper calls changed --}}
                            <div id="print_dues_from_member">
                                <div class="print-dues-from-member">
                                    <div class="row">
                                        <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                    </div>
                                    <br>
                                    <div class="report-bill">Bill Summary Update</div>
                                    <div class="report-bill"></div>
                                    <?php
                                        $mismatchCount = 0;
                                        if (isset($societyMemberDetails) && count($societyMemberDetails) > 0) {
                                            foreach ($societyMemberDetails as $md) { if ($md['difference'] != 0) { $mismatchCount++; } }
                                            $correctCount = count($societyMemberDetails) - $mismatchCount;
                                    ?>
                                    <div class="text-center" id="mismatch_summary_counts" style="margin-bottom:8px;">
                                        <span style="color:#3c763d;font-weight:bold;"><?php echo $correctCount; ?> Correct</span>
                                        &nbsp;|&nbsp;
                                        <span style="color:#a94442;font-weight:bold;"><?php echo $mismatchCount; ?> Wrong</span>
                                    </div>
                                    <?php } ?>
                                    <div class="report-bill-outer-section no-border-thead">
                                        <div class="table-wrap1">
                                            <table id="" class="table table-bordered1 padding-td-none padding-th-none">
                                                <thead>
                                                    <tr>
                                                        <th style="width:5%;" class="text-center">
                                                            <label style="cursor:pointer;font-weight:bold;white-space:nowrap;" title="Select all mismatched members">
                                                                <input type="checkbox" id="mismatch_select_all" class="mismatch-green-checkbox"> All
                                                            </label>
                                                        </th>
                                                        <th style="width:5%;" class="text-center">Sr.</th>
                                                        <th style="width:5%;" class="text-center">Unit No.</th>
                                                        <th class="text-center">Member Name</th>

                                                        <?php
                                                            // Was hardcoded "2021" regardless of the financial year in use.
                                                            // This report's own naming convention (comparison_2018closing_2019opening)
                                                            // labels each balance by its FY's ending calendar year, so FY 2026-2027's
                                                            // closing is "2027" and the following FY's opening is "2028".
                                                            $closingYearLabel = $session->read('Auth.end_year');
                                                            $openingYearLabel = $closingYearLabel ? ($closingYearLabel + 1) : '';
                                                        ?>
                                                        <th style="width:18%;" class="text-right">Closing Balance <?php echo $closingYearLabel; ?></th>
                                                        <th style="width:18%;" class="text-right">Opening Balance <?php echo $openingYearLabel; ?></th>
                                                        <th style="width:18%;" class="text-right">Differnce</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                   <tr id="">
                                                        <td colspan="7" class="border-none">Building : <?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></td>
                                                    </tr>
                                                    <tr id="">
                                                        <td colspan="7" class="border-none"><span style="display: block;">Wing : <?php echo isset($societyDetails['Wing']['wing_name']) ? $societyDetails['Wing']['wing_name'] : ''; ?></span></td>
                                                    </tr>
                                                <?php
                                                // Only mismatched members are shown here - this report exists to find
                                                // and fix them, correct (difference = 0) rows just add noise to scroll
                                                // past. The Correct/Wrong summary above still counts everyone.
                                                $shownClosingTotal = 0;
                                                $shownOpeningTotal = 0;
                                                $shownDifferenceTotal = 0;
                                                if(isset($societyMemberDetails) && $mismatchCount > 0){
                                                $idCount = 1;

                                                foreach($societyMemberDetails as $memberId => $memberDetails){
                                                    if ($memberDetails['difference'] == 0) { continue; }
                                                    $shownClosingTotal += $memberDetails['closing2018'];
                                                    $shownOpeningTotal += (float)str_replace(',', '', $memberDetails['opening2019']) * ($memberDetails['openingCrDr'] === ' Cr' ? -1 : 1);
                                                    $shownDifferenceTotal += $memberDetails['difference'];
                                                    ?>
                                                    <tr id="<?php echo $idCount;  ?>">
                                                        <td class="text-center">
                                                            <input type="checkbox" class="mismatch-checkbox mismatch-green-checkbox" value="<?php echo $memberId; ?>">
                                                        </td>
                                                        <td><?php echo $idCount;  ?></td>
                                                        <td><?php echo isset($memberDetails['flat_no']) ? $memberDetails['flat_no'] : '';  ?></td>
                                                        <td><?php echo isset($memberDetails['member_prefix']) ? $memberDetails['member_prefix'].$memberDetails['member_name'] : '';  ?> <?php echo isset($memberDetails['member_name']) ? $memberDetails['member_name'] : '';  ?></td>
                                                        <td class="text-right"><?php echo $memberDetails['closing2018'].$memberDetails['closingCrDr'];  ?></td>
                                                        <td class="text-right"><?php echo $memberDetails['opening2019'].$memberDetails['openingCrDr'];  ?></td>

                                                        <td class="text-right" bgcolor="yellow"><?php echo $memberDetails['difference'].$memberDetails['differenceCrDr'];  ?></td>
                                                    </tr>
                                                <?php $idCount++; }?>


                                                    <tr id="">
                                                        <td class="text-right" colspan="4" class="border-none">Total (wrong records shown above)</td>
                                                        <td class="text-right"><?php echo number_format($shownClosingTotal,2,'.',',');  ?><?php echo ($shownClosingTotal > 0) ? ' Dr' : ' Cr';?></td>
                                                        <td class="text-right"><?php echo number_format($shownOpeningTotal,2,'.',',');  ?><?php echo ($shownOpeningTotal > 0) ? ' Dr' : ' Cr';?></td>

                                                        <td class="text-right"><?php echo number_format($shownDifferenceTotal,2,'.',',');  ?><?php echo ($shownDifferenceTotal > 0) ? ' Dr' : ' Cr';?></td>
                                                    </tr>
                                                 <?php }else{?>
                                                    <tr><td colspan="7"><center>No mismatched records - everything matches the ledger.</center></td></tr>
                                                <?php }?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php if (isset($societyMemberDetails) && $mismatchCount > 0) { ?>
                                        <div style="padding: 10px;">
                                            <div id="mismatch_fix_status"></div>
                                            <button type="button" id="mismatch_select_all_btn" class="btn btn-default btn-sm">All</button>
                                            <button type="button" id="mismatch_update_bill_btn" class="btn btn-success btn-sm" disabled>Update Bill</button>
                                            <span style="margin-left:10px;color:#888;">Click All to select every wrong record, then Update Bill to correct them all at once - or tick individual rows.</span>
                                        </div>
                                        <?php } ?>
                                    </div>

                                    <hr>
                                    <div class="report-bill">Member Balance Reconciliation (Current Financial Year vs. Last Bill)</div>
                                    <div style="padding: 10px;">
                                        <label style="cursor:pointer;font-weight:bold;">
                                            <input type="checkbox" id="recon_select_all" class="mismatch-green-checkbox"> Select All Members
                                        </label>
                                        &nbsp;
                                        <button type="button" id="recon_scan_all_btn" class="btn btn-default btn-sm">Check All &amp; Select Wrong</button>
                                        <button type="button" id="recon_preview_btn" class="btn btn-primary btn-sm">Preview Reconciliation</button>
                                        <button type="button" id="recon_apply_btn" class="btn btn-success btn-sm" disabled>Confirm &amp; Update Selected</button>
                                        <div style="color:#888;font-size:12px;margin-top:4px;">"Check All &amp; Select Wrong" scans every member and auto-ticks only the ones whose balance doesn't match - nothing is saved by this step. "Preview Reconciliation" computes new Tax/Interest/Principal for whatever is ticked, without saving. Review, then Confirm to persist.</div>
                                    </div>
                                    <div id="recon_summary_counts" class="text-center" style="margin-bottom:8px;"></div>
                                    <div id="recon_status"></div>
                                    <div style="max-height:300px;overflow-y:auto;padding:0 10px;">
                                    <?php if (isset($societyMemberDetails)) { foreach ($societyMemberDetails as $memberId => $md) { ?>
                                        <label style="display:inline-block;width:24%;font-weight:normal;">
                                            <input type="checkbox" class="recon-checkbox" value="<?php echo $memberId; ?>"> <?php echo isset($md['flat_no']) ? $md['flat_no'].' - ' : ''; ?><?php echo isset($md['member_name']) ? $md['member_name'] : ''; ?>
                                        </label>
                                    <?php } } ?>
                                    </div>
                                    <div class="table-wrap1">
                                        <table class="table table-bordered1 padding-td-none padding-th-none">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Member</th>
                                                    <th class="text-right">Current Tax</th>
                                                    <th class="text-right">Current Interest</th>
                                                    <th class="text-right">Current Principal</th>
                                                    <th class="text-right">Current Balance</th>
                                                    <th class="text-right">New Tax</th>
                                                    <th class="text-right">New Interest</th>
                                                    <th class="text-right">New Principal</th>
                                                    <th class="text-right">New Balance</th>
                                                    <th class="text-right">Diff</th>
                                                </tr>
                                            </thead>
                                            <tbody id="recon_results_tbody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
