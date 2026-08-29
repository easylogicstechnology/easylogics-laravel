@php
    $__sid = Auth::id();
    $__bankHeads = \App\Models\SocietyLedgerHead::where('society_id', $__sid)->where('status',1)
        ->whereIn('account_category_id', [1,2])->orderBy('title')->get();
    $__expenseHeads = \App\Models\SocietyLedgerHead::where('society_id', $__sid)->where('status',1)->orderBy('title')->get();
@endphp
<div id="paymentEntryModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:1100px;">
    <div class="modal-header-bar">Payment Entry <span class="modal-close" onclick="closeModal('paymentEntryModal')">&times;</span></div>
    <div class="modal-body-content">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:12px;">
            <div class="form-group" style="margin:0;">
                <label>Date*</label>
                <input type="date" class="form-control" id="pe_date" value="{{ date('Y-m-d') }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label>By*</label>
                <select class="form-control" id="pe_by_ledger">
                    <option value="">Select Vendor</option>
                    @foreach($__bankHeads as $bh)
                    <option value="{{ $bh->id }}">{{ $bh->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Type*</label>
                <select class="form-control" id="pe_type" onchange="toggleChequeFields()">
                    <option value="">Select pay</option>
                    <option value="Bank">Bank</option>
                    <option value="Cash">Cash</option>
                </select>
            </div>
        </div>
        <div style="margin-bottom:12px;">
            <label><input type="checkbox" id="pe_single_voucher"> Below Payment(s) To Be Created A Single Voucher</label>
        </div>
        <div style="display:flex; gap:8px; margin-bottom:8px;">
            <button class="btn btn-success" onclick="saveSocietyPayment()">Save Society Payment</button>
            <button class="btn" style="background:#e74c3c; color:#fff;" onclick="closeModal('paymentEntryModal')">Cancel</button>
        </div>
        <div style="overflow-x:auto;">
            <table style="font-size:13px;">
                <thead>
                    <tr style="background:#ccc;">
                        <th>SR.</th><th>PAID TO</th><th>AMOUNT</th><th>PAYMENT DATE</th>
                        <th class="pe-cheque-col">CHEQUENO</th><th class="pe-cheque-col">CHEQUE DATE</th>
                        <th>PARTICULARS</th><th>REMARK</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 9; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td>
                            <select class="form-control" style="width:180px; font-size:12px;" name="pe_ledger_{{ $i }}">
                                <option value="">Select Ledger</option>
                                @foreach($__expenseHeads as $eh)
                                <option value="{{ $eh->id }}">{{ $eh->title }}</option>
                                @endforeach
                            </select>
                            <label style="font-size:11px;"><input type="checkbox" name="pe_tax_{{ $i }}"> Is tax applicable?</label>
                        </td>
                        <td><input type="text" class="form-control" style="width:70px; font-size:12px; text-align:right;" name="pe_amount_{{ $i }}" value="0"></td>
                        <td><input type="date" class="form-control" style="width:130px; font-size:12px;" name="pe_pay_date_{{ $i }}"></td>
                        <td class="pe-cheque-col"><input type="text" class="form-control" style="width:70px; font-size:12px;" name="pe_cheque_no_{{ $i }}" value="0"></td>
                        <td class="pe-cheque-col"><input type="date" class="form-control" style="width:130px; font-size:12px;" name="pe_cheque_date_{{ $i }}"></td>
                        <td><input type="text" class="form-control" style="width:120px; font-size:12px;" name="pe_particulars_{{ $i }}"></td>
                        <td><input type="text" class="form-control" style="width:80px; font-size:12px;" name="pe_remark_{{ $i }}"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div id="pe_message" style="margin-top:8px;"></div>
    </div>
</div>
</div>
