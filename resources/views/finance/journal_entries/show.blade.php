@extends('admin.layout.master')

@php
    $route = 'finance.journal_entries';
@endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">تفاصيل القيد المحاسبي</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">
                <a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">القيود المحاسبية</a>
            </li>
            <li class="breadcrumb-item text-gray-600">{{$data->entry_number}}</li>
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

        {{-- Header card --}}
        <div class="col-xl-5">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">بيانات القيد</span>
                    </h3>
                </div>
                <div class="card-body pt-5">

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">رقم القيد</span>
                        <span class="fw-bold fs-5 text-primary">{{$data->entry_number}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">النوع</span>
                        @php
                            $typeColors = ['sales'=>'success','purchase'=>'primary','receipt'=>'info','payment'=>'warning','expense'=>'danger','transfer'=>'dark','opening'=>'secondary','adjustment'=>'light'];
                        @endphp
                        <span class="badge bg-light-{{$typeColors[$data->entry_type] ?? 'secondary'}}">{{$data->entry_type_label}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">التاريخ</span>
                        <span class="fw-bold fs-6">{{$data->date->format('Y-m-d')}}</span>
                    </div>
                    <div class="separator separator-dashed mb-4"></div>

                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">الحالة</span>
                        @php
                            $statusColors = ['draft'=>'warning','posted'=>'success','canceled'=>'danger'];
                        @endphp
                        <span class="badge bg-light-{{$statusColors[$data->status] ?? 'secondary'}}">{{$data->status_label}}</span>
                    </div>

                    @if($data->reference_type)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">المرجع</span>
                        <span class="fw-bold fs-6">{{$data->reference_type}} {{$data->reference_id ? '#'.$data->reference_id : ''}}</span>
                    </div>
                    @endif

                    @if($data->description)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="mb-4">
                        <span class="text-gray-600 fw-semibold fs-6 d-block mb-2">الوصف</span>
                        <p class="text-gray-800">{{$data->description}}</p>
                    </div>
                    @endif

                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">أُضيف بواسطة</span>
                        <span class="fw-bold fs-6">{{$data->createdBy?->name ?? '—'}}</span>
                    </div>

                    @if($data->approved_by)
                    <div class="separator separator-dashed mb-4"></div>
                    <div class="d-flex flex-stack mb-4">
                        <span class="text-gray-600 fw-semibold fs-6">اعتمد بواسطة</span>
                        <span class="fw-bold fs-6">{{$data->approvedBy?->name}} — {{$data->approved_at?->format('Y-m-d H:i')}}</span>
                    </div>
                    @endif

                </div>
            </div>
        </div>

        {{-- Totals --}}
        <div class="col-xl-7">
            <div class="card card-flush mb-5">
                <div class="card-body py-5">
                    <div class="row g-4">
                        @php
                            $totalDebit  = $data->items->sum('debit');
                            $totalCredit = $data->items->sum('credit');
                            $balanced    = abs($totalDebit - $totalCredit) < 0.01;
                        @endphp
                        <div class="col-md-4">
                            <div class="border border-dashed border-danger rounded p-4 text-center">
                                <div class="fs-2 fw-bold text-danger">{{number_format($totalDebit, 2)}}</div>
                                <div class="text-gray-600 fs-6 mt-1">إجمالي المدين</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-success rounded p-4 text-center">
                                <div class="fs-2 fw-bold text-success">{{number_format($totalCredit, 2)}}</div>
                                <div class="text-gray-600 fs-6 mt-1">إجمالي الدائن</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-dashed border-{{$balanced ? 'success' : 'danger'}} rounded p-4 text-center">
                                <div class="fs-4 fw-bold text-{{$balanced ? 'success' : 'danger'}}">
                                    {{$balanced ? 'متوازن' : 'غير متوازن'}}
                                </div>
                                @if(!$balanced)
                                <div class="text-danger fs-6 mt-1">الفارق: {{number_format(abs($totalDebit - $totalCredit), 2)}}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items table --}}
            <div class="card card-flush">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-900">أسطر القيد</span>
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-6">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                    <th>#</th>
                                    <th>الحساب</th>
                                    <th>مدين</th>
                                    <th>دائن</th>
                                    <th>مركز التكلفة</th>
                                    <th>الوصف</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data->items as $i => $item)
                                <tr>
                                    <td class="text-muted">{{$i + 1}}</td>
                                    <td>
                                        @if($item->account)
                                            <a href="{{route('finance.account_trees.show', $item->account_tree_id)}}" class="fw-semibold text-gray-800 text-hover-primary">
                                                {{$item->account->code}} — {{$item->account->name}}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="fw-bold text-danger">{{$item->debit > 0 ? number_format($item->debit, 2) : '—'}}</td>
                                    <td class="fw-bold text-success">{{$item->credit > 0 ? number_format($item->credit, 2) : '—'}}</td>
                                    <td class="text-muted">{{$item->costCenter?->name ?? '—'}}</td>
                                    <td class="text-muted">{{$item->description ?? '—'}}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">لا توجد أسطر</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
