@extends('layouts.app')
@section('title', 'Society Tariff Order')
@section('content')
<div class="page-header">
    <h2>Set Society Tariff Order</h2>
</div>

<div class="card">
    @if($items->count() > 0)
    <p style="font-size:13px; color:#666; margin-bottom:12px;">Drag items to reorder, then click "Set Order" to save.</p>
    <ul id="sortableList" style="list-style:none; padding:0; margin:0; max-width:500px;">
        @foreach($items as $lh)
        <li data-id="{{ $lh->id }}" style="padding:8px 12px; margin-bottom:4px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; cursor:move; display:flex; align-items:center; gap:8px;">
            <span style="color:#999; font-size:14px;">&#9776;</span>
            {{ $lh->title }}
        </li>
        @endforeach
    </ul>
    <div style="margin-top:16px;">
        <button type="button" class="btn btn-success" onclick="saveTariffOrder()">Set Order</button>
    </div>
    @else
    <p style="text-align:center; color:#999;">No ledger heads with bill charges found. Add ledger heads with "Include in Bill Charges" enabled first.</p>
    @endif
</div>

<script>
(function() {
    var list = document.getElementById('sortableList');
    if (!list) return;
    var dragged = null;

    list.addEventListener('dragstart', function(e) {
        dragged = e.target.closest('li');
        if (dragged) {
            dragged.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
        }
    });
    list.addEventListener('dragend', function(e) {
        if (dragged) dragged.style.opacity = '1';
        dragged = null;
        var items = list.querySelectorAll('li');
        for (var i = 0; i < items.length; i++) {
            items[i].style.borderTop = '';
        }
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('li');
        var items = list.querySelectorAll('li');
        for (var i = 0; i < items.length; i++) items[i].style.borderTop = '';
        if (target && target !== dragged) {
            target.style.borderTop = '2px solid #337ab7';
        }
    });
    list.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('li');
        if (target && dragged && target !== dragged) {
            list.insertBefore(dragged, target);
        }
    });

    var items = list.querySelectorAll('li');
    for (var i = 0; i < items.length; i++) {
        items[i].draggable = true;
    }
})();

function saveTariffOrder() {
    var items = document.querySelectorAll('#sortableList li');
    var order = [];
    for (var i = 0; i < items.length; i++) {
        order.push(items[i].getAttribute('data-id'));
    }
    var token = document.querySelector('meta[name="csrf-token"]').content;
    fetch('{{ route("society.saveTariffOrder") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
        body: JSON.stringify({order: order})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message);
        } else {
            alert('Error saving order.');
        }
    })
    .catch(function() {
        alert('Error saving order.');
    });
}
</script>
@endsection
