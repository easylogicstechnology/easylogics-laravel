@extends('layouts.app')
@section('title', 'Society Head Sub Category')
@section('content')
<div class="page-header">
    <h2>Society Head Sub Category</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="POST" action="{{ route('society.headSubCategories') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $editItem->id ?? '' }}">
        <div class="grid-4" style="align-items:end;">
            <div class="form-group">
                <label>Account Sub Group <span class="required">*</span></label>
                @if($editItem)
                <input type="text" name="title" class="form-control" value="{{ $editItem->title ?? '' }}" required>
                @else
                <textarea name="title" class="form-control" style="height:100px;" placeholder="Enter Head Title (one per line for bulk add)" required>{{ old('title') }}</textarea>
                @endif
            </div>
            <div class="form-group">
                <label>Account Category <span class="required">*</span></label>
                <select name="account_category_id" class="form-control" id="accCatSelect" required>
                    <option value="">Select</option>
                    @foreach($accountCategories as $catId => $catName)
                    <option value="{{ $catId }}" {{ ($editItem && $editItem->account_category_id == $catId) ? 'selected' : '' }}>{{ $catName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Account Group</label>
                <select name="account_head_id" class="form-control" id="accHeadSelect">
                    <option value="">Select</option>
                    @if($editItem && $editItem->accountHead)
                    <option value="{{ $editItem->account_head_id }}" selected>{{ $editItem->accountHead->title }}</option>
                    @endif
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <div style="padding-top:6px;">
                    <label style="margin-right:12px;"><input type="radio" name="account_status" value="0" {{ (!$editItem || $editItem->account_status == 0) ? 'checked' : '' }}> Active</label>
                    <label><input type="radio" name="account_status" value="1" {{ ($editItem && $editItem->account_status == 1) ? 'checked' : '' }}> Inactive</label>
                </div>
            </div>
        </div>
        <div style="margin-top:8px;">
            <button type="submit" class="btn btn-success">{{ $editItem ? 'Update Head Category' : 'Add Head Category' }}</button>
            @if($editItem)
            <a href="{{ route('society.headSubCategories') }}" class="btn btn-sm" style="background:#999; color:#fff; margin-left:8px;">Cancel</a>
            @endif
        </div>
    </form>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account Category</th>
                    <th>Account Head</th>
                    <th>Society Head Sub Group</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->accountCategory->title ?? '' }}</td>
                    <td>{{ $item->accountHead->title ?? '' }}</td>
                    <td>{{ $item->title }}</td>
                    <td>
                        @if($item->account_status == 0)
                        <span style="background:#337ab7; color:#fff; padding:2px 8px; border-radius:3px; font-size:12px;">Active</span>
                        @else
                        <span style="background:#d9534f; color:#fff; padding:2px 8px; border-radius:3px; font-size:12px;">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center; color:#999;">No sub categories found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('accCatSelect').addEventListener('change', function() {
    var catId = this.value;
    var headSelect = document.getElementById('accHeadSelect');
    headSelect.innerHTML = '<option value="">Select</option>';
    if (!catId) return;
    fetch('{{ route("society.getAccountHeads") }}?category_id=' + catId)
        .then(function(r) { return r.json(); })
        .then(function(heads) {
            heads.forEach(function(h) {
                var opt = document.createElement('option');
                opt.value = h.id;
                opt.textContent = h.title;
                headSelect.appendChild(opt);
            });
        });
});
</script>
@endsection
