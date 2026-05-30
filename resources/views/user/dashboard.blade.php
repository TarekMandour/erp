@extends('admin.layout.master')

@section('css')

@endsection

@section('style')
    
@endsection

@section('breadcrumb')

@endsection

@section('content')

<div class="content flex-column-fluid" id="kt_content">
    <!--begin::About card-->
    <div class="card">
        <!--begin::Body-->
        <div class="card-body p-lg-17">
            <form action="{{route('logout')}}" method="POST">
            @csrf
            <input type="submit" name="submit" value="login" />
        </form>
        </div>
        <!--end::Body-->
    </div>
    <!--end::About card-->
</div>


@endsection

@section('script')
<script src="{{asset('dash/assets/plugins/global/plugins.bundle.js')}}"></script>

@endsection