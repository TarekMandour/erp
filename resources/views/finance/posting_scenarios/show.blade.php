@extends('admin.layout.master')

@php $route = 'finance.posting_scenarios'; @endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">سيناريو: {{ $data->code }}</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">سيناريوهات الترحيل</a></li>
            <li class="breadcrumb-item text-gray-600">عرض</li>
        </ul>
    </div>
    <div class="d-flex align-items-center py-2">
        <a href="{{ route($route.'.edit', $data->id) }}" class="btn btn-primary btn-sm me-2">
            <i class="bi bi-pencil-square"></i> تعديل
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-5">
            <i class="bi bi-check-circle-fill fs-3 me-3"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="row g-5">

        {{-- Scenario Header Card --}}
        <div class="col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">بيانات السيناريو</span>
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-row-bordered gy-3">
                        <tr><th class="w-150px">الكود</th><td><span class="badge bg-light-dark fs-7">{{ $data->code }}</span></td></tr>
                        <tr><th>الاسم</th><td>{{ $data->name }}</td></tr>
                        <tr><th>نوع العملية</th><td>
                            @php $colors = ['sales'=>'success','purchase'=>'primary','inventory'=>'info','expense'=>'danger','receipt'=>'warning','payment'=>'dark','transfer'=>'secondary']; @endphp
                            <span class="badge bg-light-{{ $colors[$data->operation_type] ?? 'secondary' }}">{{ $data->operation_type_label }}</span>
                        </td></tr>
                        <tr><th>الأولوية</th><td>{{ $data->priority }}</td></tr>
                        <tr><th>الحالة</th><td>
                            @if($data->is_active)
                                <span class="badge bg-light-success">نشط</span>
                            @else
                                <span class="badge bg-light-danger">متوقف</span>
                            @endif
                        </td></tr>
                        @if($data->description)
                        <tr><th>الوصف</th><td class="text-muted">{{ $data->description }}</td></tr>
                        @endif
                        <tr><th>أنشئ بواسطة</th><td>{{ $data->createdBy?->name ?? '—' }}</td></tr>
                        <tr><th>تاريخ الإنشاء</th><td>{{ $data->created_at?->format('Y-m-d') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Rules Card --}}
        <div class="col-xl-8">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <h3 class="card-title">قواعد الترحيل <span class="badge bg-light-primary ms-2">{{ $data->rules->count() }}</span></h3>
                </div>
                <div class="card-body pt-0">
                    @if($data->rules->isEmpty())
                        <p class="text-muted">لا توجد قواعد بعد.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-sm fs-7 align-middle">
                                <thead class="table-light">
                                    <tr class="fw-bold text-gray-600 text-center">
                                        <th>#</th>
                                        <th>المجموعة</th>
                                        <th>حساب مدين</th>
                                        <th>حساب دائن</th>
                                        <th>نوع المبلغ</th>
                                        <th>القيمة</th>
                                        <th>مركز التكلفة</th>
                                        <th>إلزامي</th>
                                        <th>المتغيرات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data->rules as $rule)
                                    <tr>
                                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                        <td class="text-center"><span class="badge bg-light-dark">{{ $rule->rule_group }}</span></td>
                                        <td class="text-muted">{{ $rule->debitAccount ? $rule->debitAccount->code . ' - ' . $rule->debitAccount->name : '—' }}</td>
                                        <td class="text-muted">{{ $rule->creditAccount ? $rule->creditAccount->code . ' - ' . $rule->creditAccount->name : '—' }}</td>
                                        <td><span class="badge bg-light-info">{{ $rule->amount_type_label }}</span></td>
                                        <td class="text-center">
                                            @if($rule->amount_value !== null) {{ number_format($rule->amount_value, 2) }}
                                            @elseif($rule->amount_field) <em>{{ $rule->amount_field }}</em>
                                            @else —
                                            @endif
                                        </td>
                                        <td class="text-muted">
                                            @if($rule->cost_center_source === 'fixed')
                                                {{ $rule->fixedCostCenter?->name ?? '—' }}
                                            @elseif($rule->cost_center_source)
                                                <em>{{ $rule->cost_center_source }}</em>
                                            @else —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($rule->is_required)
                                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                                            @else
                                                <span class="text-muted"><i class="bi bi-x-circle"></i></span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($rule->variables->count() > 0)
                                                <a href="{{ route('finance.posting_rule_variables.index', ['rule_id' => $rule->id]) }}"
                                                   class="badge bg-light-primary">{{ $rule->variables->count() }} متغير</a>
                                            @else
                                                <a href="{{ route('finance.posting_rule_variables.create', ['rule_id' => $rule->id]) }}"
                                                   class="btn btn-xs btn-light-success"><i class="bi bi-plus fs-6"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
