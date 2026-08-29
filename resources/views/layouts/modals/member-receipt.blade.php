@php
    $__sid = Auth::id();
    $__receiptMembers = \App\Models\Member::where('society_id', $__sid)->where('status',1)->orderBy('flat_no')->get();
    $__banks = \App\Models\SocietyBank::where('society_id', $__sid)->with('ledgerHead')->get();
    $__memberBanks = \App\Models\Bank::orderBy('bank_name')->get();
@endphp
<div id="memberReceiptModal" class="modal-overlay" style="display:none;">
<div class="modal-box" style="max-width:1200px;">
    <div class="modal-header-bar">Member Receipt <span class="modal-close" onclick="closeModal('memberReceiptModal')">&times;</span></div>
    <div class="modal-body-content">
        <div style="margin-bottom:12px;">
            <button class="btn btn-success" onclick="saveMemberReceipt()">Save Member Payment</button>
        </div>
        <div style="overflow-x:auto;">
            <table style="font-size:13px;">
                <thead>
                    <tr style="background:#ccc;">
                        <th>UNITNO</th><th>MEMBER</th><th>PAYMET MODE</th><th>AMOUNT</th>
                        <th>RECEIPT DATE</th><th>CHEQUENO</th><th>CHEQUEDATE</th>
                        <th>SOCIETY BANK</th><th>MEMBERBANK</th><th>BALANCE AMOUNT</th><th>REMARK</th>
                    </tr>
                </thead>
                <tbody id="receipt_table">
                    @foreach($__receiptMembers as $m)
                    <tr data-member-id="{{ $m->id }}">
                        <td>{{ $m->flat_no }}</td>
                        <td style="white-space:nowrap;">{{ $m->member_prefix }} {{ $m->member_name }}</td>
                        <td>
                            <select class="form-control" style="width:80px; font-size:12px;" name="payment_mode_{{ $m->id }}">
                                <option value="Bank">Bank</option><option value="Cash">Cash</option>
                            </select>
                        </td>
                        <td><input type="text" class="form-control" style="width:70px; font-size:12px; text-align:right;" name="amount_{{ $m->id }}"></td>
                        <td><input type="date" class="form-control" style="width:130px; font-size:12px;" name="receipt_date_{{ $m->id }}" value="{{ date('Y-m-d') }}"></td>
                        <td><input type="text" class="form-control" style="width:70px; font-size:12px;" name="cheque_no_{{ $m->id }}"></td>
                        <td><input type="date" class="form-control" style="width:130px; font-size:12px;" name="cheque_date_{{ $m->id }}"></td>
                        <td>
                            <select class="form-control" style="width:120px; font-size:12px;" name="society_bank_{{ $m->id }}">
                                <option value="">Select Bank</option>
                                @foreach($__banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->ledgerHead->title ?? $bank->branch }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control" style="width:120px; font-size:12px;" name="member_bank_{{ $m->id }}">
                                <option value="">Select Bank</option>
                                @foreach($__memberBanks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td style="text-align:right;" class="balance-amt" id="balance_{{ $m->id }}">0.00</td>
                        <td><input type="text" class="form-control" style="width:60px; font-size:12px;" name="remark_{{ $m->id }}"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
