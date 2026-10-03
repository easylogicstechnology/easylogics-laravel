{{-- Carried over from CakePHP app/View/AccountReports/member_chart.ctp lines 96-181: only the helper calls changed --}}
                            <div id="print_member_chart">
                                <div id="export_to_excel_member_chart">
                                    <br>
                                    <div class="row">
                                        <div class="report-bill-outer-section">
                                            <div class="table-wrap table-responsive">
                                                <table id="member_chart_table" class="table padding-td-none padding-th-none table-bordered">
                                                    <thead>
                                                        <tr style="border: solid; border-color: whitesmoke;"><th colspan="<?php echo count(isset($uniqueLedgerHeads) ? $uniqueLedgerHeads : array()) + 7; ?>" style="background: #f5f5f5; text-align:center;"><h5><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5></th></tr>
                                                        <tr style="border: solid; border-color: whitesmoke;"><th colspan="<?php echo count(isset($uniqueLedgerHeads) ? $uniqueLedgerHeads : array()) + 7; ?>" style="background: #f5f5f5; text-align:center;"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></th></tr>
                                                        <tr style="border: solid; border-color: whitesmoke;"><th colspan="<?php echo count(isset($uniqueLedgerHeads) ? $uniqueLedgerHeads : array()) + 7; ?>" style="background: #f5f5f5; text-align:center;"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></th></tr>
                                                        <tr style="border: solid; border-color: whitesmoke;"><th colspan="<?php echo count(isset($uniqueLedgerHeads) ? $uniqueLedgerHeads : array()) + 7; ?>" style="background: #f5f5f5; text-align:center;"><strong>MEMBER CHART</strong></th></tr>
                                                        <tr style="background:#d9edf7;">
                                                            <th class="text-center" width="3%">Sr.</th>
                                                            <th class="text-center" width="6%">Unit No.</th>
                                                            <th class="text-center">Op. Balance</th>
                                                            <?php if(isset($uniqueLedgerHeads)) foreach($uniqueLedgerHeads as $lhId => $lhTitle) { ?>
                                                                <th class="text-center"><?php echo $lhTitle; ?></th>
                                                            <?php } ?>
                                                            <th class="text-center">Interest</th>
                                                            <th class="text-center">GST Amt</th>
                                                            <th class="text-center">Total</th>
                                                            <th class="text-center">Received</th>
                                                            <th class="text-center">Cl. Balance</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    $sr = 1;
                                                    $grandOpBal = $grandInterest = $grandGst = $grandTotal = $grandReceived = $grandClosing = 0;
                                                    $grandTariffTotals = array();

                                                    if(isset($memberChartData) && !empty($memberChartData)) {
                                                        foreach($memberChartData as $memberId => $mData) {
                                                            $grandOpBal += $mData['opening_balance'];
                                                            $grandInterest += $mData['interest'];
                                                            $grandGst += $mData['gst'];
                                                            $grandTotal += $mData['total_billed'];
                                                            $grandReceived += $mData['received'];
                                                            $grandClosing += $mData['closing_balance'];
                                                    ?>
                                                        <tr>
                                                            <td class="text-center"><?php echo $sr; ?></td>
                                                            <td class="text-center"><?php echo $mData['flat_no']; ?></td>
                                                            <td class="text-right"><?php echo number_format($mData['opening_balance'], 2); ?></td>
                                                            <?php if(isset($uniqueLedgerHeads)) foreach($uniqueLedgerHeads as $lhId => $lhTitle) {
                                                                $amt = isset($mData['tariffs'][$lhId]) ? $mData['tariffs'][$lhId] : 0;
                                                                if (!isset($grandTariffTotals[$lhId])) $grandTariffTotals[$lhId] = 0;
                                                                $grandTariffTotals[$lhId] += $amt;
                                                            ?>
                                                                <td class="text-right"><?php echo number_format($amt, 2); ?></td>
                                                            <?php } ?>
                                                            <td class="text-right"><?php echo number_format($mData['interest'], 2); ?></td>
                                                            <td class="text-right"><?php echo number_format($mData['gst'], 2); ?></td>
                                                            <td class="text-right"><strong><?php echo number_format($mData['total_billed'], 2); ?></strong></td>
                                                            <td class="text-right"><?php echo number_format($mData['received'], 2); ?></td>
                                                            <td class="text-right"><strong><?php echo number_format($mData['closing_balance'], 2); ?></strong></td>
                                                        </tr>
                                                    <?php
                                                            $sr++;
                                                        }
                                                    }
                                                    ?>
                                                    </tbody>
                                                    <?php if(isset($memberChartData) && !empty($memberChartData)) { ?>
                                                    <tfoot>
                                                        <tr style="font-weight:bold; background:#f5f5f5;">
                                                            <td colspan="2" class="text-right"><strong>Grand Total</strong></td>
                                                            <td class="text-right"><?php echo number_format($grandOpBal, 2); ?></td>
                                                            <?php if(isset($uniqueLedgerHeads)) foreach($uniqueLedgerHeads as $lhId => $lhTitle) { ?>
                                                                <td class="text-right"><?php echo number_format(isset($grandTariffTotals[$lhId]) ? $grandTariffTotals[$lhId] : 0, 2); ?></td>
                                                            <?php } ?>
                                                            <td class="text-right"><?php echo number_format($grandInterest, 2); ?></td>
                                                            <td class="text-right"><?php echo number_format($grandGst, 2); ?></td>
                                                            <td class="text-right"><?php echo number_format($grandTotal, 2); ?></td>
                                                            <td class="text-right"><?php echo number_format($grandReceived, 2); ?></td>
                                                            <td class="text-right"><?php echo number_format($grandClosing, 2); ?></td>
                                                        </tr>
                                                    </tfoot>
                                                    <?php } ?>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
