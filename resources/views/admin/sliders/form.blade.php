<<div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_header" data-kt-scroll-wrappers="#kt_modal_add_scroll" data-kt-scroll-offset="300px">

        @if (isset($data))
        <input type="hidden" name="id" value="{{$data->id}}" />
        @endif

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
                    @if (isset($data) && $data->getMedia('image')->count() > 0)
                    <style>.image-input-placeholder { background-image: url({{$data->getFirstMediaUrl('image', 'thumb')}}); } [data-bs-theme="dark"] .image-input-placeholder { background-image: url({{$data->getFirstMediaUrl('image', 'thumb')}}); }</style>
                    @else
                        <style>.image-input-placeholder { background-image: url({{asset('dash/assets/media/svg/files/blank-image.svg')}}); } [data-bs-theme="dark"] .image-input-placeholder { background-image: url({{asset('dash/assets/media/svg/files/blank-image-dark.svg')}}); }</style>
                    @endif
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
                            <input type="file" name="image" accept=".png, .jpg, .jpeg">
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
                <input type="text" class="form-control form-control-solid" name="title" value="{{old('title',$data->title ?? '')}}" placeholder="العنوان"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">اللينك</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="link" value="{{old('link',$data->link ?? '')}}" placeholder="اللينك"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">الترتيب</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="sort" value="{{old('sort',$data->sort ?? '')}}" placeholder="الترتيب"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="form-check form-switch form-check-solid">
            <input class="form-check-input" type="checkbox" name="status" value="active" @if(isset($data) && $data->status == 'active') checked="checked" @endif id="flexSwitchDefault"/>
            <label class="form-check-label" for="flexSwitchDefault">
                الحالة 
            </label>
        </div>
        
</div>