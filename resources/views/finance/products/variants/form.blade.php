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

            <label class="form-label">SKU</label>
            <input type="text" class="form-control form-control-solid mb-3" id="sku" name="sku" value="{{old('sku',$data->sku ?? '')}}" placeholder="SKU"  />
            <span class="mb-5"><a href="javascript:;" id="generateSku" class="fs-6 text-muted"><i class="bi bi-magic"></i> Click generate Sku</a></span>

            <br><br>
            <label class="form-label">Barcode</label>
            <input type="text" class="form-control form-control-solid mb-5" name="barcode" value="{{old('barcode',$data->barcode ?? '')}}" placeholder="barcode"  />

            <div class="form-check form-check-custom form-check-primary form-check-solid mt-2 mb-5">
                <input class="form-check-input h-20px w-20px" type="checkbox" name="is_active" value="1" @if(isset($data) && $data->is_active == 1) checked @endif />
                <label class="form-check-label text-dark fw-bold" for="">
                    حالة المنتج ؟
                </label>
            </div>

            <div class="form-check form-check-custom form-check-primary form-check-solid mb-5">
                <input class="form-check-input h-20px w-20px" type="checkbox" name="is_default" value="1" @if(isset($data) && $data->is_default == 1) checked @endif />
                <label class="form-check-label text-dark fw-bold" for="">
                    هل النوع افتراضي ؟
                </label>
            </div>

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
            
            <div id="kt_docs_repeater_basic">
                <!--begin::Form group-->
                <div class="form-group">
                    <div data-repeater-list="kt_docs_repeater_basic">
                        @if(isset($data) && count($data->attributes) > 0)

                        @foreach ($data->attributes as $key => $pro_attribute)
                        
                        <div data-repeater-item>
                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="form-label">الخصائص:</label>
                                    <select class="form-select  mb-2" data-control="select2" name="attri_keys" data-hide-search="true" data-placeholder="اختر ...">
                                        <option></option>
                                        @foreach ($filters['attributes'] as $attribute)
                                            <option value="{{ $attribute->id}}" @if($key == $attribute->id) selected @endif>{{ $attribute->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">القيمة:</label>
                                    <input type="text" name="attri_values" value="{{$pro_attribute}}" class="form-control mb-2 mb-md-0" placeholder="القيمة" />
                                </div>
                                <div class="col-md-1">
                                    <a href="javascript:;" data-repeater-delete class="btn btn-icon btn-light-danger mt-3 mt-md-8">
                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>        
                                    </a>
                                </div>
                            </div>  
                        </div>
                        @endforeach
                        @endif
                        <div data-repeater-item>
                            
                            <div class="form-group row">
                                <div class="col-md-4">
                                    <label class="form-label">الخصائص:</label>
                                    <select class="form-select  mb-2" data-control="select2" name="attri_keys" data-hide-search="true" data-placeholder="اختر ...">
                                        <option></option>
                                        @foreach ($filters['attributes'] as $attribute)
                                            <option value="{{ $attribute->id}}">{{ $attribute->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">القيمة:</label>
                                    <input type="text" name="attri_values" value="" class="form-control mb-2 mb-md-0" placeholder="القيمة" />
                                </div>
                                <div class="col-md-1">
                                    <a href="javascript:;" data-repeater-delete class="btn btn-icon btn-light-danger mt-3 mt-md-8">
                                        <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>        
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Form group-->

                <!--begin::Form group-->
                <div class="form-group mt-5">
                    <a href="javascript:;" data-repeater-create class="btn btn-light-primary">
                        <i class="ki-duotone ki-plus fs-3"></i>
                        اضف المزيد
                    </a>
                </div>
                <!--end::Form group-->
            </div>

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
                    <label class="form-label">سعر بيع الوحده
                        <span class="ms-1" data-bs-toggle="tooltip" title="سعر بيع الوحده">
                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                    </label>
                    <input type="number" class="form-control form-control-solid" name="selling_price" value="{{old('selling_price',$data->selling_price ?? 0)}}" step="0.01" min="0" placeholder="سعر بيع الوحدة"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">تكلفة الشراء للوحده
                        <span class="ms-1" data-bs-toggle="tooltip" title="تكلفة شراء الوحده">
                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                    </label>
                    <input type="number" class="form-control form-control-solid" name="purchase_price" value="{{old('purchase_price',$data->purchase_price ?? 0)}}" step="0.01" min="0" placeholder="تكلفة شراء الوحده"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">التكلفة الفعلية للشراء
                        <span class="ms-1" data-bs-toggle="tooltip" title="تكلفة شراء الوحده بعد خصم الضريبه">
                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </span>
                    </label>
                    <input type="number" class="form-control form-control-solid" name="cost_price" value="{{old('cost_price',$data->cost_price ?? 0)}}" step="0.01" min="0" placeholder="التكلفة الفعلية للشراء"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label"> متوسط تكلفة الشراء
                        <span class="ms-1" data-bs-toggle="tooltip" title="متوسط تكلفة الشراء">
                            <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                    </label>
                    <input type="number" class="form-control form-control-solid" name="average_cost" value="{{old('average_cost',$data->average_cost ?? 0)}}" step="0.01" min="0" placeholder="متوسط تكلفة الشراء"  />
                </div>
            </div>

            <div class="row mb-10">
                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label"> الحد الادنى للتنبيه للكمية</label>
                    <input type="number" class="form-control form-control-solid" name="alert_quantity" value="{{old('alert_quantity',$data->alert_quantity ?? 1)}}" placeholder="الحد الادنى للتنبيه للكمية"  />
                </div>

                <div class="col-md-3 fv-row fv-plugins-icon-container">
                    <label class="form-label">الترتيب</label>
                    <input type="number" class="form-control form-control-solid" name="sort_order" value="{{old('sort_order',$data->sort_order ?? 0)}}" placeholder="الترتيب"  />
                </div>
            </div>
            

        </div>
        <!--end::Card header-->
    </div>


</div>


<script src="{{ URL::asset('dash/assets/plugins/custom/tinymce/tinymce.bundle.js')}}"></script>
<script>
    var options = {selector: "#kt_docs_tinymce_basic"};

    tinymce.init(options);

</script>

