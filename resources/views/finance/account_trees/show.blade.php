@extends('admin.layout.master')

@php
    $route = 'finance.account_trees';
@endphp

@section('css')
<link href="{{asset('dash/assets/plugins/custom/datatables/datatables.bundle.rtl.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل الحساب</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">شجرة الحسابات</a>
            </li>
            <li class="breadcrumb-item text-gray-600">{{$data->name}}</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2 py-md-1">
        <a href="{{route($route.'.edit', $data->id)}}" class="btn btn-primary me-3">
            <i class="bi bi-pencil-square fs-4 me-1"></i> تعديل
        </a>
        <a href="{{route('finance.trans_account_trees.create')}}?account_id={{$data->id}}" class="btn btn-success me-3">
            <i class="bi bi-plus-circle fs-4 me-1"></i> قيد جديد
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

        {{-- Account Info --}}
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">بيانات الحساب</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">كود الحساب</span>
                        <span class="fw-bold fs-6">{{$data->code}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الاسم</span>
                        <span class="fw-bold fs-6">{{$data->name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">النوع</span>
                        @php
                            $typeColors = ['asset'=>'primary','liability'=>'danger','equity'=>'warning','revenue'=>'success','expense'=>'info'];
                        @endphp
                        <span class="badge bg-light-{{$typeColors[$data->type] ?? 'secondary'}}">{{$data->type_name}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الحساب الأب</span>
                        <span class="fw-bold fs-6">{{$data->parent ? $data->parent->name : '—'}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المستوى</span>
                        <span class="fw-bold fs-6">{{$data->level}}</span>
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

        {{-- Balance Summary --}}
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">ملخص الأرصدة</span>
                    </h3>
                </div>
                <div class="card-body pt-5">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="border border-dashed border-gray-300 rounded p-4 text-center">
                                <div class="fs-2 fw-bold text-danger">{{number_format($data->total_debit, 2)}}</div>
                                <div class="text-gray-600 fs-6 mt-1">إجمالي المدين</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-gray-300 rounded p-4 text-center">
                                <div class="fs-2 fw-bold text-success">{{number_format($data->total_credit, 2)}}</div>
                                <div class="text-gray-600 fs-6 mt-1">إجمالي الدائن</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-{{$data->balance >= 0 ? 'success' : 'danger'}} rounded p-4 text-center">
                                <div class="fs-2 fw-bold text-{{$data->balance >= 0 ? 'success' : 'danger'}}">{{number_format($data->balance, 2)}}</div>
                                <div class="text-gray-600 fs-6 mt-1">الرصيد</div>
                            </div>
                        </div>
                    </div>

                    @if($data->children->count() > 0)
                    <div class="mt-7">
                        <h5 class="fw-bold text-gray-900 mb-4">الحسابات الفرعية</h5>
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle fs-6">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th>الكود</th>
                                        <th>الاسم</th>
                                        <th>الرصيد</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data->children as $child)
                                    <tr>
                                        <td class="text-muted">{{$child->code}}</td>
                                        <td class="fw-semibold">{{$child->name}}</td>
                                        <td class="fw-bold text-{{$child->balance >= 0 ? 'success' : 'danger'}}">{{number_format($child->balance, 2)}}</td>
                                        <td>
                                            <a href="{{route($route.'.show', $child->id)}}" class="btn btn-xs btn-icon btn-info"><i class="bi bi-eye fs-5"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Recent Transactions --}}
    <div class="card">
        <div class="card-header pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold text-gray-900">آخر القيود</span>
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle fs-6">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                            <th>#</th>
                            <th>رقم القيد</th>
                            <th>المدين</th>
                            <th>الدائن</th>
                            <th>الوصف</th>
                            <th>التاريخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data->transactions()->with('entry')->orderByDesc('id')->take(20)->get() as $i => $trans)
                        <tr>
                            <td>{{$i + 1}}</td>
                            <td class="fw-semibold text-primary">
                                @if($trans->entry)
                                    <a href="{{route('finance.journal_entries.show', $trans->journal_entry_id)}}">
                                        {{$trans->entry->entry_number}}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="fw-bold text-danger">{{$trans->debit > 0 ? number_format($trans->debit, 2) : '—'}}</td>
                            <td class="fw-bold text-success">{{$trans->credit > 0 ? number_format($trans->credit, 2) : '—'}}</td>
                            <td class="text-muted">{{$trans->description ?? ($trans->entry?->description ?? '—')}}</td>
                            <td class="text-muted">{{$trans->entry?->date?->format('Y-m-d') ?? '—'}}</td>
                            <td>
                                @if($trans->entry)
                                <a href="{{route('finance.journal_entries.show', $trans->journal_entry_id)}}" class="btn btn-xs btn-icon btn-info"><i class="bi bi-eye fs-5"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">لا توجد قيود</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
