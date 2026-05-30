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
                    <a class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{route('admin.dashboard')}}">
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
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة المستخدمين</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.customers.*') ? 'active' : '' }}" href="{{route('finance.customers.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-people"></i>
                        </span>
                        <span class="menu-title">العملاء</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.suppliers.*') ? 'active' : '' }}" href="{{route('finance.suppliers.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-truck"></i>
                        </span>
                        <span class="menu-title">الموردين</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة المبيعات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.orders.*') ? 'active' : '' }}" href="{{route('finance.orders.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-bag-check"></i>
                        </span>
                        <span class="menu-title">طلبات البيع</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.offers.*') ? 'active' : '' }}" href="{{route('finance.offers.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-tags-fill"></i>
                        </span>
                        <span class="menu-title">العروض</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.coupons.*') ? 'active' : '' }}" href="{{route('finance.coupons.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-ticket-perforated"></i>
                        </span>
                        <span class="menu-title">الكوبونات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.coupon_usages.*') ? 'active' : '' }}" href="{{route('finance.coupon_usages.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-receipt-cutoff"></i>
                        </span>
                        <span class="menu-title">استخدامات الكوبونات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة المنتجات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.products.*') ? 'active' : '' }}" href="{{route('finance.products.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <span class="menu-title">المنتجات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.unitconversions.*') ? 'active' : '' }}" href="{{route('finance.products.unitconversions.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-shuffle"></i>
                        </span>
                        <span class="menu-title">وحدات التحويل</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.category.*') ? 'active' : '' }}" href="{{route('finance.category.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-collection"></i>
                        </span>
                        <span class="menu-title">الفئات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.brands.*') ? 'active' : '' }}" href="{{route('finance.brands.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-star"></i>
                        </span>
                        <span class="menu-title">الماركات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.units.*') ? 'active' : '' }}" href="{{route('finance.units.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-rulers"></i>
                        </span>
                        <span class="menu-title">الوحدات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.finance.attributes.*') ? 'active' : '' }}" href="{{route('finance.attributes.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-sliders"></i>
                        </span>
                        <span class="menu-title">الخصائص</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة المخزون</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.warehouses.*') ? 'active' : '' }}" href="{{route('finance.warehouses.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-building"></i>
                        </span>
                        <span class="menu-title">المستودعات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.inventory.*') ? 'active' : '' }}" href="{{route('finance.inventory.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-boxes"></i>
                        </span>
                        <span class="menu-title">المخزون</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.inventory.transfers.*') ? 'active' : '' }}" href="{{route('finance.inventory.transfers.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-arrow-left-right"></i>
                        </span>
                        <span class="menu-title">تحويلات المخزون</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.variant-prices.*') ? 'active' : '' }}" href="{{route('finance.variant-prices.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </span>
                        <span class="menu-title">التسعير </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة المشتريات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.purchases.*') ? 'active' : '' }}" href="{{route('finance.purchases.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-cart3"></i>
                        </span>
                        <span class="menu-title">المشتريات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">الماليات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.account_trees.*') ? 'active' : '' }}" href="{{route('finance.account_trees.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-diagram-3"></i>
                        </span>
                        <span class="menu-title">شجرة الحسابات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.trans_account_trees.*') ? 'active' : '' }}" href="{{route('finance.trans_account_trees.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-journal-bookmark"></i>
                        </span>
                        <span class="menu-title">قيود شجرة الحسابات </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.opening_balances.*') ? 'active' : '' }}" href="{{route('finance.opening_balances.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-journal-text"></i>
                        </span>
                        <span class="menu-title">الأرصدة الافتتاحية</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.vouchers.*') ? 'active' : '' }}" href="{{route('finance.vouchers.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-receipt"></i>
                        </span>
                        <span class="menu-title">السندات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.banks.*') ? 'active' : '' }}" href="{{route('finance.banks.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-bank"></i>
                        </span>
                        <span class="menu-title">البنوك</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                {{-- <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.bank_accounts.*') ? 'active' : '' }}" href="{{route('finance.bank_accounts.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-credit-card"></i>
                        </span>
                        <span class="menu-title">الحسابات البنكية</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.bank_transactions.*') ? 'active' : '' }}" href="{{route('finance.bank_transactions.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-arrow-left-right"></i>
                        </span>
                        <span class="menu-title">المعاملات البنكية</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div> --}}

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.treasuries.*') ? 'active' : '' }}" href="{{route('finance.treasuries.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-safe"></i>
                        </span>
                        <span class="menu-title">الخزن</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                {{-- <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('finance.treasury_transactions.*') ? 'active' : '' }}" href="{{route('finance.treasury_transactions.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-cash-coin"></i>
                        </span>
                        <span class="menu-title">حركات الخزنة</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div> --}}

                <div class="menu-item pt-5">
                    <!--begin:Menu content-->
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-4">ادارة الاعدادات</span>
                    </div>
                    <!--end:Menu content-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                    <a class="menu-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{route('admin.settings.edit', 1)}}">
                        <span class="menu-icon">
                            <i class="bi bi-gear-fill"></i>
                        </span>
                        <span class="menu-title">الاعدادات</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                     
                    <a class="menu-link {{ request()->routeIs('admin.admins.*') ? 'active' : '' }}" href="{{route('admin.admins.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-person-bounding-box"></i>
                        </span>
                        <span class="menu-title">مديرين النظام</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <!--end:Menu link-->
                </div>

                <div class="menu-item">
                    <!--begin:Menu link-->
                     
                    <a class="menu-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}" href="{{route('admin.pages.index')}}">
                        <span class="menu-icon">
                            <i class="bi bi-file-earmark-break"></i>
                        </span>
                        <span class="menu-title">الصفحات</span>
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