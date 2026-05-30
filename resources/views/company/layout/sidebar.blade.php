<div id="kt_aside" class="aside card" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_toggle">
    <!--begin::Aside menu-->
    <div class="aside-menu flex-column-fluid px-4">
        <!--begin::Aside Menu-->
        <div class="hover-scroll-overlay-y mh-100 my-5" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="{default: '#kt_aside_footer', lg: '#kt_header, #kt_aside_footer'}" data-kt-scroll-wrappers="#kt_aside, #kt_aside_menu" data-kt-scroll-offset="{default: '5px', lg: '75px'}">
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-5" id="#kt_aside_menu" data-kt-menu="true">
                <!--begin:Menu item-->

                <div class="menu-item pt-5">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}" href="{{route('company.dashboard')}}">
                        <span class="menu-icon">
                            <i class="bi bi-grid"></i>
                        </span>
                        <span class="menu-title">الرئيسية</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة الـ whatsapp</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة الاعدادات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                     
                    <a class="menu-link {{ request()->routeIs('company.companys.edit.company') ? 'active' : '' }}" href="{{route('company.companys.edit.company', Auth::guard('company')->user()->company_id)}}">
                        <span class="menu-icon">
                            <i class="bi bi-person-bounding-box"></i>
                        </span>
                        <span class="menu-title">بيانات الشركة</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                     
                    <a class="menu-link {{ request()->routeIs('company.companys.index') ? 'active' : '' }}" href="{{route('company.companys.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-person-bounding-box"></i>
                        </span>
                        <span class="menu-title">الموظفين</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

            </div>
            <!--end::Menu-->
        </div>
    </div>
    <!--end::Aside menu-->
    <!--begin::Footer-->

    <!--end::Footer-->
</div>