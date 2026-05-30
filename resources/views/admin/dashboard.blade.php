@extends('admin.layout.master')

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('style')
.stat-card { transition: transform .15s; }
.stat-card:hover { transform: translateY(-3px); }
@endsection

@section('breadcrumb')
@endsection

@section('content')
<div class="content flex-column-fluid" id="kt_content">

    {{-- ── Filter Bar ────────────────────────────────────────────────── --}}
    <div class="card mb-6">
        <div class="card-body py-4">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">السنة</label>
                    <select name="year" class="form-select form-select-solid">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">من تاريخ</label>
                    <input type="text" name="from" id="from_date" class="form-control form-control-solid flatpickr"
                           value="{{ $from }}" placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">إلى تاريخ</label>
                    <input type="text" name="to" id="to_date" class="form-control form-control-solid flatpickr"
                           value="{{ $to }}" placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> تطبيق
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-light-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i> إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── KPI Cards ─────────────────────────────────────────────────── --}}
    <div class="row g-5 mb-6">
        {{-- Revenue --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-4">
                    <div class="symbol symbol-55px">
                        <span class="symbol-label bg-light-success rounded-circle">
                            <i class="bi bi-cash-stack fs-2 text-success"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-semibold">إجمالي المبيعات</div>
                        <div class="fs-3 fw-bold text-gray-800">{{ number_format($totalRevenue, 2) }} <small class="fs-7 fw-normal">ر.س</small></div>
                        <div class="fs-8 text-muted">مدفوع: {{ number_format($totalPaid, 2) }} | متبقي: {{ number_format($totalRemaining, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Orders --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-4">
                    <div class="symbol symbol-55px">
                        <span class="symbol-label bg-light-primary rounded-circle">
                            <i class="bi bi-bag-check fs-2 text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-semibold">طلبات البيع</div>
                        <div class="fs-3 fw-bold text-gray-800">{{ number_format($totalOrders) }}</div>
                        <div class="fs-8 text-muted">إجمالي الطلبات في الفترة</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Purchases --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-4">
                    <div class="symbol symbol-55px">
                        <span class="symbol-label bg-light-warning rounded-circle">
                            <i class="bi bi-cart3 fs-2 text-warning"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-semibold">إجمالي المشتريات</div>
                        <div class="fs-3 fw-bold text-gray-800">{{ number_format($totalPurchases, 2) }} <small class="fs-7 fw-normal">ر.س</small></div>
                        <div class="fs-8 text-muted">مدفوع: {{ number_format($totalPurchasesPaid, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Customers --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-4">
                    <div class="symbol symbol-55px">
                        <span class="symbol-label bg-light-info rounded-circle">
                            <i class="bi bi-people fs-2 text-info"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-muted fs-7 fw-semibold">العملاء</div>
                        <div class="fs-3 fw-bold text-gray-800">{{ number_format($totalCustomers) }}</div>
                        <div class="fs-8 text-muted">جديد هذا العام: {{ $newCustomers }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Charts Row ────────────────────────────────────────────────── --}}
    <div class="row g-5 mb-6">
        {{-- Revenue & Purchases monthly chart --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800 fs-5">المبيعات والمشتريات الشهرية</span>
                        <span class="text-muted mt-1 fw-semibold fs-7">سنة {{ $year }}</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <canvas id="revenueChart" height="110"></canvas>
                </div>
            </div>
        </div>
        {{-- Orders by status pie --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">
                        <span class="card-label fw-bold text-gray-800 fs-5">الطلبات حسب الحالة</span>
                    </h3>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <canvas id="statusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Orders Count Chart + Top Customers ─────────────────────── --}}
    <div class="row g-5 mb-6">
        {{-- Monthly orders count --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">
                        <span class="card-label fw-bold text-gray-800 fs-5">عدد الطلبات شهرياً</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    <canvas id="ordersChart" height="160"></canvas>
                </div>
            </div>
        </div>
        {{-- Top customers --}}
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title">
                        <span class="card-label fw-bold text-gray-800 fs-5">أفضل 5 عملاء</span>
                        <span class="text-muted mt-1 fw-semibold fs-7 ms-2">سنة {{ $year }}</span>
                    </h3>
                </div>
                <div class="card-body pt-2">
                    @forelse($topCustomers as $tc)
                    @php
                        $pct = $totalRevenue > 0 ? round(($tc->total_revenue / $totalRevenue) * 100) : 0;
                    @endphp
                    <div class="d-flex align-items-center mb-5">
                        <div class="symbol symbol-35px me-3">
                            <span class="symbol-label bg-light-primary fw-bold text-primary fs-7">
                                {{ mb_substr($tc->customer->name ?? '?', 0, 2) }}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-gray-700 fs-7">{{ $tc->customer->name ?? 'محذوف' }}</span>
                                <span class="fw-bold text-gray-800 fs-7">{{ number_format($tc->total_revenue, 2) }} ر.س</span>
                            </div>
                            <div class="progress h-6px">
                                <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-10">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('dash/assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Flatpickr date pickers
    flatpickr('.flatpickr', { dateFormat: 'Y-m-d', allowInput: true });

    const monthLabels = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];

    // ── Revenue & Purchases Chart ─────────────────────────────────────
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    label: 'المبيعات (ر.س)',
                    data: @json($revenueChart),
                    backgroundColor: 'rgba(54, 153, 255, 0.75)',
                    borderRadius: 4
                },
                {
                    label: 'المشتريات (ر.س)',
                    data: @json($purchasesChart),
                    backgroundColor: 'rgba(255, 168, 0, 0.7)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('ar-SA') } }
            }
        }
    });

    // ── Orders count line chart ───────────────────────────────────────
    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'عدد الطلبات',
                data: @json($ordersChart),
                borderColor: '#50cd89',
                backgroundColor: 'rgba(80,205,137,0.12)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#50cd89'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // ── Orders by status doughnut chart ──────────────────────────────
    const statusLabels = {
        draft: 'مسودة', confirmed: 'مؤكد', processing: 'قيد التجهيز',
        shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي'
    };
    const statusData   = @json($ordersByStatus);
    const statusColors = ['#3699ff','#50cd89','#ffc700','#7239ea','#00b2ff','#f1416c'];
    const statusKeys   = Object.keys(statusData);

    if (statusKeys.length) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusKeys.map(k => statusLabels[k] ?? k),
                datasets: [{
                    data: statusKeys.map(k => statusData[k]),
                    backgroundColor: statusColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } }
                }
            }
        });
    }
</script>
@endsection