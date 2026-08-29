@extends('layouts.app')
@section('title', $editItem ? 'Edit Ledger Head' : 'Ledger Heads')
@section('content')
<div class="page-header">
    <h2>Ledger Heads</h2>
</div>

<div class="card">
    <form method="POST" action="{{ route('society.addLedgerHead', $editItem->id ?? '') }}" id="ledgerHeadsForm">
        @csrf
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:16px;">
            <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                <label>Society Head Sub Group</label>
                <select name="society_head_sub_category_id" class="form-control" id="subGroupSelect" required>
                    <option value="">select Society Head Sub Group</option>
                    @foreach($headSubCategories as $catId => $catName)
                    <option value="{{ $catId }}" {{ ($editItem && $editItem->society_head_sub_category_id == $catId) ? 'selected' : '' }}>{{ $catName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:160px;">
                <label>Account Category</label>
                <select name="account_category_id" class="form-control" id="accCatSelect">
                    @if($editItem && $editItem->accountCategory)
                    <option value="{{ $editItem->account_category_id }}">{{ $editItem->accountCategory->title ?? '' }}</option>
                    @endif
                </select>
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:200px;">
                <label>Account Heads</label>
                <select name="account_head_id" class="form-control" id="accHeadSelect">
                    @if($editItem && $editItem->accountHead)
                    <option value="{{ $editItem->account_head_id }}">{{ $editItem->accountHead->title ?? '' }}</option>
                    @endif
                </select>
            </div>
        </div>

        @if($editItem)
        {{-- EDIT MODE: single item inline fields --}}
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:end; margin-bottom:12px;">
            <div class="form-group" style="margin:0; flex:1; min-width:120px;">
                <label>Short Code</label>
                <input type="text" name="short_code" class="form-control" value="{{ $editItem->short_code ?? '' }}">
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:160px;">
                <label>Ledger Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ $editItem->title ?? '' }}" required>
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:120px;">
                <label>Opening Balance</label>
                <input type="text" name="opening_amount" class="form-control" value="{{ $editItem->opening_amount ?? '' }}">
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:100px;">
                <label>TDS value %</label>
                <input type="text" name="tds_value" class="form-control" value="{{ $editItem->tds_value ?? '' }}">
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:120px;">
                <label>Branch</label>
                <input type="text" name="bank_branch" class="form-control" value="{{ $bankData->branch ?? '' }}">
            </div>
            <div class="form-group" style="margin:0; flex:1; min-width:120px;">
                <label>Account No</label>
                <input type="text" name="account_no" class="form-control" value="{{ $bankData->account_no ?? '' }}">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <span style="font-weight:bold; font-size:13px; color:#2c3e50;">Bill Tariff Details</span>
            <div style="display:flex; flex-wrap:wrap; gap:16px; margin-top:6px;">
                <label style="font-size:13px;"><input type="checkbox" name="is_in_bill_charges" value="1" {{ $editItem->is_in_bill_charges ? 'checked' : '' }}> Is In Bill Charges</label>
                <label style="font-size:13px;"><input type="checkbox" name="is_tax_applicable" value="1" {{ $editItem->is_tax_applicable ? 'checked' : '' }}> Service Tax / GST Applicable?</label>
                <label style="font-size:13px;"><input type="checkbox" name="is_rebate_applicable" value="1" {{ $editItem->is_rebate_applicable ? 'checked' : '' }}> Rebate Application?</label>
                <label style="font-size:13px;"><input type="checkbox" name="is_interest_free" value="1" {{ $editItem->is_interest_free ? 'checked' : '' }}> Interest Free?</label>
                <label style="font-size:13px;"><input type="checkbox" name="is_supplementary_bill" value="1" {{ $editItem->is_supplementary_bill ? 'checked' : '' }}> Is Supplementary Bill</label>
                <label style="font-size:13px;"><input type="checkbox" name="is_tds" value="1" {{ $editItem->is_tds ? 'checked' : '' }}> Is tds</label>
            </div>
        </div>
        @endif

        <div style="margin-bottom:16px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Head' : 'Add Head' }}</button>
            <a href="{{ route('society.ledgerHeads') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:6px;">Cancel</a>
        </div>

        @if(!$editItem)
        {{-- ADD MODE: bulk grid table --}}
        <div style="overflow-x:auto;">
            <table class="table" style="font-size:12px;">
                <thead>
                    <tr style="background:#e9ecef;">
                        <th style="min-width:160px;">Ledger Head Title</th>
                        <th style="min-width:100px;">Short Code</th>
                        <th style="min-width:110px;">Opening Balance</th>
                        <th style="min-width:110px;">Bank Branch</th>
                        <th style="min-width:110px;">Account No</th>
                        <th style="min-width:50px;">TDS Type</th>
                        <th style="min-width:50px;">Is Supplementary Bill</th>
                        <th style="min-width:50px;">Is In Bill Charges</th>
                        <th style="min-width:50px;">Service Tax/GST Applicable</th>
                        <th style="min-width:50px;">TDS Applicable</th>
                        <th style="min-width:90px;">TDS set value in %</th>
                        <th style="min-width:50px;">Rebate</th>
                        <th style="min-width:50px;">Interest Free</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < 10; $i++)
                    <tr>
                        <td><input type="text" name="rows[{{ $i }}][title]" class="form-control" style="font-size:12px; padding:4px 6px;" {{ $i == 0 ? 'required' : '' }}></td>
                        <td><input type="text" name="rows[{{ $i }}][short_code]" class="form-control" style="font-size:12px; padding:4px 6px;"></td>
                        <td><input type="text" name="rows[{{ $i }}][op_amount]" class="form-control" style="font-size:12px; padding:4px 6px;"></td>
                        <td><input type="text" name="rows[{{ $i }}][bank_branch]" class="form-control" style="font-size:12px; padding:4px 6px;"></td>
                        <td><input type="text" name="rows[{{ $i }}][account_no]" class="form-control" style="font-size:12px; padding:4px 6px;"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][tds_type]" value="1"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_supplementary_bill]" value="1"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_in_bill_charges]" value="1"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_tax_applicable]" value="1"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_tds]" value="1"></td>
                        <td><input type="number" name="rows[{{ $i }}][tds_value]" class="form-control" style="font-size:12px; padding:4px 6px;"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_rebate_applicable]" value="1"></td>
                        <td style="text-align:center;"><input type="checkbox" name="rows[{{ $i }}][is_interest_free]" value="1"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        @endif
    </form>
</div>

<script>
document.getElementById('subGroupSelect').addEventListener('change', function() {
    var subGroupId = this.value;
    var catSelect = document.getElementById('accCatSelect');
    var headSelect = document.getElementById('accHeadSelect');
    catSelect.innerHTML = '';
    headSelect.innerHTML = '';
    if (!subGroupId) return;
    fetch('{{ route("society.getSubGroupDetails") }}?sub_group_id=' + subGroupId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.account_category) {
                var opt = document.createElement('option');
                opt.value = data.account_category.id;
                opt.textContent = data.account_category.title;
                catSelect.appendChild(opt);
            }
            if (data.account_head) {
                var opt2 = document.createElement('option');
                opt2.value = data.account_head.id;
                opt2.textContent = data.account_head.title;
                headSelect.appendChild(opt2);
            }
        });
});
</script>
@endsection
