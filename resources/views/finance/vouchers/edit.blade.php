@extends('admin.layout.master')
@php $route = 'finance.vouchers'; $viewPath = 'finance.vouchers'; @endphp
@section('breadcrumb')
<div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
    <div class="page-title d-flex flex-column me-3">
        <h1 class="d-flex text-gray-900 fw-bold my-1 fs-3">السندات</h1>
        <ul class="breadcrumb breadcrumb-dot fw-semibold text-gray-600 fs-6 my-1">
            <li class="breadcrumb-item text-gray-600"><a href="{{route('admin.dashboard')}}" class="text-gray-600 text-hover-primary">الرئيسية</a></li>
            <li class="breadcrumb-item text-gray-600"><a href="{{route($route.'.index')}}" class="text-gray-600 text-hover-primary">السندات</a></li>
            <li class="breadcrumb-item text-gray-600">تعديل البيانات</li>
        </ul>
    </div>
</div>
@endsection
@section('content')
<form action="{{route($route.'.update')}}" method="POST" id="kt_account_profile_details_form" class="form d-flex flex-column flex-lg-row">
    @csrf
    <input type="hidden" name="id" value="{{$data->id}}" />
    @include($viewPath.'.form')
</form>
@endsection
@section('script')
<script>
$(function () {
    function togglePartySection() {
        var type = $('#party_type').val();
        $('#section-customer').hide().find('select').prop('disabled', true);
        $('#section-supplier').hide().find('select').prop('disabled', true);
        $('#section-other').hide().find('input').prop('disabled', true);
        if (type === 'customer') {
            $('#section-customer').show().find('select').prop('disabled', false);
        } else if (type === 'supplier') {
            $('#section-supplier').show().find('select').prop('disabled', false);
        } else if (type === 'other') {
            $('#section-other').show().find('input').prop('disabled', false);
        }
    }
    $('#party_type').on('change', togglePartySection);
    togglePartySection();
});
</script>
@endsection
