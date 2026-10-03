{{-- "Select (if required)" box of the member-wise reports: Building / Wing / Unit range. $input, $buildings, $wingId --}}
<div class="ar-row">
    <div class="ar-field">
        <label>Building</label>
        <select name="building_id" id="ar_building_id" onchange="arLoadWings(this.value);">
            <option value="">Select building</option>
            @foreach($buildings as $bid => $bname)
                <option value="{{ $bid }}" {{ (string) ($input['building_id'] ?? '') === (string) $bid ? 'selected' : '' }}>{{ $bname }}</option>
            @endforeach
        </select>
    </div>
    <div class="ar-field">
        <label>Wing</label>
        <select name="wing_id" id="ar_wing_id" data-selected="{{ $input['wing_id'] ?? '' }}">
            <option value="">Select Wing</option>
        </select>
    </div>
    <div class="ar-field">
        <label>Unit No</label>
        <input type="text" name="flat_no" value="{{ $input['flat_no'] ?? '' }}" style="width:90px">
    </div>
    <div class="ar-field">
        <label>To</label>
        <input type="text" name="flat_no_to" value="{{ $input['flat_no_to'] ?? '' }}" style="width:90px">
    </div>
</div>
<script>
function arLoadWings(buildingId, selected) {
    var sel = document.getElementById('ar_wing_id');
    sel.innerHTML = '<option value="">Select Wing</option>';
    if (!buildingId) { return; }
    fetch('{{ route('society.getWings') }}?building_id=' + encodeURIComponent(buildingId), {headers: {'Accept': 'application/json'}})
        .then(function (r) { return r.json(); })
        .then(function (wings) {
            Object.keys(wings).forEach(function (id) {
                var o = document.createElement('option');
                o.value = id; o.textContent = wings[id];
                if (selected && String(selected) === String(id)) { o.selected = true; }
                sel.appendChild(o);
            });
        });
}
document.addEventListener('DOMContentLoaded', function () {
    var b = document.getElementById('ar_building_id');
    if (b && b.value) { arLoadWings(b.value, document.getElementById('ar_wing_id').getAttribute('data-selected')); }
});
</script>
