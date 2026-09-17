@php $isEdit = isset($transaction) && $transaction; @endphp
<div class="row">
    <div class="col-md-4 mb-5">
        <label class="required form-label">المستودع</label>
        <select name="warehouse_id" class="form-select form-select-solid" {{ $isEdit ? 'disabled' : '' }} required>
            <option value="">اختر المستودع</option>
            @foreach($warehouses as $w)
                <option value="{{$w->id}}" @selected($isEdit && $transaction->warehouse_id == $w->id)>{{$w->name}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-5">
        <label class="required form-label">المنتج</label>
        <select name="product_id" id="product_id" class="form-select form-select-solid" {{ $isEdit ? 'disabled' : '' }} required>
            <option value="">اختر المنتج</option>
            @foreach($products as $p)
                <option value="{{$p->id}}" @selected($isEdit && $transaction->product_id == $p->id)>{{$p->name}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-5">
        <label class="form-label">المتغير (إن وجد)</label>
        <select name="variant_id" id="variant_id" class="form-select form-select-solid" {{ $isEdit ? 'disabled' : '' }}>
            <option value="">بدون متغير</option>
            @if($isEdit && $transaction->variant)
                <option value="{{$transaction->variant_id}}" selected>{{$transaction->variant->sku}}</option>
            @endif
        </select>
    </div>

    <div class="col-md-4 mb-5">
        <label class="required form-label">نوع الحركة</label>
        <select name="type" class="form-select form-select-solid" {{ $isEdit ? 'disabled' : '' }} required>
            <option value="in" @selected($isEdit && $transaction->type == 'in')>وارد</option>
            <option value="out" @selected($isEdit && $transaction->type == 'out')>صادر</option>
            <option value="adjustment" @selected($isEdit && $transaction->type == 'adjustment')>تعديل</option>
        </select>
    </div>
    <div class="col-md-4 mb-5">
        <label class="required form-label">الكمية</label>
        <input type="number" step="0.001" name="quantity" class="form-control form-control-solid"
               value="{{ $isEdit ? $transaction->quantity : old('quantity') }}" {{ $isEdit ? 'disabled' : '' }} required>
    </div>
    <div class="col-md-4 mb-5">
        <label class="form-label">تكلفة الوحدة</label>
        <input type="number" step="0.01" name="unit_cost" class="form-control form-control-solid"
               value="{{ $isEdit ? $transaction->unit_cost : old('unit_cost') }}" {{ $isEdit ? 'disabled' : '' }}>
    </div>
    <div class="col-md-4 mb-5">
        <label class="form-label">الوحدة</label>
        <select name="unit_id" class="form-select form-select-solid" {{ $isEdit ? 'disabled' : '' }}>
            <option value="">بدون تحديد</option>
            @foreach($units as $u)
                <option value="{{$u->id}}" @selected($isEdit && $transaction->unit_id == $u->id)>{{$u->name}}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12 mb-5">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control form-control-solid" rows="3">{{ $isEdit ? $transaction->notes : old('notes') }}</textarea>
    </div>
</div>
@if($isEdit)
<input type="hidden" name="id" value="{{$transaction->id}}">
<div class="alert alert-warning">لا يمكن تعديل المستودع/المنتج/الكمية/النوع بعد التسجيل لضمان صحة الرصيد. لتصحيح الكمية، احذف الحركة وأضف حركة جديدة.</div>
@endif
