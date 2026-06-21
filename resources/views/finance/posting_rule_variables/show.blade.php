@extends('admin.layout.master')

@php $route = 'finance.posting_rule_variables'; @endphp

@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">متغير: {{ $data->variable_name }}</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">المتغيرات</a></li>
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
    <div class="card card-flush mw-xl-600px">
        <div class="card-header pt-5">
            <h3 class="card-title">تفاصيل المتغير</h3>
        </div>
        <div class="card-body">
            <table class="table table-row-bordered gy-4">
                <tr>
                    <th class="w-180px">السيناريو</th>
                    <td>
                        @if($data->rule?->scenario)
                            <a href="{{ route('finance.posting_scenarios.show', $data->rule->scenario->id) }}" class="fw-bold">
                                {{ $data->rule->scenario->code }}
                            </a> — {{ $data->rule->scenario->name }}
                        @else —
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>القاعدة (مجموعة)</th>
                    <td>مجموعة {{ $data->rule?->rule_group ?? '—' }}</td>
                </tr>
                <tr>
                    <th>حساب مدين</th>
                    <td>{{ $data->rule?->debitAccount ? $data->rule->debitAccount->code . ' - ' . $data->rule->debitAccount->name : '—' }}</td>
                </tr>
                <tr>
                    <th>حساب دائن</th>
                    <td>{{ $data->rule?->creditAccount ? $data->rule->creditAccount->code . ' - ' . $data->rule->creditAccount->name : '—' }}</td>
                </tr>
                <tr>
                    <th>اسم المتغير</th>
                    <td><code>{{ $data->variable_name }}</code></td>
                </tr>
                <tr>
                    <th>نوع المصدر</th>
                    <td>
                        @php $colors = ['field'=>'primary','function'=>'info','subquery'=>'warning']; @endphp
                        <span class="badge bg-light-{{ $colors[$data->source_type] ?? 'secondary' }}">{{ $data->source_type_label }}</span>
                    </td>
                </tr>
                <tr>
                    <th>قيمة المصدر</th>
                    <td><code>{{ $data->source_value }}</code></td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection
