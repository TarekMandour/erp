{{-- Shared form fields for create --}}

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show mb-7">
    <ul class="mb-0">@foreach($errors->all() as $err)<li>{{$err}}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Row 1: Coupon + Customer --}}
<div class="row mb-7">
    <div class="col-md-6 fv-row">
        <label class="form-label required">الكوبون</label>
        <select class="form-select form-select-solid" name="coupon_id" id="coupon_id">
            <option value="">-- اختر الكوبون --</option>
            @foreach($coupons as $coupon)
                <option value="{{$coupon->id}}" {{old('coupon_id') == $coupon->id ? 'selected' : ''}}>
                    {{$coupon->code}}
                </option>
            @endforeach
        </select>
        @error('coupon_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-6 fv-row">
        <label class="form-label required">العميل</label>
        <select class="form-select form-select-solid" name="customer_id" id="customer_id">
            <option value="">-- اختر العميل --</option>
            @foreach($customers as $customer)
                <option value="{{$customer->id}}" {{old('customer_id') == $customer->id ? 'selected' : ''}}>
                    {{$customer->name}}
                </option>
            @endforeach
        </select>
        @error('customer_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>

{{-- Row 2: Order ID + Discount Amount --}}
<div class="row mb-7">
    <div class="col-md-4 fv-row">
        <label class="form-label required">رقم الطلب (Order ID)</label>
        <input type="number" min="1" class="form-control form-control-solid" name="order_id"
            value="{{old('order_id')}}" placeholder="أدخل رقم الطلب" />
        @error('order_id')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label required">مبلغ الخصم</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="discount_amount"
                value="{{old('discount_amount')}}" placeholder="0.00" />
            <span class="input-group-text">ر.س</span>
        </div>
        @error('discount_amount')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
    <div class="col-md-4 fv-row">
        <label class="form-label required">تاريخ الاستخدام</label>
        <input type="datetime-local" class="form-control form-control-solid" name="used_at"
            value="{{old('used_at', now()->format('Y-m-d\TH:i'))}}" />
        @error('used_at')<div class="text-danger mt-1">{{$message}}</div>@enderror
    </div>
</div>
