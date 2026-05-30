<div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_scroll" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#kt_modal_add_header" data-kt-scroll-wrappers="#kt_modal_add_scroll" data-kt-scroll-offset="300px">

        @if (isset($data))
        <input type="hidden" name="id" value="{{$data->id}}" />
        @endif

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="required">صورة</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <div class="mt-1">

                    <!--begin::Image input-->
                    <div class="image-input image-input-outline image-input-placeholder image-input-empty image-input-empty" data-kt-image-input="true">
                        <!--begin::Preview existing avatar-->
                        @if (isset($data) && $data->getMedia('image')->count() > 0)
                        <div class="image-input-wrapper w-100px h-100px" style="background-image: url('{{$data->getFirstMediaUrl('image')}}')"></div>
                        @else
                        <div class="image-input-wrapper w-100px h-100px" style="background-image: url('{{asset('dash/assets/media/svg/files/blank-image.svg')}}')"></div>
                        @endif
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
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="name" value="{{old('name',$data->name ?? '')}}" placeholder="العنوان"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">المعرف</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <!--begin::Input-->
                <input type="text" class="form-control form-control-solid" name="code" value="{{old('code',$data->code ?? '')}}" placeholder="المعرف"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="row fv-row mb-7">
            <div class="col-md-3 text-md-end">
                <!--begin::Label-->
                <label class="fs-6 fw-semibold form-label mt-3">
                    <span class="">متفرع من</span>
                </label>
                <!--end::Label-->
            </div>
            <div class="col-md-9">
                <select class="form-select form-select-solid" name="parent_id" data-kt-select2="true" data-close-on-select="true" data-placeholder="اختر ..." data-allow-clear="false">
                    <option></option>
                    @foreach (\App\Models\Finance\Category::all() as $item)
                        <option value="{{ $item->id}}" @if(isset($data) && $data->parent_id == $item->id) selected="selected" @endif >{{ $item->fullPath}}</option>
                    @endforeach
                </select>
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
                <input type="number" class="form-control form-control-solid" name="sort" value="{{old('sort',$data->sort ?? 0)}}" min="0" placeholder="الترتيب"  />
                <!--end::Input-->
            </div>
        </div>

        <div class="form-check form-switch form-check-solid">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @if(isset($data) && $data->is_active == 1) checked="checked" @endif id="flexSwitchDefault"/>
            <label class="form-check-label" for="flexSwitchDefault">
                حالة القسم
            </label>
        </div>
        
</div>