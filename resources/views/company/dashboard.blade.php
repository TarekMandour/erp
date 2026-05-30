@extends('company.layout.master')

@section('css')

@endsection

@section('style')
    
@endsection

@section('breadcrumb')

@endsection

@section('content')

<div class="content flex-column-fluid" id="kt_content">
    <!--begin::About card-->
    <div class="card" style="height: 70vh;">
        <!--begin::Body-->
        <div class="card-body p-lg-17 text-center">
            <a href="{{url('/dashboard')}}">
                @if (Helper::settings()->getMedia('logo')->count())
                <img alt="{{Helper::settings()->append_name}}" src="{{Helper::settings()->getFirstMediaUrl('logo')}}" class=" h-lg-100px" />
                @else
                {{Helper::settings()->append_name}}
                @endif
            </a>
        </div>
        <!--end::Body-->
    </div>
    <!--end::About card-->
</div>


@endsection

@section('script')
<script src="{{asset('dash/assets/plugins/global/plugins.bundle.js')}}"></script>

@endsection