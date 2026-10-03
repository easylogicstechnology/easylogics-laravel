{{-- Filter of the bill print screens (CakePHP bill_half_page.ctp and its copies). $action, $input, $months, and the flags $showReceiptPeriod, $showUnitArea, $showEmail --}}
<form method="post" action="{{ $action }}" class="ar-form" autocomplete="off">
    @csrf
    @if(!empty($prevDataFilter))
        <div class="ar-row">
            <div class="ar-field">
                <label>Months</label>
                <select name="month">
                    <option value="">Select Month</option>
                    @foreach($months as $id => $name)
                        <option value="{{ $id }}" {{ (string) ($input['month'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ar-field">
                <label>Bill Type</label>
                <select name="bill_type">
                    <option value="">Select Bill Type</option>
                    <option value="reg" {{ ($input['bill_type'] ?? '') === 'reg' ? 'selected' : '' }}>Regular</option>
                    <option value="sup" {{ ($input['bill_type'] ?? '') === 'sup' ? 'selected' : '' }}>Supplementary</option>
                </select>
            </div>
        </div>
        <div class="ar-row">
            <div class="ar-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <a href="{{ $action }}" class="btn btn-warning">Cancel</a>
            </div>
        </div>
    @else
    <div class="ar-row">
        <div class="ar-field">
            <label>From</label>
            <select name="month">
                <option value="">Select Month</option>
                @foreach($months as $id => $name)
                    <option value="{{ $id }}" {{ (string) ($input['month'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ar-field">
            <label>To</label>
            <select name="month_to">
                <option value="">Select Month</option>
                @foreach($months as $id => $name)
                    <option value="{{ $id }}" {{ (string) ($input['month_to'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @if(!empty($showUnitArea))
            <div class="ar-field">
                <label>Unit Area</label>
                <select name="unit_area">
                    <option value="">Select Unit Area</option>
                    <option value="SqFt" {{ ($input['unit_area'] ?? 'SqFt') === 'SqFt' ? 'selected' : '' }}>Sq. Ft.</option>
                    <option value="SqMtr" {{ ($input['unit_area'] ?? '') === 'SqMtr' ? 'selected' : '' }}>Sq. Mtr.</option>
                </select>
            </div>
        @endif
    </div>
    <div class="ar-row">
        <div class="ar-field"><label>Bill Date</label><input type="date" name="bill_date" value="{{ $input['bill_date'] ?? '' }}"></div>
        <div class="ar-field"><label>To</label><input type="date" name="bill_date_to" value="{{ $input['bill_date_to'] ?? '' }}"></div>
        <div class="ar-field"><label>Bill No</label><input type="text" name="bill_no" style="width:100px" value="{{ $input['bill_no'] ?? '' }}"></div>
        <div class="ar-field"><label>To</label><input type="text" name="bill_no_to" style="width:100px" value="{{ $input['bill_no_to'] ?? '' }}"></div>
        <div class="ar-field"><label>Unit No</label><input type="text" name="flat_no" style="width:100px" value="{{ $input['flat_no'] ?? '' }}"></div>
        <div class="ar-field"><label>To</label><input type="text" name="flat_no_to" style="width:100px" value="{{ $input['flat_no_to'] ?? '' }}"></div>
        @if(!empty($showReceiptPeriod))
            <div class="ar-field"><label>Receipt Period From</label><input type="date" name="from_date" value="{{ $input['from_date'] ?? '' }}"></div>
            <div class="ar-field"><label>To</label><input type="date" name="to_date" value="{{ $input['to_date'] ?? '' }}"></div>
        @endif
    </div>
    <div class="ar-row">
        <div class="ar-actions">
            <button type="submit" class="btn btn-success">Submit</button>
            @if(!empty($showEmail))
                <button type="submit" name="submit" value="email" class="btn btn-success">Email</button>
            @endif
            <a href="{{ $action }}" class="btn btn-warning">Cancel</a>
        </div>
    </div>
    @endif
</form>
