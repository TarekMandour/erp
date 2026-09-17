{{-- ======================================================
     orders/form.blade.php
     Variables: $warehouses, $orderNumber (create), $data (edit)
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
        <div class="card-title"><h2>بيانات الطلب</h2></div>
    </div>
    <div class="card-body pt-0">
        <div class="row g-5">

            {{-- Customer --}}
            <div class="col-md-4 fv-row">
                <label class="form-label required">العميل</label>
                <select class="form-select form-select-solid" name="customer_id" id="customer_select"
                        data-placeholder="ابحث عن عميل ...">
                    @if($isEdit && $d->customer)
                        <option value="{{$d->customer_id}}" selected>
                            {{$d->customer->name}} {{$d->customer->customer_code ? '('.$d->customer->customer_code.')' : ''}}
                        </option>
                    @endif
                </select>
                @error('customer_id')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Warehouse --}}
            <div class="col-md-4 fv-row">
                <label class="form-label required">المستودع</label>
                <select class="form-select form-select-solid" name="warehouse_id" id="warehouse_select">
                    <option value="">اختر المستودع</option>
                    @foreach($warehouses as $w)
                        <option value="{{$w->id}}" {{old('warehouse_id', $isEdit ? $d->warehouse_id : '') == $w->id ? 'selected' : ''}}>{{$w->name}}</option>
                    @endforeach
                </select>
                @error('warehouse_id')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Order number --}}
            <div class="col-md-4 fv-row">
                <label class="form-label">رقم الطلب</label>
                <input type="text" name="order_number" class="form-control form-control-solid"
                       value="{{old('order_number', $isEdit ? $d->order_number : ($orderNumber ?? ''))}}"
                       placeholder="SO-0001" readonly>
            </div>

            {{-- Date --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">تاريخ الطلب</label>
                <input type="date" name="date" class="form-control form-control-solid"
                       value="{{old('date', $isEdit ? $d->date?->format('Y-m-d') : date('Y-m-d'))}}">
                @error('date')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Delivery date --}}
            <div class="col-md-3 fv-row">
                <label class="form-label">تاريخ التسليم</label>
                <input type="date" name="delivery_date" class="form-control form-control-solid"
                       value="{{old('delivery_date', $isEdit ? $d->delivery_date?->format('Y-m-d') : '')}}">
                @error('delivery_date')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Payment type --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">طريقة الدفع</label>
                <select class="form-select form-select-solid" name="payment_type">
                    @foreach(\App\Models\Finance\Order::$paymentTypeLabels as $key => $label)
                        <option value="{{$key}}" {{old('payment_type', $isEdit ? $d->payment_type : 'cash') == $key ? 'selected' : ''}}>{{$label}}</option>
                    @endforeach
                </select>
                @error('payment_type')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Status --}}
            <div class="col-md-3 fv-row">
                <label class="form-label required">الحالة</label>
                <select class="form-select form-select-solid" name="status">
                    @foreach(\App\Models\Finance\Order::$statusLabels as $key => $label)
                        <option value="{{$key}}" {{old('status', $isEdit ? $d->status : 'draft') == $key ? 'selected' : ''}}>{{$label}}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Shipping address --}}
            <div class="col-md-8 fv-row">
                <label class="form-label">عنوان الشحن</label>
                <input type="text" name="shipping_address" class="form-control form-control-solid"
                       value="{{old('shipping_address', $isEdit ? $d->shipping_address : '')}}"
                       placeholder="عنوان التسليم (اختياري)">
                @error('shipping_address')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Shipping cost --}}
            <div class="col-md-4 fv-row">
                <label class="form-label">تكلفة الشحن</label>
                <div class="input-group">
                    <input type="number" name="shipping_cost" id="shipping_cost" class="form-control form-control-solid"
                           value="{{old('shipping_cost', $isEdit ? $d->shipping_cost : 0)}}" min="0" step="0.01">
                    <span class="input-group-text">ر.س</span>
                </div>
                @error('shipping_cost')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>

            {{-- Notes --}}
            <div class="col-12 fv-row">
                <label class="form-label">الملاحظات</label>
                <textarea name="notes" class="form-control form-control-solid" rows="2"
                          placeholder="ملاحظات اختيارية ...">{{old('notes', $isEdit ? $d->notes : '')}}</textarea>
                @error('notes')<div class="text-danger fs-7 mt-1">{{$message}}</div>@enderror
            </div>
        </div>
    </div>
</div>

{{-- ── Cart / Items card ───────────────────────────────── --}}
<div class="card card-flush py-4 mb-7">
    <div class="card-header">
        <div class="card-title"><h2>سلة المنتجات</h2></div>
        <div class="card-toolbar">
            {{-- Barcode / SKU scanner input --}}
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative" style="min-width:280px">
                    <i class="ki-duotone ki-barcode position-absolute top-50 translate-middle-y ms-3 fs-3 text-gray-500" style="right:auto;left:10px">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                    </i>
                    <input type="text" id="scanner_input"
                           class="form-control form-control-solid form-control-sm ps-13"
                           placeholder="امسح الباركود أو أدخل رمز المنتج (SKU) ..."
                           autocomplete="off" spellcheck="false">
                </div>
                <span id="scanner_feedback" class="text-muted fs-7"></span>
                <button type="button" id="addRowBtn" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-plus fs-4"><span class="path1"></span><span class="path2"></span></i>
                    إضافة منتج
                </button>
            </div>
        </div>
    </div>
    <div class="card-body pt-0">
        @error('items')<div class="alert alert-danger mb-4">{{$message}}</div>@enderror

        {{-- Stock warning --}}
        <div id="stock-warning" class="alert alert-warning d-none mb-4">
            <i class="ki-duotone ki-warning fs-3 me-2"><span class="path1"></span><span class="path2"></span></i>
            <span id="stock-warning-text"></span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-3" id="items-table">
                <thead>
                    <tr class="text-start text-dark bg-light-dark fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-200px">المنتج</th>
                        <th class="min-w-140px">المتغير</th>
                        <th class="w-120px">الوحدة</th>
                        <th class="w-100px">الكمية</th>
                        <th class="w-30px text-center text-info" title="المخزون المتاح">
                            <i class="ki-duotone ki-package fs-5 text-info" title="المتاح"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        </th>
                        <th class="w-120px">سعر الوحدة</th>
                        <th class="w-110px">تكلفة الوحدة</th>
                        <th class="w-100px">الخصم</th>
                        <th class="w-80px">ض%</th>
                        <th class="w-120px text-end">الإجمالي</th>
                        <th class="w-50px"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    @if(!$isEdit)
                    {{-- Default empty row for new orders --}}
                    <tr class="item-row default-row">
                        <td>
                            <select class="form-select form-select-solid form-select-sm item-product"
                                    name="items[0][product_id]"
                                    data-placeholder="ابحث عن منتج ..."></select>
                        </td>
                        <td>
                            <select class="form-select form-select-solid form-select-sm item-variant"
                                    name="items[0][variant_id]">
                                <option value="">بدون متغير</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-solid form-select-sm item-unit"
                                    name="items[0][unit_conversion_id]">
                                <option value="">الوحدة الأساسية</option>
                            </select>
                        </td>
                        <td><input type="number" class="form-control form-control-solid form-control-sm item-qty"
                                   name="items[0][quantity]" value="1" min="0.001" step="0.001"></td>
                        <td class="text-center">
                            <span class="badge badge-light-secondary item-stock-badge" title="المخزون المتاح">—</span>
                        </td>
                        <td><input type="number" class="form-control form-control-solid form-control-sm item-price"
                                   name="items[0][unit_price]" value="0.00" min="0" step="0.01"
                                   data-base-price="0"></td>
                        <td><input type="number" class="form-control form-control-solid form-control-sm item-unit-cost"
                                   name="items[0][unit_cost]" value="0.00" min="0" step="0.0001"
                                   placeholder="0.00" readonly></td>
                        <td><input type="number" class="form-control form-control-solid form-control-sm item-disc"
                                   name="items[0][discount]" value="0.00" min="0" step="0.01"></td>
                        <td><input type="number" class="form-control form-control-solid form-control-sm item-tax"
                                   name="items[0][tax_rate]" value="0.00" min="0" max="100" step="0.01"></td>
                        <td class="text-end fw-bold item-total-cell">0.00</td>
                        <td>
                            <button type="button" class="btn btn-icon btn-xs btn-light-danger remove-row">
                                <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                            </button>
                        </td>
                    </tr>
                    @endif
                    @if($isEdit && $d->items->count())
                        @foreach($d->items as $item)
                        <tr class="item-row">
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-product"
                                        name="items[{{$loop->index}}][product_id]"
                                        data-placeholder="ابحث عن منتج ...">
                                    <option value="{{$item->product_id}}" selected
                                            data-price="{{$item->product?->selling_price}}"
                                            data-tax="{{$item->product?->tax_rate}}">
                                        {{$item->product?->name}} ({{$item->product?->sku}})
                                    </option>
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-variant"
                                        name="items[{{$loop->index}}][variant_id]">
                                    <option value="">بدون متغير</option>
                                    @if($item->variant)
                                        <option value="{{$item->variant_id}}" selected>{{$item->variant->sku}}</option>
                                    @endif
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-solid form-select-sm item-unit"
                                        name="items[{{$loop->index}}][unit_conversion_id]"
                                        data-selected="{{$item->unit_conversion_id}}">
                                    <option value="">الوحدة الأساسية</option>
                                    @if($item->unitConversion)
                                        <option value="{{$item->unit_conversion_id}}" selected
                                                data-conversion-rate="{{$item->unitConversion->conversion_rate}}"
                                                data-allow-fractions="{{$item->unitConversion->allow_fractions ? 'true' : 'false'}}"
                                                data-decimal-places="{{$item->unitConversion->decimal_places}}">
                                            {{$item->unitConversion->target_unit}} (× {{ rtrim(rtrim((string)$item->unitConversion->conversion_rate, '0'), '.') }} {{$item->unitConversion->base_unit}})
                                        </option>
                                    @endif
                                </select>
                            </td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-qty"
                                       name="items[{{$loop->index}}][quantity]"
                                       value="{{$item->quantity}}" min="0.001" step="0.001"></td>
                            <td class="text-center">
                                <span class="badge badge-light-info item-stock-badge" title="المخزون المتاح">—</span>
                            </td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-price"
                                       name="items[{{$loop->index}}][unit_price]"
                                       value="{{$item->unit_price}}" min="0" step="0.01"
                                       data-base-price="{{ $item->unitConversion ? round((float)$item->unit_price / (float)$item->unitConversion->conversion_rate, 6) : (float)$item->unit_price }}"></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-unit-cost"
                                       name="items[{{$loop->index}}][unit_cost]"
                                       value="{{$item->unit_cost}}" min="0" step="0.0001"
                                       placeholder="0.00" readonly></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-disc"
                                       name="items[{{$loop->index}}][discount]"
                                       value="{{$item->discount}}" min="0" step="0.01"></td>
                            <td><input type="number" class="form-control form-control-solid form-control-sm item-tax"
                                       name="items[{{$loop->index}}][tax_rate]"
                                       value="{{$item->tax_rate}}" min="0" max="100" step="0.01"></td>
                            <td class="text-end fw-bold item-total-cell">{{number_format((float)$item->total, 2)}}</td>
                            <td>
                                <button type="button" class="btn btn-icon btn-xs btn-light-danger remove-row">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </td>
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
                    {{-- Coupon --}}
                    <label class="form-label">كوبون الخصم</label>
                    <div class="input-group">
                        <input type="text" id="coupon_code_input"
                               class="form-control form-control-solid"
                               placeholder="أدخل رمز الكوبون ..."
                               value="{{ $isEdit && $d->coupon ? $d->coupon->code : '' }}"
                               autocomplete="off" spellcheck="false">
                        <button type="button" id="apply_coupon_btn" class="btn btn-light-primary fw-semibold">
                            تطبيق
                        </button>
                        <button type="button" id="remove_coupon_btn" class="btn btn-light-danger fw-semibold {{ ($isEdit && $d->coupon_id) ? '' : 'd-none' }}">
                            <i class="ki-duotone ki-cross fs-5"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    </div>
                    <div id="coupon_feedback" class="fs-7 mt-1 {{ ($isEdit && $d->coupon_id) ? 'text-success' : 'text-muted' }}">
                        @if($isEdit && $d->coupon_id)
                            تم تطبيق الكوبون — خصم {{ number_format((float)$d->coupon_discount, 2) }} ر.س
                        @endif
                    </div>
                    <input type="hidden" name="coupon_id" id="coupon_id_input" value="{{ $isEdit ? ($d->coupon_id ?? '') : '' }}">
                    <input type="hidden" name="coupon_discount" id="coupon_discount_input" value="{{ $isEdit ? (float)$d->coupon_discount : 0 }}">
                </div>
                <div class="mb-5">
                    {{-- Offer --}}
                    <label class="form-label">العرض / الترقية</label>
                    <div class="input-group">
                        <select class="form-select form-select-solid" id="offer_select" style="max-width:calc(100% - 100px)">
                            @if($isEdit && $d->offer_id && $d->offer)
                                <option value="{{ $d->offer_id }}" selected>{{ $d->offer->name }}</option>
                            @endif
                        </select>
                        <button type="button" id="apply_offer_btn" class="btn btn-light-success fw-semibold">
                            تطبيق
                        </button>
                        <button type="button" id="remove_offer_btn" class="btn btn-light-danger fw-semibold {{ ($isEdit && $d->offer_id) ? '' : 'd-none' }}">
                            <i class="ki-duotone ki-cross fs-5"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                    </div>
                    <div id="offer_feedback" class="fs-7 mt-1 {{ ($isEdit && $d->offer_id) ? 'text-success' : 'text-muted' }}">
                        @if($isEdit && $d->offer_id)
                            تم تطبيق العرض — خصم {{ number_format((float)$d->offer_discount, 2) }} ر.س
                        @endif
                    </div>
                    <input type="hidden" name="offer_id" id="offer_id_input" value="{{ $isEdit ? ($d->offer_id ?? '') : '' }}">
                    <input type="hidden" name="offer_discount" id="offer_discount_input" value="{{ $isEdit ? (float)$d->offer_discount : 0 }}">
                </div>
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
                    <tr>
                        <td class="text-gray-600">الشحن</td>
                        <td class="text-end" id="summary-shipping">0.00 ر.س</td>
                    </tr>
                    <tr id="summary-coupon-row" class="{{ ($isEdit && $d->coupon_id) ? '' : 'd-none' }}">
                        <td class="text-gray-600">خصم الكوبون</td>
                        <td class="text-end text-success" id="summary-coupon">- 0.00 ر.س</td>
                    </tr>
                    <tr id="summary-offer-row" class="{{ ($isEdit && $d->offer_id) ? '' : 'd-none' }}">
                        <td class="text-gray-600">خصم العرض</td>
                        <td class="text-end text-success" id="summary-offer">- 0.00 ر.س</td>
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
        <button type="submit" class="btn btn-dark fw-bold" id="submit-btn">
            <i class="ki-duotone ki-check fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
            {{$isEdit ? 'حفظ التغييرات' : 'إضافة الطلب'}}
        </button>
    </div>
</div>
