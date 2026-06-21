{{-- ======================================================
     purchases/form.blade.php
     Variables available:
       $warehouses        — Collection<Warehouse>
       $purchaseNumber    — string  (create only)
       $data              — Purchase model (edit only)
====================================================== --}}
@php
    $isEdit = isset($data);
    $d      = $isEdit ? $data : null;
@endphp

{{-- Validation errors --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-5">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ── Header card ────────────────────────────────────── --}}
<div class="card card-flush py-4 mb-7">
    <div class="card-header">
        <div class="card-title">
            <h2>بيانات الفاتورة</h2>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row g-5">
            {{-- Supplier --}}
            <div class="col-md-4 fv-row">
                <label class="form-label required">المورد</label>
                <select class="form-select form-select-solid" name="supplier_id" id="supplier_select"
                        data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                        @if($isEdit && $d->supplier)
                        <option value="{{$d->supplier_id}}" selected>
                            {{$d->supplier->company_name ?: $d->supplier->name}}
                        </option>
                    @endif
                </select>
                @error('supplier_id')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Warehouse --}}
            <div class="col-md-4 fv-row">
                <label class="form-label required">المستودع</label>
                <select class="form-select form-select-solid" name="warehouse_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                    <option value="">اختر المستودع</option>
                    @foreach($warehouses as $w)
                        <option value="{{$w->id}}" {{old('warehouse_id', $isEdit ? $d->warehouse_id : '') == $w->id ? 'selected' : ''}}>{{$w->name}}</option>
                    @endforeach
                </select>
                @error('warehouse_id')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Purchase number --}}
            <div class="col-md-4 fv-row">
                <label class="form-label">رقم الفاتورة</label>
                <input type="text" name="purchase_number" class="form-control form-control-solid"
                       value="{{old('purchase_number', $isEdit ? $d->purchase_number : ($purchaseNumber ?? ''))}}"
                       placeholder="PO-0001" readonly>
            </div>

            {{-- Date --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">تاريخ الفاتورة</label>
                <input type="date" name="date" class="form-control form-control-solid"
                       value="{{old('date', $isEdit ? $d->date?->format('Y-m-d') : date('Y-m-d'))}}">
                @error('date')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Due date --}}
            <div class="col-md-3 fv-row">
                <label class="form-label">تاريخ الاستحقاق</label>
                <input type="date" name="due_date" class="form-control form-control-solid"
                       value="{{old('due_date', $isEdit ? $d->due_date?->format('Y-m-d') : '')}}">
                @error('due_date')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Payment type --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">نوع الدفع</label>
                <select class="form-select form-select-solid" name="payment_type" data-kt-select2="true" data-minimum-results-for-search="Infinity" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                    <option value="">اختر نوع الدفع</option>
                    @foreach(\App\Models\Finance\Purchase::$paymentTypeLabels as $key => $label)
                        <option value="{{$key}}" {{old('payment_type', $isEdit ? $d->payment_type : 'cash') == $key ? 'selected' : ''}}>{{$label}}</option>
                    @endforeach
                </select>
                @error('payment_type')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Status --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">الحالة</label>
                <select class="form-select form-select-solid" name="status" data-kt-select2="true" data-minimum-results-for-search="Infinity" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                    <option value="">اختر الحالة</option>
                    @foreach(\App\Models\Finance\Purchase::$statusLabels as $key => $label)
                        <option value="{{$key}}" {{old('status', $isEdit ? $d->status : 'received') == $key ? 'selected' : ''}}>{{$label}}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Notes --}}
            <div class="col-12 fv-row">
                <label class="form-label">الملاحظات</label>
                <textarea name="notes" class="form-control form-control-solid" rows="2" placeholder="ملاحظات اختيارية ...">{{old('notes', $isEdit ? $d->notes : '')}}</textarea>
                @error('notes')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- ── Items card ──────────────────────────────────────── --}}
<div class="card card-flush py-4 mb-7">
    <div class="card-header">
        <div class="card-title">
            <h2>بنود الفاتورة</h2>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary" id="addItemBtn">
                <i class="ki-duotone ki-plus fs-4"><span class="path1"></span><span class="path2"></span></i>
                إضافة بند
            </button>
        </div>
    </div>
    <div class="card-body pt-0">
        @error('items')<div class="alert alert-danger mb-4">{{$message}}</div>@enderror
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3" id="items-table">
                <thead>
                    <tr class="text-start text-dark bg-light-dark fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-200px">المنتج</th>
                        <th class="min-w-150px">المتغير</th>
                        <th class="w-150px">الوحدة</th>
                        <th class="w-100px">الكمية</th>
                        <th class="w-120px">سعر الوحدة</th>
                        <th class="w-100px">الخصم</th>
                        <th class="w-80px">ض%</th>
                        <th class="w-120px text-end">الإجمالي</th>
                        <th class="w-50px"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @if($isEdit && $d->items->count())
                        @foreach($d->items as $i)
                        <tr class="item-row">
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-product" name="items[{{$loop->index}}][product_id]"
                                        data-placeholder="ابحث عن منتج ...">
                                    <option value="{{$i->product_id}}" selected>{{$i->product?->name}} ({{$i->product?->sku}})</option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-variant" name="items[{{$loop->index}}][variant_id]">
                                    <option value="">بدون متغير</option>
                                    @if($i->variant)
                                        <option value="{{$i->variant_id}}" selected>{{$i->variant->sku}}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-unit"
                                        name="items[{{$loop->index}}][unit_conversion_id]"
                                        data-unit-id="{{ $i->unit_conversion_id }}" 
                                        data-kt-select2="true" data-minimum-results-for-search="Infinity" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                                    <option value="">الوحدة الأساسية</option>
                                    @if($i->unitConversion)
                                        <option value="{{ $i->unit_conversion_id }}" selected
                                                data-conversion-rate="{{ $i->unitConversion->conversion_rate }}"
                                                data-allow-fractions="{{ $i->unitConversion->allow_fractions ? 'true' : 'false' }}"
                                                data-decimal-places="{{ $i->unitConversion->decimal_places }}">
                                            {{ $i->unitConversion->target_unit }} (× {{ $i->unitConversion->conversion_rate }} {{ $i->unitConversion->base_unit }})
                                        </option>
                                    @endif
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-qty" name="items[{{$loop->index}}][quantity]" value="{{$i->quantity}}" min="0.001" step="0.001"></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-cost" name="items[{{$loop->index}}][unit_cost]" value="{{$i->unit_cost}}" min="0" step="0.01"></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-disc" name="items[{{$loop->index}}][discount]" value="{{$i->discount}}" min="0" step="0.01"></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-tax" name="items[{{$loop->index}}][tax_rate]" value="{{$i->tax_rate}}" min="0" max="100" step="0.01"></td>
                            <td class="text-end fw-bold item-total-cell">{{number_format((float)$i->total, 2)}}</td>
                            <td><button type="button" class="btn btn-icon btn-xs btn-light-danger remove-row"><i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Totals + footer ─────────────────────────────────── --}}
<div class="card card-flush py-4">
    <div class="card-body pt-0">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-5">
                    <label class="form-label">المبلغ المدفوع</label>
                    <div class="input-group">
                        <input type="number" name="paid" id="paid_input" class="form-control form-control-solid"
                               value="{{old('paid', $isEdit ? $d->paid : 0)}}" min="0" step="0.01">
                        <button type="button" id="fill_paid_btn" class="btn btn-light-info fw-semibold" title="تعبئة بالإجمالي"> 
                            <i class="ki-duotone ki-double-check fs-5"><span class="path1"></span><span class="path2"></span></i>
                            كامل
                        </button>
                        <span class="input-group-text">ر.س</span>
                    </div>
                    @error('paid')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered fs-6 fw-semibold">
                    <tr>
                        <td class="text-gray-600 w-50">المجموع الفرعي</td>
                        <td class="text-end" id="summary-subtotal">0.00 ر.س</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600">الخصم الكلي</td>
                        <td class="text-end text-danger" id="summary-discount">0.00 ر.س</td>
                    </tr>
                    <tr>
                        <td class="text-gray-600">الضريبة</td>
                        <td class="text-end text-warning" id="summary-tax">0.00 ر.س</td>
                    </tr>
                    <tr class="fs-5">
                        <td class="fw-bold">الإجمالي</td>
                        <td class="text-end fw-bolder text-success" id="summary-total">0.00 ر.س</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-end py-6 px-9">
        <a href="{{route($route.'.index')}}" class="btn btn-light me-3">إلغاء</a>
        <button type="submit" class="btn btn-dark fw-bold">
            <i class="ki-duotone ki-check fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{$isEdit ? 'حفظ التغييرات' : 'إضافة الفاتورة'}}
        </button>
    </div>
</div>
