@section('script')
<script>
// ─────────────────────────────────────────────
//  Purchases JS — Select2 + dynamic items rows
// ─────────────────────────────────────────────
$(function () {

    var itemIndex = {{ isset($data) ? $data->items->count() : 0 }};
    var ajaxProductsUrl   = '{{ route("finance.purchases.ajax.products") }}';
    var ajaxSuppliersUrl  = '{{ route("finance.purchases.ajax.suppliers") }}';
    var ajaxVariantsUrl   = '{{ route("finance.purchases.ajax.variants") }}';
    var ajaxUnitsUrl      = '{{ route("finance.purchases.ajax.units") }}';
    var ajaxPriceUrl      = '{{ route("finance.purchases.ajax.price") }}';

    /* --------------------------------------------------------
       Pricing Mode:
         inclusive = السعر المدخل يشمل الضريبة (تُستخرج الضريبة منه)
         exclusive = السعر المدخل لا يشمل الضريبة (تُضاف الضريبة فوقه)
    -------------------------------------------------------- */
    var pricingMode    = '{{ $pricingMode ?? "exclusive" }}';
    var defaultTaxRate = {{ \App\Helpers\Helper::defaultTaxRate() }};

    // ── Supplier Select2 ─────────────────────
    $('#supplier_select').select2({

        dropdownParent: $('body'),
        placeholder: 'ابحث عن مورد ...',
        minimumInputLength: 0,
        language: {
            noResults:     function () { return 'لا توجد نتائج'; },
            searching:     function () { return 'جارٍ البحث ...'; },
            loadingMore:   function () { return 'تحميل المزيد ...'; },
            inputTooShort: function () { return 'اكتب للبحث'; },
        },
        ajax: {
            url: ajaxSuppliersUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { search: params.term || '', page: params.page || 1 };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return { results: data.results, pagination: data.pagination };
            },
        },
    });

    // ── Product Select2 on a row ─────────────
    function initProductSelect2(el) {
        $(el).select2({
            dropdownParent: $(el).closest('tr'),
            placeholder: 'ابحث عن منتج ...',
            minimumInputLength: 0,
            language: {
                noResults:   function () { return 'لا توجد نتائج'; },
                searching:   function () { return 'جارٍ البحث ...'; },
                loadingMore: function () { return 'تحميل المزيد ...'; },
            },
            ajax: {
                url: ajaxProductsUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { search: params.term || '', page: params.page || 1 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return { results: data.results, pagination: data.pagination };
                },
            },
        }).on('select2:select', function (e) {
            var item  = e.params.data;
            var $r    = $(this).closest('tr');
            var price = parseFloat(item.purchase_price || 0);
            $r.attr('data-product-cost', price);                              // store on row for variant-deselect restore
            $r.find('.item-cost').val(price.toFixed(2)).attr('data-base-cost', price); // HTML attr — readable by unit handler
            $r.find('.item-tax').val(parseFloat(item.tax_rate || defaultTaxRate).toFixed(2));
            loadVariants($r, item.id);
            loadUnitsForRow($r, item.id, null, null);
            calcRowTotal($r);
        });
    }

    // ── Load variants for a product ──────────
    function loadVariants($row, productId) {
        var $variantSel = $row.find('.item-variant');
        $variantSel.empty().append('<option value="">بدون متغير</option>');
        if (!productId) return;

        $.getJSON(ajaxVariantsUrl, { product_id: productId }, function (data) {
            $.each(data.results, function (i, v) {
                $variantSel.append('<option value="' + v.id + '" data-price="' + v.purchase_price + '">' + v.text + '</option>');
            });
        });
    }

    // ── Row total calculation ─────────────────
    function calcRowTotal($row) {
        var qty  = parseFloat($row.find('.item-qty').val())  || 0;
        var cost = parseFloat($row.find('.item-cost').val()) || 0;
        var disc = parseFloat($row.find('.item-disc').val()) || 0;
        var tax  = parseFloat($row.find('.item-tax').val())  || 0;
        var base = qty * cost - disc;
        var total;
        if (pricingMode === 'inclusive') {
            total = base; // السعر شامل الضريبة — لا تُضاف ضريبة إضافية
        } else {
            total = base * (1 + tax / 100);
        }
        $row.find('.item-total-cell').text(total.toFixed(2));
        updateSummary();
    }

    // ── Summary totals ────────────────────────
    function updateSummary() {
        var subtotal = 0, discTotal = 0, taxTotal = 0, total = 0;
        $('#items-body .item-row').each(function () {
            var qty  = parseFloat($(this).find('.item-qty').val())  || 0;
            var cost = parseFloat($(this).find('.item-cost').val()) || 0;
            var disc = parseFloat($(this).find('.item-disc').val()) || 0;
            var tax  = parseFloat($(this).find('.item-tax').val())  || 0;
            var gross = qty * cost - disc;
            discTotal += disc;
            if (pricingMode === 'inclusive') {
                var lineTax = gross * tax / (100 + tax);
                taxTotal  += lineTax;
                subtotal  += gross - lineTax;
                total     += gross;
            } else {
                var lineTax = gross * tax / 100;
                subtotal  += gross;
                taxTotal  += lineTax;
                total     += gross + lineTax;
            }
        });
        $('#summary-subtotal').text(subtotal.toFixed(2) + ' ر.س');
        $('#summary-discount').text(discTotal.toFixed(2) + ' ر.س');
        $('#summary-tax').text(taxTotal.toFixed(2) + ' ر.س');
        $('#summary-total').text(total.toFixed(2) + ' ر.س');
    }

    // ── Row template ──────────────────────────
    function newRowHtml(idx) {
        return `
        <tr class="item-row">
            <td>
                <select class="form-select form-select-solid form-select-sm item-product"
                        name="items[${idx}][product_id]" data-placeholder="ابحث عن منتج ..."></select>
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm item-variant"
                        name="items[${idx}][variant_id]" >
                    <option value="">بدون متغير</option>
                </select>
            </td>
            <td>
                <select class="form-select form-select-solid form-select-sm item-unit"
                        name="items[${idx}][unit_conversion_id]" >
                    <option value="">الوحدة الأساسية</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-qty"
                       name="items[${idx}][quantity]" value="1" min="0.001" step="0.001"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-cost"
                       name="items[${idx}][unit_cost]" value="0" min="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-disc"
                       name="items[${idx}][discount]" value="0" min="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-tax"
                       name="items[${idx}][tax_rate]" value="${defaultTaxRate.toFixed(2)}" min="0" max="100" step="0.01"></td>
            <td class="text-end fw-bold item-total-cell">0.00</td>
            <td><button type="button" class="btn btn-icon btn-xs btn-light-danger remove-row">
                <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
            </button></td>
        </tr>`;
    }

    // ── Add row ───────────────────────────────
    $('#addItemBtn').on('click', function () {
        var $row = $(newRowHtml(itemIndex++));
        $('#items-body').append($row);
        initProductSelect2($row.find('.item-product'));
        calcRowTotal($row);
    });

    // ── Remove row ────────────────────────────
    $('#items-body').on('click', '.remove-row', function () {
        $(this).closest('tr').remove();
        updateSummary();
    });

    // ── Calc on input change ──────────────────
    $('#items-body').on('input change', '.item-qty,.item-cost,.item-disc,.item-tax', function () {
        calcRowTotal($(this).closest('tr'));
    });

    // ── Variant change — fetch resolved purchase price from backend ───
    // Mirrors orders JS: calls Helper::resolvePurchasePrice(productId, variantId)
    // Priority: variant.average_cost → product.average_cost → product.purchase_price
    $('#items-body').on('change', '.item-variant', function () {

        var $row      = $(this).closest('tr');
        var productId = $row.find('.item-product').val();
        var variantId = $(this).val() || null;

        if (!productId) return;

        var $costInput = $row.find('.item-cost');
        var $unitOpt   = $row.find('.item-unit option:selected');

        $.getJSON(ajaxPriceUrl, { product_id: productId, variant_id: variantId || '' }, function (res) {
            var basePrice = parseFloat(res.purchase_price || 0);
            console.log(basePrice);
            // Keep base cost as the raw per-base-unit price
            $costInput.attr('data-base-cost', basePrice);
            // Apply current unit rate right away
            var rate = ($unitOpt.val()) ? (parseFloat($unitOpt.attr('data-conversion-rate')) || 1) : 1;
            $costInput.val((basePrice / rate).toFixed(2));
            calcRowTotal($row);
        });

        // Reload units in parallel (keep current unit selected)
        loadUnitsForRow($row, productId, variantId, $unitOpt.val() || null);
    });

    // ── Unit conversion change ────────────────
    $('#items-body').on('change', '.item-unit', function () {
        var $row       = $(this).closest('tr');
        var $opt       = $(this).find('option:selected');
        var $costInput = $row.find('.item-cost');
        
        // Read from HTML attr (set by product/variant handlers) — reliable across jQuery wrappers
        var baseCost   = parseFloat($costInput.attr('data-base-cost') || $costInput.val()) || 0;
        var rate       = parseFloat($opt.attr('data-conversion-rate')) || 1;
        if (!$opt.val()) rate = 1;
        
        // In purchases: baseCost = cost per base unit; dividing gives cost per converted unit
        // e.g. average_cost=100 (per piece), rate=2 (1 box = 2 pieces) → cost per box = 100/2 = 50
        $costInput.val((baseCost / rate).toFixed(2));
        calcRowTotal($row);
    });

    // ── Load unit conversions for a row ───────
    function loadUnitsForRow($row, productId, variantId, selectedUnitId) {
        if (!productId) return;
        var $unitSel = $row.find('.item-unit');
        $.get(ajaxUnitsUrl, { product_id: productId, variant_id: variantId || '' }, function (data) {
            $unitSel.find('option:not([value=""])').remove();
            if (data.results && data.results.length) {
                $.each(data.results, function (i, u) {
                    // Use HTML string — $('<option>', {'data-x': v}) puts data in jQuery cache only,
                    // NOT as a real HTML attribute, so .attr('data-x') would return undefined.
                    var isSelected = (selectedUnitId && u.id == selectedUnitId) || (!selectedUnitId && u.is_default);
                    var html = '<option value="' + u.id + '"'
                        + ' data-conversion-rate="' + u.conversion_rate + '"'
                        + ' data-allow-fractions="' + (u.allow_fractions ? 1 : 0) + '"'
                        + ' data-decimal-places="' + u.decimal_places + '"'
                        + (isSelected ? ' selected' : '') + '>'
                        + u.text + '</option>';
                    $unitSel.append(html);
                });
            }
        });
    }

    // ── Init existing rows (edit mode) ───────
    $('#items-body .item-row').each(function () {
        var $row = $(this);
        var pid  = $row.find('.item-product').val();
        var vid  = $row.find('.item-variant').val() || null;
        var uid  = $row.find('.item-unit').data('unit-id') || null;
        initProductSelect2($row.find('.item-product'));
        if (pid) loadUnitsForRow($row, pid, vid, uid);
    });

    $('#fill_paid_btn').on('click', function () {
        var total = parseFloat($('#summary-total').text()) || 0;
        $('#paid_input').val(total.toFixed(2));
    });

    // ── Initial summary ───────────────────────
    updateSummary();
    
});
</script>
@endsection
