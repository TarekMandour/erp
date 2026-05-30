<script>
@php $data = $data ?? null; @endphp
(function () {
    // Map: offer type → which conditional groups to show
    var groupRules = {
        'percentage':              ['value'],
        'fixed':                   ['value'],
        'product_price_discount':  ['value'],
        'first_order':             ['value'],
        'bundle':                  ['value', 'buy-get'],
        'buy_x_get_y':             ['buy-get', 'get-qty'],
        'buy_x_get_discount':      ['buy-get', 'min-amount', 'discount'],
        'buy_amount_get_discount': ['min-amount', 'discount'],
        'free_shipping':           ['min-amount', 'free-shipping-info'],
        'tiered':                  ['tier'],
        'flash':                   ['flash', 'discount'],
    };

    // Map: offer type → suffix text for the value input
    var valueSuffix = {
        'percentage':  '%',
        'first_order': '%',
        'fixed':                  'ر.س',
        'product_price_discount': 'ر.س',
        'bundle':                 'ر.س',
    };

    function updateForm() {
        var type = $('#offer_type').val();

        // Hide all conditional groups
        $('.offer-grp').hide();

        // Show groups for this type
        var groups = groupRules[type] || [];
        groups.forEach(function (g) {
            $('.offer-grp-' + g.replace('_', '-')).show();
        });

        // Update value suffix
        if (valueSuffix[type]) {
            $('#value-suffix').text(valueSuffix[type]);
        }
    }

    // ----------------------------------------------------------------
    // Tier builder helpers
    // ----------------------------------------------------------------
    function buildTierRow(minAmount, discountPct) {
        var idx = Date.now() + Math.random();
        return '<div class="d-flex align-items-center gap-3 mb-3 tier-row" data-idx="' + idx + '">' +
            '<div class="input-group w-auto">' +
            '<span class="input-group-text">من</span>' +
            '<input type="number" step="0.01" min="0" class="form-control form-control-solid tier-min" placeholder="100" value="' + (minAmount || '') + '">' +
            '<span class="input-group-text">ر.س</span>' +
            '</div>' +
            '<span class="text-muted">→</span>' +
            '<div class="input-group w-auto">' +
            '<input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid tier-pct" placeholder="5" value="' + (discountPct || '') + '">' +
            '<span class="input-group-text">%</span>' +
            '</div>' +
            '<button type="button" class="btn btn-icon btn-sm btn-light-danger remove-tier"><i class="bi bi-x-lg"></i></button>' +
            '</div>';
    }

    function saveTiers() {
        var tiers = [];
        $('.tier-row').each(function () {
            var min = parseFloat($(this).find('.tier-min').val());
            var pct = parseFloat($(this).find('.tier-pct').val());
            if (!isNaN(min) && !isNaN(pct)) {
                tiers.push({ min_amount: min, discount_percentage: pct });
            }
        });
        $('#tier_thresholds_input').val(JSON.stringify(tiers));
    }

    // ----------------------------------------------------------------
    // Load existing tiers (edit mode)
    // ----------------------------------------------------------------
    @if(isset($data) && $data && $data?->tier_thresholds)
    var existingTiers = @json($data?->tier_thresholds);
    if (Array.isArray(existingTiers)) {
        existingTiers.forEach(function (t) {
            $('#tier-rows').append(buildTierRow(t.min_amount, t.discount_percentage));
        });
    }
    @endif

    // ----------------------------------------------------------------
    // Event bindings
    // ----------------------------------------------------------------
    $('#offer_type').on('change', updateForm);

    // ----------------------------------------------------------------
    // applies_to → reinitialize Select2 AJAX for product or category
    // ----------------------------------------------------------------
    var ajaxUrl = '{{ route("finance.offers.ajax.items") }}';
    var currentItemType = null;

    function initItemsSelect2(type) {
        var $sel = $('#offer_items_select');

        // Destroy previous instance if exists
        if ($sel.hasClass('select2-hidden-accessible')) {
            $sel.select2('destroy');
        }

        // Clear selected values when switching type
        if (currentItemType && currentItemType !== type) {
            $sel.empty();
        }
        currentItemType = type;

        var labelText = type === 'category' ? 'الفئات' : 'المنتجات';
        $('#offer-items-label').text(labelText);

        $sel.select2({
            placeholder: 'ابحث وحدد ...',
            allowClear: true,
            multiple: true,
            minimumInputLength: 0,
            language: {
                searching: function () { return 'جاري البحث ...'; },
                noResults: function () { return 'لا توجد نتائج'; },
                loadingMore: function () { return 'تحميل المزيد ...'; },
                inputTooShort: function () { return ''; },
            },
            ajax: {
                url: ajaxUrl,
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return {
                        type: type,
                        search: params.term || '',
                        page: params.page || 1,
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.results,
                        pagination: { more: data.pagination.more },
                    };
                },
                cache: true,
            },
        });
    }

    function toggleItemsRow() {
        var val = $('select[name="applies_to"]').val();
        if (val === 'product' || val === 'category') {
            $('#offer-items-row').show();
            initItemsSelect2(val);
        } else {
            $('#offer-items-row').hide();
            var $sel = $('#offer_items_select');
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }
        }
    }

    $('select[name="applies_to"]').on('change', toggleItemsRow);

    $('#add-tier-row').on('click', function () {
        $('#tier-rows').append(buildTierRow());
    });

    $(document).on('click', '.remove-tier', function () {
        $(this).closest('.tier-row').remove();
        saveTiers();
    });

    $(document).on('input', '.tier-min, .tier-pct', saveTiers);

    // Save tiers before form submit
    $('form').on('submit', saveTiers);

    // ----------------------------------------------------------------
    // Init on page load
    // ----------------------------------------------------------------
    $(document).ready(function () {
        updateForm();
        toggleItemsRow();
        // Init tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
})();
</script>
