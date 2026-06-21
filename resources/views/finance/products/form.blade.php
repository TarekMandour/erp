<!--begin::Aside column-->
<div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
    <div class="card card-flush py-4">
        <!--begin::Card header-->
        <div class="card-header">
            <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" data-kt-ecommerce-settings-type="cancel" class="btn btn-light me-3">الغاء</button>
                <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
            </div>
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0">
            <label class="form-label">الاقسام</label>
            <select class="form-select form-select-solid mb-3" id="category_id" name="category_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                <option></option>
                @foreach ($filters['categories'] as $item)
                    <option value="{{ $item->id}}" @if(isset($data) && $data->category_id == $item->id) selected="selected" @endif >{{ $item->name}}</option>
                @endforeach
            </select>

            <label class="form-label">الماركات</label>
            <select class="form-select form-select-solid mb-3" id="brand_id" name="brand_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                <option></option>
                @foreach ($filters['brands'] as $item)
                    <option value="{{ $item->id}}" @if(isset($data) && $data->brand_id == $item->id) selected="selected" @endif >{{ $item->name}}</option>
                @endforeach
            </select>

            <label class="form-label">الوحدات</label>
            <select class="form-select form-select-solid mb-5" id="unit_id" name="unit_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                <option></option>
                @foreach ($filters['units'] as $item)
                    <option value="{{ $item->id}}" @if(isset($data) && $data->unit_id == $item->id) selected="selected" @endif >{{ $item->name}}</option>
                @endforeach
            </select>

            <div class="form-check form-check-custom form-check-primary form-check-solid mt-2 mb-5">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input h-20px w-20px" type="checkbox" name="is_active" value="1" @if(isset($data) && $data->is_active == 1) checked @endif />
                <label class="form-check-label text-dark fw-bold" for="">
                    حالة المنتج ؟
                </label>
            </div>

            <div class="form-check form-check-custom form-check-primary form-check-solid mb-5">
                <input type="hidden" name="has_variants" value="0">
                <input class="form-check-input h-20px w-20px" type="checkbox" name="has_variants" value="1" @if(isset($data) && $data->has_variants == 1) checked @endif />
                <label class="form-check-label text-dark fw-bold" for="">
                    يوجد خصائص للمنتج ؟
                </label>
            </div>

            <div class="form-check form-check-custom form-check-primary form-check-solid mb-5">
                <input type="hidden" name="has_expiry" value="0">
                <input class="form-check-input h-20px w-20px" type="checkbox" name="has_expiry" value="1" @if(isset($data) && $data->has_expiry == 1) checked @endif />
                <label class="form-check-label text-dark fw-bold" for="">
                    يوجد تاريخ الصلاحية ؟
                </label>
            </div>

        </div>
        <!--end::Card body-->
    </div>
    <!--begin::Thumbnail settings-->
    <div class="card card-flush py-4">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="card-title">
                <h2>الصوره الاساسية</h2>
            </div>
            <!--end::Card title-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body text-center pt-0">
            <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                <!--begin::Preview existing avatar-->
                @if (isset($data) && $data->getMedia('thumbnail')->count() > 0)
                <div class="image-input-wrapper w-100px h-100px" style="background-image: url('{{$data->getFirstMediaUrl('thumbnail')}}')"></div>
                @else
                <div class="image-input-wrapper w-100px h-100px" style="background-image: url('{{asset('dash/assets/media/svg/files/blank-image.svg')}}')"></div>
                @endif
                <!--end::Preview existing avatar-->
                <!--begin::Label-->
                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                    <i class="ki-duotone ki-pencil fs-7">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--begin::Inputs-->
                    <input type="file" name="thumbnail" accept=".png, .jpg, .jpeg" />
                    <input type="hidden" name="avatar_remove" />
                    <!--end::Inputs-->
                </label>
                <!--end::Label-->
                <!--begin::Cancel-->
                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <!--end::Cancel-->
                <!--begin::Remove-->
                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <!--end::Remove-->
            </div>
            <!--end::Image input-->

        </div>
        <!--end::Card body-->
    </div>

    <div class="card card-flush py-4">
        <!--begin::Card header-->
        <div class="card-header">
            <!--begin::Card title-->
            <div class="card-title">
                <h2>معرض الصور</h2>
            </div>
            <!--end::Card title-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body text-center pt-0">
            <div class="image-input image-input-empty image-input-outline image-input-placeholder mb-3" data-kt-image-input="true">
                <div class="image-input-wrapper w-100px h-100px mb-5" style="background-image: url('{{asset('dash/assets/media/svg/files/blank-image.svg')}}')"></div>
                <!--begin::Label-->
                <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                    <i class="ki-duotone ki-pencil fs-7">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <!--begin::Inputs-->
                    <input type="file" name="gallery[]" accept=".png, .jpg, .jpeg" multiple/>
                    <input type="hidden" name="avatar_remove" />
                    <!--end::Inputs-->
                </label>
                <!--end::Label-->
                <!--begin::Cancel-->
                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <!--end::Cancel-->
                <!--begin::Remove-->
                <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </span>
                <!--end::Remove-->

                <!--begin::Preview existing avatar-->
                @if (isset($data) && $data->getMedia('gallery')->count() > 0)
                    @foreach ($data->getMedia('gallery') as $item)
                        
                        <div class="symbol symbol-50px">{{ $item }}</div>
                    @endforeach
                @endif
                <!--end::Preview existing avatar-->
            </div>
            <!--end::Image input-->

        </div>
        <!--end::Card body-->
    </div>

</div>
<!--end::Aside column-->
<!--begin::Main column-->
<div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
    <div class="card card-flush py-4">
        <!--begin::Card header-->
        <div class="card-header">
            <div class="card-title">
                <h2>معلومات عن المنتج</h2>
            </div>
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0">
            <div class="mb-10 fv-row">
                <!--begin::Label-->
                <label class="required form-label">العنوان</label>
                <!--end::Label-->
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="name" value="{{old('name',$data->name ?? '')}}" placeholder="العنوان"  />
                <!--end::Input-->
            </div>
            <div class="mb-10 fv-row">
                <!--begin::Label-->
                <label class="form-label">سعرات حرارية</label>
                <!--end::Label-->
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="calories" value="{{old('calories',$data->calories ?? '')}}" placeholder="سعرات حرارية"  />
                <!--end::Input-->
            </div>
            <div>
                <!--begin::Label-->
                <label class="form-label">المحتوى</label>
                <!--end::Label-->
                <!--begin::Editor-->
                <textarea name="description" class="form-control form-control-solid" id="">
                    {{old('description',$data->description ?? '')}}
                </textarea>
                <!--end::Editor-->
            </div>
            <!--end::Input group-->
        </div>
        <!--end::Card header-->
    </div>

    <div class="card card-flush py-4">
        <!--begin::Card header-->
        <div class="card-header">
            <div class="card-title">
                <h2>خصائص المنتج</h2>
            </div>
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-0">

            <div class="row mb-10">
                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">تكلفة الشراء للوحده</label>
                    <input type="number" class="form-control form-control-solid" name="purchase_price" value="{{old('purchase_price',$data->purchase_price ?? 0)}}" step="0.01" min="0" placeholder="تكلفة شراء الوحده"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">سعر بيع الوحده</label>
                    <input type="number" class="form-control form-control-solid" name="selling_price" value="{{old('selling_price',$data->selling_price ?? 0)}}" step="0.01" min="0" placeholder="سعر بيع الوحدة"  />
                    @if(isset($pricingMode) && $pricingMode === 'inclusive')
                        <div class="form-text text-warning fw-semibold mt-1">
                            <i class="ki-duotone ki-information-5 fs-6"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            السعر المدخل شامل الضريبة
                        </div>
                    @else
                        <div class="form-text text-info fw-semibold mt-1">
                            <i class="ki-duotone ki-information-5 fs-6"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            السعر المدخل غير شامل الضريبة وسيتم إضافة الضريبة عند البيع
                        </div>
                    @endif
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">الضريبه %</label>
                    <input type="number" class="form-control form-control-solid" name="tax_rate"
                           value="{{old('tax_rate', $data->tax_rate ?? \App\Helpers\Helper::defaultTaxRate())}}"
                           step="0.01" min="0" placeholder="الضريبه %"  />
                    <div class="form-text text-muted mt-1">
                        القيمة الافتراضية للنظام: {{ \App\Helpers\Helper::defaultTaxRate() }}%
                    </div>
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label"> الحد الادنى للتنبيه للكمية</label>
                    <input type="number" class="form-control form-control-solid" name="alert_quantity" value="{{old('alert_quantity',$data->alert_quantity ?? 1)}}" placeholder="الحد الادنى للتنبيه للكمية"  />
                </div>
            </div>

            <div class="row mb-10">
                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">الترتيب</label>
                    <input type="number" class="form-control form-control-solid" name="sort" value="{{old('sort',$data->sort ?? 0)}}" placeholder="الترتيب"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-control form-control-solid mb-3" id="sku" name="sku" value="{{old('sku',$data->sku ?? '')}}" placeholder="SKU"  />
                    <span class=""><a href="javascript:;" id="generateSku" class="fs-6 text-muted"><i class="bi bi-magic"></i> Click generate Sku</a></span>
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control form-control-solid" name="barcode" value="{{old('barcode',$data->barcode ?? '')}}" placeholder="Barcode"  />
                </div>


            </div>
            

        </div>
        <!--end::Card header-->
    </div>


</div>


<script src="{{ URL::asset('dash/assets/plugins/custom/tinymce/tinymce.bundle.js')}}"></script>



@section('script')
<script>
    var options = {selector: "#kt_docs_tinymce_basic"};

    tinymce.init(options);

    $(document).ready(function() {
        // Generate SKU
        $('#generateSku').click(function() {
            $.ajax({
                url: '{{ route("finance.products.generate-sku", 1) }}',
                method: 'GET',
                data: {
                    category_id: $('#category_id').val(),
                    brand_id: $('#brand_id').val(),
                    unit_id: $('#unit_id').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#sku').val(response.sku);
                    }
                }
            });
        });
    });

</script>
@endsection
