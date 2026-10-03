{{-- Carried over from CakePHP app/View/AccountReports/account_trial_balance_difference_bank.ctp lines 1-221: only the helper calls changed --}}
<?php
$fyStart = $session->read('Auth.year_start_date');
$fyEnd   = $session->read('Auth.year_end_date');
$fyStart = $fyStart ? date('Y-m-d', strtotime($fyStart)) : '';
$fyEnd   = $fyEnd   ? date('Y-m-d', strtotime($fyEnd))   : '';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="panel panel-default card-view">
                
                <div class="panel-heading">
                    <div class="col-sm-6">
                        <div class="pull-left">
                            <h6 class="panel-title txt-dark">Bank/Cash Difference</h6>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="pull-right">
                            <?php /* Same unclosed-anchor bug as the member-difference page: </h6>
                                     for </a> left the link wrapping the filter form. */ ?>
                            <a href="?t=0">Member Bill Difference</a>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="panel-wrapper collapse in">
                    <div class="panel-body">
                        <div class="form-wrap">
                            <div id="show_notify_error"></div>
                              
                            <div class="clearfix"></div>
                            <div id="diff_filter_bar" style="margin:0 0 12px 0;padding:10px 12px;background:#f7f7f9;border:1px solid #e3e3e6;border-radius:5px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;margin:0 0 2px;color:#555;">From Date</label>
                                    <input type="date" id="diff_from" class="form-control input-sm" value="<?php echo $fyStart; ?>" style="height:30px;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:11px;font-weight:600;margin:0 0 2px;color:#555;">To Date</label>
                                    <input type="date" id="diff_to" class="form-control input-sm" value="<?php echo $fyEnd; ?>" style="height:30px;">
                                </div>
                                <div style="flex:1;min-width:180px;">
                                    <label style="display:block;font-size:11px;font-weight:600;margin:0 0 2px;color:#555;">Search (Member / Flat / Ledger / Particular)</label>
                                    <input type="text" id="diff_search" class="form-control input-sm" placeholder="Type to filter..." style="height:30px;">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;margin:0;white-space:nowrap;">
                                        <input type="checkbox" id="diff_only" style="vertical-align:middle;"> Only rows with difference
                                    </label>
                                </div>
                                <div>
                                    <button type="button" id="diff_reset" class="btn btn-sm btn-default" style="height:30px;">Reset</button>
                                </div>
                                <div style="margin-left:auto;font-size:12px;color:#777;white-space:nowrap;">Showing <b id="diff_count">0</b></div>
                            </div>
                            <br>
                            <div id="print_account_cah_book">
                                <div class="print-cash-book">
                                    <div class="row">
                                        <h5 class="text-center"><?php echo isset($societyDetails['Society']['society_name']) ? $societyDetails['Society']['society_name'] : ''; ?></h5>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['registration_no']) ? $societyDetails['Society']['registration_no'] : ''; ?></div>
                                        <div class="report-address-heading"><?php echo isset($societyDetails['Society']['address']) ? $societyDetails['Society']['address'] : ''; ?></div>
                                    </div>
                                    <br>
                                    <div class="row1">
                                        <div class="report-bill"></div>
                                        <div class="report-bill-outer-section">
                                            <div class="table-wrap1">
                                                <table id="" class="table table-bordered padding-td-none padding-th-none" >
                                                    <thead>
                                                        <tr>
                                                            <th colspan="3" class="text-center">Voucher</th>
                                                            <th rowspan="2" class="text-center">Particular</th>
                                                            <th   rowspan="2" class="text-center">DrAmount</th>
                                                            <th   rowspan="2" class="text-center">CrAmount</th>
                                                            <th rowspan="2" class="text-center">Difference</th>
                                                            
                                                        </tr>
                                                        <tr>
                                                            <th class="text-center">Ledger Name</th>
                                                            <th  class="text-center">Date</th>
                                                            <th  class="text-center">No</th>
                                                        </tr>

                                                    </thead>
                                                    <tbody id="diffBody">
                                                    <?php 
                                                    $balancePerRow = 0;
                                                    $totalDeposit = 0;
                                                    $totalWithdraw = 0;
                                                    //echo '<pre>';print_r($bookData);
                                                    ?>
                                                    <?php //TODO
                                                    foreach ($bookData as $lname => $cashBookData){
                                                        
                                                    
                                                        if(isset($cashBookData) && count($cashBookData) > 0){
                                                        $idCount = 1;
                                                        foreach($cashBookData as $paymentDate => $bankBookInfo){
                                                            foreach($bankBookInfo as $flagType => $bookData){

                                                                if(isset($bookData) && count($bookData) > 0){
                                                                foreach($bookData as $finalData){  
                                                                    
                                                                    
                                                         ?>
                                                        <tr class="voucher-row" data-date="<?php echo isset($finalData['payment_date']) ? $finalData['payment_date'] : ''; ?>">
                                                            <?php 
                                                                $totalBal = $totalDeposit - abs($totalWithdraw);
                                                                //echo $totalBal;
                                                                if($totalBal < 0) {
                                                                    $posNeg = 'Cr';
                                                                } else { $posNeg = 'Dr';}                                                               
                                                                $linkParticular  = isset($finalData['particulars']) ? $finalData['particulars'] :'';;
                                                                if (isset($finalData['link'])){
                                                                        $linkParticular = '<a target="_blank" href="'.$finalData['link'].'">'.$linkParticular.'</a>';
                                                                }
                                                            ?>
                                                            <td class="text-center"><?php echo $lname;  ?></td>
                                                            <td><?php echo isset($finalData['payment_date']) ? $utilObj->getFormatDate($finalData['payment_date'],'d/m/Y') : '';  ?></td>
                                                            
                                                            <td class="text-center"><?php echo (isset($finalData['voucher_no']) && $finalData['voucher_no'] !== '' && $finalData['voucher_no'] !== null) ? $finalData['voucher_no'] : $idCount;  ?></td>
                                                            <td><?php echo $linkParticular;  ?></td>
                                                            <td class="text-right"><?php  if (!is_array($finalData['deposit'])){  echo $finalData['deposit']; $dramt = $finalData['deposit'];}else { $dramt =0; ?>
                                                                <table class="table table-bordered padding-td-none padding-th-none">
                                                                    <tr>
                                                                        <th class="text-center">Ledger Name</th>
                                                                        <th class="text-center">Particular</th>
                                                                        <th class="text-center">Amount</th>
                                                                    </tr>
                                                                    <?php foreach ($finalData['deposit'] as $arr){ ?>
                                                                    <tr>
                                                                        <td class="text-center"><?php echo  $arr['ledger_head'];?></td>
                                                                        <td class="text-center"><?php echo  $arr['particular'];?></td>
                                                                        <td class="text-center"><?php $dramt += $arr['amount']; echo  $arr['amount'];?></td>
                                                                    </tr>
                                                                    <?php } ?>
                                                                </table>
                                                            <?php } ?>
                                                            </td>
                                                            <td class="text-right"><?php if (!is_array($finalData['withdrawal'])){ $cramt = $finalData['withdrawal'];  echo $finalData['withdrawal']; }else { $cramt = 0; ?>
                                                                <table class="table table-bordered padding-td-none padding-th-none">
                                                                    <tr>
                                                                        <th class="text-center">Ledger Name</th>
                                                                        <th class="text-center">Particular</th>
                                                                        <th class="text-center">Amount</th>
                                                                    </tr>
                                                                    <?php foreach ($finalData['withdrawal'] as $arr){?>
                                                                    <tr>
                                                                        <td class="text-center"><?php echo  $arr['ledger_head'];?></td>
                                                                        <td class="text-center"><?php echo  $arr['particular'];?></td>
                                                                        <td class="text-center"><?php $cramt += $arr['amount']; echo  $arr['amount'];?></td>
                                                                    </tr>
                                                                    <?php } ?>
                                                                </table>
                                                            <?php } ?>
                                                            </td>
                                                            <td class="text-right diff-cell"><?php $__diff = $cramt - $dramt; echo ($__diff != 0) ? '<span style="color:#d9534f;font-weight:bold;">'.$__diff.'</span>' : $__diff; ?></td>
                                                            
                                                        </tr>
                                                        <?php $idCount++; } } } } ?> 
                                                                
                                                        <?php } ?>
                                                        <?php } ?>
                                                    </tbody>   
                                                </table> 
                                            </div>
                                        </div>  
                                    </div> 
                                </div> 
                            </div> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  function toTs(d){ if(!d) return null; d=(''+d).substr(0,10); var p=d.split('-'); if(p.length!==3) return null; return new Date(+p[0], (+p[1])-1, +p[2]).getTime(); }
  function apply(){
    var fromEl=document.getElementById('diff_from');
    if(!fromEl) return;
    var from=toTs(fromEl.value), to=toTs(document.getElementById('diff_to').value),
        q=(document.getElementById('diff_search').value||'').toLowerCase().trim(),
        onlyDiff=document.getElementById('diff_only').checked, shown=0;
    var rows=document.querySelectorAll('#diffBody > tr.voucher-row');
    rows.forEach(function(tr){
      var show=true, ts=toTs(tr.getAttribute('data-date'));
      if(from!==null && ts!==null && ts<from) show=false;
      if(to!==null && ts!==null && ts>to) show=false;
      if(show && q && tr.textContent.toLowerCase().indexOf(q)===-1) show=false;
      if(show && onlyDiff){
        var dc=tr.querySelector('.diff-cell');
        var v=dc?parseFloat((dc.textContent||'').replace(/[^0-9.\-]/g,'')):0;
        if(!v) show=false;
      }
      tr.style.display=show?'':'none';
      if(show) shown++;
    });
    var c=document.getElementById('diff_count'); if(c) c.textContent=shown;
  }
  function bind(){
    ['diff_from','diff_to','diff_search','diff_only'].forEach(function(id){
      var el=document.getElementById(id); if(el){ el.addEventListener('input',apply); el.addEventListener('change',apply); }
    });
    var rb=document.getElementById('diff_reset');
    if(rb) rb.addEventListener('click',function(){
      document.getElementById('diff_search').value='';
      document.getElementById('diff_only').checked=false;
      document.getElementById('diff_from').value='<?php echo $fyStart; ?>';
      document.getElementById('diff_to').value='<?php echo $fyEnd; ?>';
      apply();
    });
    apply();
  }
  if(document.readyState!=='loading') bind(); else document.addEventListener('DOMContentLoaded', bind);
})();
</script>
