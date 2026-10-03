@extends('layouts.app')

@section('title', 'Manage Partners - EasyLogics')

@section('content')
<div class="page-header">
    <h2>Channel Partners Showcase (Homepage)</h2>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Location</th>
                <th>Company</th>
                <th>Shown on Website</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($partners as $p)
            <tr>
                <td>
                    @if($p->image_path)
                        <img src="{{ asset($p->image_path) }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:50%;">
                    @else
                        <span class="text-muted">No photo</span>
                    @endif
                </td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->location }}</td>
                <td>{{ $p->company }}</td>
                <td>{{ (int)$p->display_status === 1 ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('admin.website.partners.edit', $p->id) }}" class="btn btn-primary btn-sm">Edit</a>
                    <form method="POST" action="{{ route('admin.website.partners.destroy', $p->id) }}" style="display:inline" onsubmit="return confirm('Remove this partner card?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-muted">No partners added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="max-width: 700px; margin-top: 16px;">
    <h3 style="margin-bottom:12px;">Add New Partner</h3>
    <form method="POST" action="{{ route('admin.website.partners') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label>Photo</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            <p class="text-muted" style="font-size:12px; margin-top:4px;">JPG, PNG or WebP. Max 2 MB.</p>
        </div>

        <div class="form-group">
            <label>Name <span class="required">*</span></label>
            <select class="form-control" name="name" id="partnerNameSelect" onchange="document.getElementById('partnerNameCustomWrap').style.display=(this.value==='__custom__')?'block':'none';" required>
                <option value="">-- Select active Reseller --</option>
                @foreach($resellersList as $uname)
                    <option value="{{ $uname }}">{{ $uname }}</option>
                @endforeach
                <option value="__custom__">Other (type manually)</option>
            </select>
            <div id="partnerNameCustomWrap" style="display:none;margin-top:8px">
                <input type="text" class="form-control" name="name_custom" maxlength="150" placeholder="Enter name">
            </div>
            <p class="text-muted" style="font-size:12px; margin-top:4px;">Pick a real active Reseller so the card shows their actual name, or choose "Other" to type one.</p>
        </div>

        <div class="form-group">
            <label>Location</label>
            <input type="text" class="form-control" name="location" maxlength="100" placeholder="e.g. Mumbai">
        </div>

        <div class="form-group">
            <label>Company</label>
            <input type="text" class="form-control" name="company" maxlength="150" placeholder="e.g. ABC Accounting Services">
        </div>

        <div class="form-group">
            <label>Short Description</label>
            <textarea class="form-control" name="description" rows="3" maxlength="400"></textarea>
        </div>

        <div class="form-group">
            <label style="font-weight:normal">
                <input type="checkbox" name="display_status" value="1" checked>
                Display this card on the homepage
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Add Partner</button>
    </form>
</div>
@endsection
