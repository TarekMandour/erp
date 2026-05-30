<div class="row">
    <div class="col-lg-12">

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span>صورة</span>
                    <span class="ms-1" data-bs-toggle="tooltip" title="Allowed file types: png, jpg, jpeg.">
                        <i class="ki-duotone ki-information fs-7">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <div class="mt-1">
                    <!--begin::Image placeholder-->
                    @if (isset($data) && $data->getMedia('image')->count() > 0)
                    <style>.image-input-placeholder { background-image: url({{$data->getFirstMediaUrl('image', 'thumb')}}); } [data-bs-theme="dark"] .image-input-placeholder { background-image: url({{$data->getFirstMediaUrl('image', 'thumb')}}); }</style>
                    @else
                        <style>.image-input-placeholder { background-image: url({{asset('dash/assets/media/svg/files/blank-image.svg')}}); } [data-bs-theme="dark"] .image-input-placeholder { background-image: url({{asset('dash/assets/media/svg/files/blank-image-dark.svg')}}); }</style>
                    @endif
                    
                    <!--end::Image placeholder-->
                    <!--begin::Image input-->
                    <div class="image-input image-input-outline image-input-placeholder image-input-empty image-input-empty" data-kt-image-input="true">
                        <!--begin::Preview existing avatar-->
                        <div class="image-input-wrapper w-100px h-100px" style="background-image: url('')"></div>
                        <!--end::Preview existing avatar-->
                        <!--begin::Edit-->
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" aria-label="Change avatar" data-bs-original-title="Change avatar" data-kt-initialized="1">
                            <i class="ki-duotone ki-pencil fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <!--begin::Inputs-->
                            <input type="file" name="photo[]" accept=".png, .jpg, .jpeg" multiple />
                            <input type="hidden" name="avatar_remove">
                            <!--end::Inputs-->
                        </label>
                        <!--end::Edit-->
                        <!--begin::Cancel-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" aria-label="Cancel avatar" data-bs-original-title="Cancel avatar" data-kt-initialized="1">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <!--end::Cancel-->
                        <!--begin::Remove-->
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" aria-label="Remove avatar" data-bs-original-title="Remove avatar" data-kt-initialized="1">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <!--end::Remove-->
                    </div>
                    <!--end::Image input-->
                </div>
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">ملف فيديو</span>
                    <span class="ms-1" data-bs-toggle="tooltip" title="MP4 file">
                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="file" name="video" placeholder="ملف الفيديو" value="{{old('video',$data->video ?? '')}}" accept=".mp4" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">نوع المقالة</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <select class="form-select mb-2 newstype" data-control="select2" name="type" data-hide-search="true" data-placeholder="اختر ..." id="kt_ecommerce_add_category_store_template">
                    <option value="news" @if(isset($data) && $data->type == "news") selected @endif>مقاله </option>
                    <option value="gallery" @if(isset($data) && $data->type == "gallery") selected @endif>معرض صور </option>
                    <option value="video" @if(isset($data) && $data->type == "video") selected @endif>فيديو </option>
                </select>
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="required">العنوان</span>
                    <span class="ms-1" data-bs-toggle="tooltip" title="Set the title of the store for SEO.">
                        <i class="ki-duotone ki-information-5 text-gray-500 fs-6">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="name" value="{{old('name',$data->name ?? '')}}" placeholder="العنوان"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7 show-news" @if(isset($data) && $data->type != "news") style="display: none" @endif>
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">المحتوى</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <textarea name="description" id="kt_docs_tinymce_basic">
                    {{old('description',$data->description ?? '')}}
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
