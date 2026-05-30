<div class="row">
    <div class="col-lg-12">

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">حالة الرسالة</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <select class="form-select mb-2" data-control="select2" name="status" data-hide-search="true" data-placeholder="اختر ..." id="kt_ecommerce_add_category_store_template">
                    <option value="read" @if(isset($data) && $data->status == "read") selected @endif>Read </option>
                    <option value="unread" @if(isset($data) && $data->status == "unread") selected @endif>Unread </option>
                </select>
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="required">الاسم</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="name" value="{{old('name',$data->name ?? '')}}" placeholder="الاسم"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="required">رقم الجوال</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="phone" value="{{old('phone',$data->phone ?? '')}}" placeholder="رقم الجوال"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="required">البريد الالكتروني</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="email" class="form-control form-control-solid" name="email" value="{{old('email',$data->email ?? '')}}" placeholder="البريد الالكتروني"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">المحتوى</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <textarea name="content" id="kt_docs_tinymce_basic">
                    {{old('content',$data->content ?? '')}}
                </textarea>
            </div>
        </div>
        
    </div>
</div>

<script src="{{ URL::asset('dash/assets/plugins/custom/tinymce/tinymce.bundle.js')}}"></script>

<script>
    var options = {selector: "#kt_docs_tinymce_basic"};

    tinymce.init(options);

</script>
