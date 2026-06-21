@extends('admin.layout.master')

@php
    $route = 'finance.cost_centers';
@endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل مركز التكلفة</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">مراكز التكلفة</a>
            </li>
            <li class="breadcrumb-item text-gray-600">{{$data->name}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary me-3">
            <i class="bi bi-pencil-square fs-4 me-1"></i> تعديل
        </a>
        <a href="{{route($route.'.index')}}" class="btn btn-light fw-bold">
            <i class="bi bi-arrow-right fs-4 me-1"></i> العودة
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">
    <div class="row g-5 g-xl-10 mb-5">

        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">بيانات مركز التكلفة</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الكود</span>
                        <span class="fw-bold fs-5 text-primary">{{$data->code}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الاسم</span>
                        <span class="fw-bold fs-6">{{$data->name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المركز الأب</span>
                        <span class="fw-bold fs-6">
                            @if($data->parent)
                                <a href="{{route($route.'.show', $data->parent_id)}}">{{$data->parent->name}}</a>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الحالة</span>
                        <span class="badge bg-light-{{$data->is_active ? 'success' : 'danger'}}">
                            {{$data->is_active ? 'نشط' : 'متوقف'}}
                        </span>
                    </div>

                </div>
            </div>
        </div>

        @if($data->children->count())
        <div class="col-xl-7">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">المراكز الفرعية</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th>الكود</th>
                                    <th>الاسم</th>
                                    <th>الحالة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data->children as $child)
                                <tr>
                                    <td class="text-muted">{{$child->code}}</td>
                                    <td class="fw-semibold">{{$child->name}}</td>
                                    <td>
                                        <span class="badge bg-light-{{$child->is_active ? 'success' : 'danger'}}">
                                            {{$child->is_active ? 'نشط' : 'متوقف'}}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{route($route.'.show', $child->id)}}" class="btn btn-xs btn-icon btn-info"><i class="bi bi-eye fs-5"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
