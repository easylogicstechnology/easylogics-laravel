<div style="overflow-x:auto;">
    <table id="datable_4" class="table" style="font-size:12px; border-collapse:collapse; width:100%;">
        <thead>
            <tr style="background:#e0e0e0;">
                <th style="padding:4px 6px;">UnitNo</th>
                <th style="padding:4px 6px;">Member</th>
                <th style="padding:4px 6px;">Paymet Mode</th>
                <th style="padding:4px 6px;">Amount</th>
                <th style="padding:4px 6px;">Receipt Date</th>
                <th style="padding:4px 6px;">ChequeNo</th>
                <th style="padding:4px 6px;">ChequeDate</th>
                <th style="padding:4px 6px;">Society Bank</th>
                <th style="padding:4px 6px;">MemberBank</th>
                <th style="padding:4px 6px;">Balance Amount</th>
                <th style="padding:4px 6px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @php $s = 1; @endphp
            @foreach($members as $member)
            <tr>
                <td style="padding:3px 4px;">{{ $member->flat_no }}</td>
                <td style="padding:3px 4px; white-space:nowrap;">
                    <input type="hidden" name="data[MemberPayments][{{ $s }}][member_id]" value="{{ $member->id }}">
                    {{ $member->member_name }}
                </td>
                <td style="padding:3px 4px;">
                    <select onchange="setSocietyBankDataInBulkPay('{{ $s }}',this.value)" id="payment_method_{{ $s }}" class="form-control" name="data[MemberPayments][{{ $s }}][reciept_payment_mode]" style="width:120px; font-size:12px; padding:2px 4px;">
                        <option value="">Select payment mode</option>
                        <option value="3" selected>Bank</option>
                        <option value="2">NEFT</option>
                        <option value="1">Cash</option>
                    </select>
                </td>
                <td style="padding:3px 4px;">
                    <input type="text" class="form-control" id="amount_paid_{{ $s }}" name="data[MemberPayments][{{ $s }}][amount_paid]" style="width:80px; font-size:12px; padding:2px 4px; text-align:right;" pattern="[0-9]*">
                </td>
                <td style="padding:3px 4px;">
                    <input type="date" class="form-control" name="data[MemberPayments][{{ $s }}][payment_date]" value="{{ date('Y-m-d') }}" style="width:140px; font-size:12px; padding:2px 4px;">
                </td>
                <td style="padding:3px 4px;">
                    <input type="text" class="form-control" name="data[MemberPayments][{{ $s }}][cheque_reference_number]" style="width:80px; font-size:12px; padding:2px 4px;">
                </td>
                <td style="padding:3px 4px;">
                    <input type="date" class="form-control" name="data[MemberPayments][{{ $s }}][cheque_date]" style="width:140px; font-size:12px; padding:2px 4px;">
                </td>
                <td style="padding:3px 4px;">
                    <select id="society_bank_{{ $s }}" onchange="removeErrorMsg('society_bank_{{ $s }}','{{ $s }}')" name="data[MemberPayments][{{ $s }}][society_bank_id]" style="width:140px; font-size:12px; padding:2px 4px;">
                        <option value="" selected>Select Bank</option>
                        @foreach($bankLists as $bankId => $bankName)
                        <option value="{{ $bankId }}">{{ $bankName }}</option>
                        @endforeach
                    </select>
                    <span style="color:red; font-size:11px;" id="bank_selection_error_{{ $s }}"></span>
                </td>
                <td style="padding:3px 4px;">
                    <select name="data[MemberPayments][{{ $s }}][member_bank_id]" style="width:140px; font-size:12px; padding:2px 4px;">
                        <option value="" selected>Select Bank</option>
                        @foreach($banks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->bank_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="padding:3px 4px; text-align:right;">
                    {{ isset($memberBalanceAmount[$member->id]) ? $memberBalanceAmount[$member->id] : '' }}
                </td>
                <td style="padding:3px 4px; width:200px;">
                    <select class="form-control" id="selectremarks{{ $s }}" onchange="setRemarkText(this.value,'{{ $s }}')" style="font-size:12px; padding:2px 4px;">
                        <option value="-1">Select Remarks</option>
                        <option value="add">Add New</option>
                        @if(!empty($memberPaymentRemarks[$member->id]))
                            @foreach($memberPaymentRemarks[$member->id] as $remarkData)
                            <option value="{{ $remarkData['narration'] }}">{{ $remarkData['narration'] }}</option>
                            @endforeach
                        @endif
                    </select>
                    <input style="display:none; font-size:12px; padding:2px 4px;" value="" name="data[MemberPayments][{{ $s }}][narration]" type="text" id="textremarks{{ $s }}" class="form-control" onkeyup="checkAndResetToList('{{ $s }}')" />
                </td>
            </tr>
            @php $s++; @endphp
            @endforeach
        </tbody>
    </table>
</div>
