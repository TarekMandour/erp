@extends('company.layout.master')

@php
    $route = 'company.companys';
    $viewPath = 'company.companys';
@endphp

@section('css')
@endsection

@section('style')
    
@endsection

@section('breadcrumb')
<!--begin::Toolbar-->
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <!--begin::Page title-->
    <div class="page-title d-flex flex-column me-3">
        <!--begin::Title-->
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">الشركات</h1>
        <!--end::Title-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('company.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-gray-600">تعديل البيانات </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
    <!--end::Page title-->
</div>
<!--end::Toolbar-->
@endsection

@section('content')

    <div class="card">
        <!--begin::Card body-->
        <div class="card-body p-lg-10">
            <div class="col-lg-8 col-xl-8">
                <!--begin::Form-->
                <form action="{{route($route. '.update.company')}}" method="POST" enctype="multipart/form-data" id="kt_account_profile_details_form" class="form">
                    @csrf
                    <input type="hidden" name="id" value="{{$data->id}}" />

                    <div class="row">
                        <div class="col-lg-12">

                            <div class="row fv-row mb-7">
                                <div class="col-md-3 text-md-end">
                                    <!--begin::Label-->
                                    <label class="fs-6 fw-semibold form-label mt-3">
                                        <span>صورة البروفايل</span>
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
                                        <span class="required">الاسم بالكامل</span>
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
                                    <input type="text" class="form-control form-control-solid" name="name" value="{{old('name',$data->name ?? '')}}" placeholder="الاسم بالكامل"  />
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
                                        <span class="required">رقم الهاتف</span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <div class="col-md-9">
                                    <!--begin::Input-->
                                    <input type="text" class="form-control form-control-solid" name="phone" value="{{old('phone',$data->phone ?? '')}}" placeholder="رقم الهاتف"  />
                                    <!--end::Input-->
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
                                    <input type="text" class="form-control form-control-solid" name="address" value="{{old('address',$data->address ?? '')}}" placeholder="العنوان"  />
                                    <!--end::Input-->
                                </div>
                            </div>

                            <div class="row fv-row mb-7">
                                <div class="col-md-3 text-md-end">
                                    <!--begin::Label-->
                                    <label class="fs-6 fw-semibold form-label mt-3">
                                        <span class="">حالة الحساب</span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <div class="col-md-9">
                                    <select class="form-select mb-2" data-control="select2" name="is_active" data-hide-search="true" data-placeholder="اختر ..." id="kt_ecommerce_add_category_store_template" disabled>
                                        <option></option>
                                        <option value="1" @if(isset($data->is_active) && $data->is_active == 1) selected="selected" @endif >مفعل</option>
                                        <option value="0" @if(isset($data->is_active) && $data->is_active == 0) selected="selected" @endif>غير مفعل</option>
                                        <option value="2" @if(isset($data->is_active) && $data->is_active == 2) selected="selected" @endif>محظور</option>
                                    </select>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end py-6 px-9">
                        <button type="reset" data-kt-ecommerce-settings-type="cancel" class="btn btn-light me-3">الغاء</button>
                        <button type="submit" class="btn btn-primary" id="kt_account_profile_details_submit">حفظ</button>
                    </div>
                    <!--end::Actions-->
                </form>

            </div>
        </div>
    </div>

@endsection

@section('script')
@endsection