<!DOCTYPE html>
<html @if (App::getLocale() == 'ar') lang="ar" direction="rtl" dir="rtl" style="direction: rtl;" @else lang="en" @endif>
	<!--begin::Head-->
	<head><base href="{{url('/admin')}}"/>
		<title>{{Helper::settings()->append_name}}</title>
		<meta charset="utf-8" />
		<meta name="description" content="{{Helper::settings()->append_description}}" />
		<meta name="keywords" content="{{Helper::settings()->append_keywords}}" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<link rel="shortcut icon" href="{{Helper::settings()->getFirstMediaUrl('fav')}}" />
		
        @include('admin.layout.head')
        
	</head> 
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed">
		<div class="d-flex flex-column flex-root">
			<!--begin::Page-->
			<div class="page d-flex flex-row flex-column-fluid">
				<!--begin::Wrapper-->
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
					<!--begin::Header-->
					@include('admin.layout.header')

					<div class="d-flex flex-column-fluid">
						<!--begin::Aside-->
						@include('admin.layout.sidebar')

						<!--begin::Container-->
						<div class="d-flex flex-column flex-column-fluid container-fluid">

							@yield('breadcrumb')

							@yield('content')

							@include('admin.layout.footer')
						</div>
					</div>
				</div>
			</div>
		</div>
        
		@include('admin.layout.footer-script')
	</body>
	<!--end::Body-->
</html>