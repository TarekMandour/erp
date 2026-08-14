@section('script')
<script>
$(function () {
    /* --------------------------------------------------------
       URLs
    -------------------------------------------------------- */
    var ajaxCustomersUrl = '{{ route("finance.orders.ajax.customers") }}';
    var ajaxProductsUrl  = '{{ route("finance.orders.ajax.products") }}';
    var ajaxVariantsUrl  = '{{ route("finance.orders.ajax.variants") }}';
    var ajaxStockUrl     = '{{ route("finance.orders.ajax.stock") }}';
    var ajaxScanUrl      = '{{ route("finance.orders.ajax.scan") }}';
    var ajaxUnitsUrl     = '{{ route("finance.orders.ajax.units") }}';
    var ajaxCouponUrl    = '{{ route("finance.orders.ajax.coupon") }}';
    var ajaxOffersUrl    = '{{ route("finance.orders.ajax.offers") }}';
    var ajaxApplyOfferUrl = '{{ route("finance.orders.ajax.apply_offer") }}';

    /* --------------------------------------------------------
       Pricing Mode:
         inclusive = السعر المدخل يشمل الضريبة (تُستخرج الضريبة منه)
         exclusive = السعر المدخل لا يشمل الضريبة (تُضاف الضريبة فوقه)
    -------------------------------------------------------- */
    var pricingMode    = '{{ $pricingMode ?? "exclusive" }}';
    var defaultTaxRate = {{ \App\Helpers\Helper::defaultTaxRate() }};

    /* --------------------------------------------------------
       Customer Select2
    -------------------------------------------------------- */
    $('#customer_select').select2({
        dir: 'rtl', placeholder: 'ابحث عن عميل ...', allowClear: true,
        ajax: {
            url: ajaxCustomersUrl, dataType: 'json', delay: 300,
            data: function (p) { return { search: p.term, page: p.page || 1 }; },
            processResults: function (d) { return { results: d.results, pagination: d.pagination }; },
            cache: true
        }
    });

    /* --------------------------------------------------------
       Helpers
    -------------------------------------------------------- */
    var rowIndex = {{ isset($data) ? count($data->items) : 1 }};

    function getWarehouseId() { return $('#warehouse_select').val(); }
    function getCustomerId()   { return $('#customer_select').val() || ''; }

    function stockClass(avail) {
        return avail <= 0 ? 'badge-light-danger' : avail < 5 ? 'badge-light-warning' : 'badge-light-success';
    }

    /** Total qty reserved in ALL rows for given product+variant */
    function getReservedQty(productId, variantId) {
        var reserved = 0;
        $('#items-body .item-row').each(function () {
            var $r = $(this);
            if ($r.find('.item-product').val() == productId &&
                ($r.find('.item-variant').val() || '') == (variantId || '')) {
                reserved += parseFloat($r.find('.item-qty').val()) || 0;
            }
        });
        return reserved;
    }

    /* =========================================================
       SCANNER INPUT
    ========================================================= */
    var scanTimer = null;

    $('#scanner_input').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(scanTimer);
            processScan($(this).val().trim());
        }
    }).on('input', function () {
        /* Auto-trigger after 300 ms of no typing (handles physical scanners that
           send characters fast then stop — but NOT Enter key scanners).
           Only fires if value looks like a barcode (no spaces, min 4 chars). */
        var val = $(this).val().trim();
        clearTimeout(scanTimer);
        if (val.length >= 4 && !/\s/.test(val)) {
            scanTimer = setTimeout(function () { processScan(val); }, 400);
        }
    });

    function processScan(code) {
        if (!code) return;
        var warehouseId = getWarehouseId();
        if (!warehouseId) {
            scanFeedback('يرجى اختيار المستودع أولاً.', 'text-danger');
            return;
        }

        scanFeedback('جاري البحث ...', 'text-muted');

        $.get(ajaxScanUrl, { code: code, warehouse_id: warehouseId, customer_id: getCustomerId() }, function (res) {
            if (!res.found) {
                scanFeedback(res.message || 'لم يُعثر على المنتج.', 'text-danger');
                return;
            }

            var reserved  = getReservedQty(res.product_id, res.variant_id || '');
            var available = Math.max(0, res.stock - reserved);

            if (available <= 0) {
                scanFeedback('المنتج «' + res.product_text + '» — المخزون صفر أو محجوز بالكامل.', 'text-danger');
                return;
            }

            /* ── If the last row is still empty (default row), fill it ── */
            var $lastRow = $('#items-body .item-row:last');
            var lastProductVal = $lastRow.find('.item-product').val();

            if (!lastProductVal) {
                fillRow($lastRow, res, available);
                $lastRow.removeClass('default-row');
            } else {
                /* Check if this product+variant already exists → increment qty */
                var $existing = findExistingRow(res.product_id, res.variant_id || '');
                if ($existing) {
                    var currentQty = parseFloat($existing.find('.item-qty').val()) || 0;
                    var newQty     = currentQty + 1;
                    if (newQty > res.stock) {
                        scanFeedback('وصلت للحد الأقصى المتاح للمنتج «' + res.product_text + '».', 'text-warning');
                        return;
                    }
                    $existing.find('.item-qty').val(newQty.toFixed(3));
                    calcRowTotal($existing);
                    updateSummary();
                    refreshSiblingBadges(res.product_id, res.variant_id || '');
                    scanFeedback('تمت زيادة كمية «' + res.product_text + '» إلى ' + newQty, 'text-success');
                } else {
                    addRow(res.product_id, res.product_text, res.variant_id,
                           res.variant_text, res.selling_price, res.tax_rate, res.stock, 1);
                    scanFeedback('تمت إضافة «' + res.product_text + '»', 'text-success');
                }
            }

            /* Clear input for next scan */
            $('#scanner_input').val('').focus();
        }).fail(function () {
            scanFeedback('خطأ في الاتصال بالخادم.', 'text-danger');
        });
    }

    function scanFeedback(msg, cls) {
        $('#scanner_feedback')
            .text(msg)
            .removeClass('text-muted text-danger text-success text-warning')
            .addClass(cls);
    }

    function findExistingRow(productId, variantId) {
        var found = null;
        $('#items-body .item-row').each(function () {
            var $r = $(this);
            if ($r.find('.item-product').val() == productId &&
                ($r.find('.item-variant').val() || '') == (variantId || '')) {
                found = $r; return false;
            }
        });
        return found;
    }

    /** Fill an existing (empty) row with scanned product data */
    function fillRow($row, res, available) {
        var $prodSel = $row.find('.item-product');

        // Add option to select and init Select2 if needed
        var $opt = $('<option>', { value: res.product_id, selected: true }).text(res.product_text);
        $prodSel.empty().append($opt);
        if (!$prodSel.hasClass('select2-hidden-accessible')) {
            initProductSelect2ForRow($row);
        } else {
            $prodSel.trigger('change.select2');
        }

        $row.find('.item-price').val(parseFloat(res.selling_price).toFixed(2)).data('base-price', parseFloat(res.selling_price) || 0);
        $row.find('.item-tax').val(parseFloat(res.tax_rate).toFixed(2));
        $row.find('.item-unit-cost').val(parseFloat(res.average_cost || 0).toFixed(4));
        $row.find('.item-qty').val('1.000');

        // Stock badge
        $row.find('.item-stock-badge')
            .text(available.toFixed(2))
            .removeClass('badge-light-success badge-light-danger badge-light-warning badge-light-secondary')
            .addClass(stockClass(available));

        // Load variants and pre-select if scanned variant
        loadVariantsForRow($row, res.product_id, getWarehouseId(), res.variant_id);
        loadUnitsForRow($row, res.product_id, res.variant_id || null, null);

        calcRowTotal($row);
        updateSummary();
        scanFeedback('تمت إضافة «' + res.product_text + '»', 'text-success');
    }

    /* =========================================================
       BUILD NEW ROW
    ========================================================= */
    function addEmptyRow() {
        addRow(null, null, null, null, 0, defaultTaxRate, 0, 1);
    }

    function addRow(productId, productText, variantId, variantText, price, taxRate, actualStock, qty) {
        var idx       = rowIndex++;
        var reserved  = productId ? getReservedQty(productId, variantId || '') : 0;
        var available = Math.max(0, actualStock - reserved);

        var $productOption = productId
            ? $('<option>', { value: productId, selected: true, 'data-price': price, 'data-tax': taxRate }).text(productText)
            : $('<option>', { value: '' }).text('');

        var $variantSelect = $('<select>').attr({
            class: 'form-select form-select-solid form-select-sm item-variant',
            name: 'items[' + idx + '][variant_id]'
        }).append($('<option>', { value: '' }).text('بدون متغير'));

        var $row = $('<tr>').addClass('item-row').append(
            $('<td>').append(
                $('<select>').attr({
                    class: 'form-select form-select-solid form-select-sm item-product',
                    name: 'items[' + idx + '][product_id]'
                }).append($productOption)
            ),
            $('<td>').append($variantSelect),
            $('<td>').append(
                $('<select>').attr({
                    class: 'form-select form-select-solid form-select-sm item-unit',
                    name: 'items[' + idx + '][unit_conversion_id]'
                }).append($('<option>', { value: '' }).text('الوحدة الأساسية'))
            ),
            $('<td>').append(
                $('<input>').attr({ type: 'number', class: 'form-control form-control-solid form-control-sm item-qty',
                    name: 'items[' + idx + '][quantity]', value: qty, min: '0.001', step: '0.001' })
            ),
            $('<td class="text-center">').append(
                $('<span>').addClass('badge item-stock-badge ' + (productId ? stockClass(available) : 'badge-light-secondary'))
                    .attr('title', 'المخزون المتاح').text(productId ? available.toFixed(2) : '—')
            ),
            $('<td>').append(
                $('<input>').attr({ type: 'number', class: 'form-control form-control-solid form-control-sm item-price',
                    name: 'items[' + idx + '][unit_price]', value: parseFloat(price || 0).toFixed(2), min: '0', step: '0.01',
                    'data-base-price': parseFloat(price || 0) })
            ),
            $('<td>').append(
                $('<input>').attr({ type: 'number', class: 'form-control form-control-solid form-control-sm item-unit-cost',
                    name: 'items[' + idx + '][unit_cost]', value: '0.00', min: '0', step: '0.0001',
                    placeholder: '0.00', readonly: true })
            ),
            $('<td>').append(
                $('<input>').attr({ type: 'number', class: 'form-control form-control-solid form-control-sm item-disc',
                    name: 'items[' + idx + '][discount]', value: '0.00', min: '0', step: '0.01' })
            ),
            $('<td>').append(
                $('<input>').attr({ type: 'number', class: 'form-control form-control-solid form-control-sm item-tax',
                    name: 'items[' + idx + '][tax_rate]', value: parseFloat(taxRate || 0).toFixed(2), min: '0', max: '100', step: '0.01' })
            ),
            $('<td class="text-end fw-bold item-total-cell">').text('0.00'),
            $('<td>').append(
                $('<button>').attr({ type: 'button', class: 'btn btn-icon btn-xs btn-light-danger remove-row' })
                    .html('<i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>')
            )
        );

        $('#items-body').append($row);
        initProductSelect2ForRow($row);
        if (productId) {
            loadVariantsForRow($row, productId, getWarehouseId(), variantId);
            loadUnitsForRow($row, productId, variantId || null, null);
            refreshSiblingBadges(productId, variantId || '');
        }
        calcRowTotal($row);
        updateSummary();
        updateGlobalStockWarning();
    }

    /* =========================================================
       ROW PRODUCT SELECT2
    ========================================================= */
    function initProductSelect2ForRow($row) {
        var $sel = $row.find('.item-product');
        if ($sel.hasClass('select2-hidden-accessible')) return;
        $sel.select2({
            dir: 'rtl', placeholder: 'ابحث عن منتج ...',
            ajax: {
                url: ajaxProductsUrl, dataType: 'json', delay: 300,
                data: function (p) { return { search: p.term, page: p.page || 1, warehouse_id: getWarehouseId(), customer_id: getCustomerId() }; },
                processResults: function (d) { return { results: d.results, pagination: d.pagination }; },
                cache: true
            }
        });

        $sel.on('select2:select', function (e) {
            var item = e.params.data;
            var $r   = $(this).closest('tr');
            var basePrice = parseFloat(item.selling_price || 0);
            $r.find('.item-price').val(basePrice.toFixed(2)).data('base-price', basePrice);
            $r.find('.item-tax').val(parseFloat(item.tax_rate || 0).toFixed(2));
            $r.find('.item-unit-cost').val(parseFloat(item.average_cost || 0).toFixed(4));
            fetchAndSetRowStock($r, item.id, null, getWarehouseId());
            loadVariantsForRow($r, item.id, getWarehouseId(), null);
            loadUnitsForRow($r, item.id, null, null);
            calcRowTotal($r);
            updateSummary();
        });
    }

    /* =========================================================
       UNIT CONVERSIONS FOR ROW
    ========================================================= */
    function loadUnitsForRow($row, productId, variantId, selectedUnitId) {
        if (!productId) return;
        var $unitSel = $row.find('.item-unit');
        $.get(ajaxUnitsUrl, { product_id: productId, variant_id: variantId || '' }, function (data) {
            $unitSel.find('option:not([value=""])').remove();
            var autoSelected = false;
            if (data.results && data.results.length) {
                $.each(data.results, function (i, u) {
                    var $opt = $('<option>', {
                        value: u.id,
                        'data-conversion-rate': u.conversion_rate,
                        'data-allow-fractions': u.allow_fractions,
                        'data-decimal-places':  u.decimal_places
                    }).text(u.text);
                    if (selectedUnitId && u.id == selectedUnitId) {
                        $opt.attr('selected', true);
                        autoSelected = true;
                    } else if (!selectedUnitId && u.is_default) {
                        $opt.attr('selected', true);
                        autoSelected = true;
                    }
                    $unitSel.append($opt);
                });
            }
            // Trigger change so the price updates when a unit is auto-selected
            if (autoSelected) {
                $unitSel.trigger('change');
            }
        });
    }

    /* =========================================================
       VARIANTS FOR ROW
    ========================================================= */
    function loadVariantsForRow($row, productId, warehouseId, selectedVariantId) {
        if (!productId) return;
        var $varSel = $row.find('.item-variant');
        $.get(ajaxVariantsUrl, { product_id: productId, warehouse_id: warehouseId, customer_id: getCustomerId() }, function (data) {
            $varSel.find('option:not([value=""])').remove();
            if (data.results && data.results.length) {
                $.each(data.results, function (i, v) {
                    var $opt = $('<option>', { value: v.id, 'data-price': v.selling_price, 'data-stock': v.stock, 'data-average-cost': v.average_cost }).text(v.text);
                    if (selectedVariantId && v.id == selectedVariantId) $opt.attr('selected', true);
                    $varSel.append($opt);
                });
                if (selectedVariantId) $varSel.trigger('change');
            }
        });
    }

    $(document).on('change', '.item-variant', function () {
        var $row      = $(this).closest('tr');
        var $opt      = $(this).find(':selected');
        var productId = $row.find('.item-product').val();
        var variantId = $opt.val() || null;

        if ($opt.val() && $opt.data('price') !== undefined) {
            var basePrice = parseFloat($opt.data('price'));
            $row.find('.item-price').val(basePrice.toFixed(2)).data('base-price', basePrice);
        }
        if ($opt.val() && $opt.data('average-cost') !== undefined) {
            $row.find('.item-unit-cost').val(parseFloat($opt.data('average-cost') || 0).toFixed(4));
        }
        fetchAndSetRowStock($row, productId, variantId, getWarehouseId());
        loadUnitsForRow($row, productId, variantId, null);
        calcRowTotal($row);
        updateSummary();
    });

    $(document).on('change', '.item-unit', function () {
        var $row        = $(this).closest('tr');
        var $opt        = $(this).find(':selected');
        var $priceInput = $row.find('.item-price');
        var basePrice   = parseFloat($priceInput.data('base-price') || $priceInput.val()) || 0;
        var rate        = parseFloat($opt.data('conversion-rate')) || 1;
        if (!$opt.val()) rate = 1; // no unit — revert to base price
        $priceInput.val(rate > 0 ? (basePrice / rate).toFixed(2) : basePrice.toFixed(2));
        calcRowTotal($row);
        updateSummary();
    });

    /* =========================================================
       STOCK BADGES
    ========================================================= */
    function fetchAndSetRowStock($row, productId, variantId, warehouseId) {
        if (!productId || !warehouseId) return;
        $.get(ajaxStockUrl, { product_id: productId, warehouse_id: warehouseId, variant_id: variantId || '' },
            function (res) { applyRowStockBadge($row, productId, variantId || '', parseFloat(res.stock || 0)); });
    }

    function applyRowStockBadge($row, productId, variantId, actualStock) {
        var thisQty       = parseFloat($row.find('.item-qty').val()) || 0;
        var allReserved   = getReservedQty(productId, variantId);
        var otherReserved = Math.max(0, allReserved - thisQty);
        var available     = Math.max(0, actualStock - otherReserved);

        $row.find('.item-stock-badge')
            .text(available.toFixed(2))
            .removeClass('badge-light-success badge-light-danger badge-light-warning badge-light-secondary')
            .addClass(stockClass(available));

        if (thisQty > available) $row.find('.item-qty').addClass('is-invalid');
        else $row.find('.item-qty').removeClass('is-invalid');
        updateGlobalStockWarning();
    }

    function refreshSiblingBadges(productId, variantId) {
        var warehouseId = getWarehouseId();
        if (!warehouseId) return;
        $('#items-body .item-row').each(function () {
            var $r = $(this);
            if ($r.find('.item-product').val() == productId &&
                ($r.find('.item-variant').val() || '') == (variantId || '')) {
                fetchAndSetRowStock($r, productId, variantId || null, warehouseId);
            }
        });
    }

    function recheckAllStocks() {
        var warehouseId = getWarehouseId();
        if (!warehouseId) return;
        $('#items-body .item-row').each(function () {
            var $r = $(this);
            var pid = $r.find('.item-product').val();
            var vid = $r.find('.item-variant').val() || null;
            if (pid) fetchAndSetRowStock($r, pid, vid, warehouseId);
        });
    }

    $('#warehouse_select').on('change', function () { recheckAllStocks(); });

    /* =========================================================
       TOTALS
    ========================================================= */
    function calcRowTotal($row) {
        var qty     = parseFloat($row.find('.item-qty').val())   || 0;
        var price   = parseFloat($row.find('.item-price').val()) || 0;
        var disc    = parseFloat($row.find('.item-disc').val())  || 0;
        var taxRate = parseFloat($row.find('.item-tax').val())   || 0;
        var base    = qty * price - disc;
        var total;
        if (pricingMode === 'inclusive') {
            // السعر شامل الضريبة: الإجمالي = base (لا تُضاف ضريبة إضافية)
            total = base;
        } else {
            // السعر غير شامل: الإجمالي = base + ضريبة
            total = base + base * taxRate / 100;
        }
        $row.find('.item-total-cell').text(total.toFixed(2));
    }

    function updateSummary() {
        var subtotal = 0, totalDisc = 0, totalTax = 0;
        $('#items-body .item-row').each(function () {
            var $r   = $(this);
            var qty  = parseFloat($r.find('.item-qty').val())   || 0;
            var p    = parseFloat($r.find('.item-price').val()) || 0;
            var disc = parseFloat($r.find('.item-disc').val())  || 0;
            var tax  = parseFloat($r.find('.item-tax').val())   || 0;
            var gross = qty * p - disc;
            totalDisc += disc;
            if (pricingMode === 'inclusive') {
                // الضريبة مُدرجة في السعر: tax = gross * rate / (100 + rate)
                var lineTax = gross * tax / (100 + tax);
                totalTax += lineTax;
                subtotal += gross - lineTax; // السعر قبل الضريبة
            } else {
                subtotal  += gross;
                totalTax  += gross * tax / 100;
            }
        });
        var shipping      = parseFloat($('#shipping_cost').val()) || 0;
        var couponDisc    = parseFloat($('#coupon_discount_input').val()) || 0;
        var offerDisc     = parseFloat($('#offer_discount_input').val()) || 0;
        $('#summary-subtotal').text(subtotal.toFixed(2) + ' ر.س');
        $('#summary-discount').text(totalDisc.toFixed(2) + ' ر.س');
        $('#summary-tax').text(totalTax.toFixed(2) + ' ر.س');
        $('#summary-shipping').text(shipping.toFixed(2) + ' ر.س');
        if (couponDisc > 0) {
            $('#summary-coupon').text('- ' + couponDisc.toFixed(2) + ' ر.س');
            $('#summary-coupon-row').removeClass('d-none');
        } else {
            $('#summary-coupon-row').addClass('d-none');
        }
        if (offerDisc > 0) {
            $('#summary-offer').text('- ' + offerDisc.toFixed(2) + ' ر.س');
            $('#summary-offer-row').removeClass('d-none');
        } else {
            $('#summary-offer-row').addClass('d-none');
        }
        var total = Math.max(0, subtotal + totalTax + shipping - couponDisc - offerDisc);
        $('#summary-total').text(total.toFixed(2) + ' ر.س');
    }

    function updateGlobalStockWarning() {
        if ($('.item-qty.is-invalid').length) {
            $('#stock-warning-text').text('تحذير: بعض المنتجات تتجاوز الكمية المتاحة في المخزون!');
            $('#stock-warning').removeClass('d-none');
        } else {
            $('#stock-warning').addClass('d-none');
        }
    }

    /* =========================================================
       LIVE EVENTS
    ========================================================= */
    $(document).on('input', '.item-qty', function () {
        var $row = $(this).closest('tr');
        calcRowTotal($row); updateSummary();
        var pid = $row.find('.item-product').val();
        var vid = $row.find('.item-variant').val() || null;
        if (pid) refreshSiblingBadges(pid, vid || '');
    });

    $(document).on('input', '.item-price, .item-disc, .item-tax', function () {
        calcRowTotal($(this).closest('tr')); updateSummary();
    });

    $('#shipping_cost').on('input', updateSummary);

    $('#fill_paid_btn').on('click', function () {
        var total = parseFloat($('#summary-total').text()) || 0;
        $('#paid_input').val(total.toFixed(2));
    });

    /* =========================================================
       COUPON
    ========================================================= */
    function getSubtotalForCoupon() {
        var subtotal = 0;
        $('#items-body .item-row').each(function () {
            var $r   = $(this);
            var qty  = parseFloat($r.find('.item-qty').val())   || 0;
            var p    = parseFloat($r.find('.item-price').val()) || 0;
            var disc = parseFloat($r.find('.item-disc').val())  || 0;
            subtotal += qty * p - disc;
        });
        return subtotal;
    }

    $('#apply_coupon_btn').on('click', function () {
        var code = $.trim($('#coupon_code_input').val());
        if (!code) return;

        $.get(ajaxCouponUrl, {
            code:        code,
            subtotal:    getSubtotalForCoupon(),
            customer_id: getCustomerId(),
            order_id:    $('input[name="id"]').val() || '',
        }, function (res) {
            if (res.valid) {
                $('#coupon_id_input').val(res.coupon_id);
                $('#coupon_discount_input').val(res.discount);
                $('#coupon_feedback')
                    .text(res.message)
                    .removeClass('text-danger text-muted')
                    .addClass('text-success');
                $('#remove_coupon_btn').removeClass('d-none');
                updateSummary();
            } else {
                // Keep previously applied coupon intact; just show error
                $('#coupon_feedback')
                    .text(res.message)
                    .removeClass('text-success text-muted')
                    .addClass('text-danger');
            }
        }).fail(function () {
            $('#coupon_feedback')
                .text('خطأ في الاتصال بالخادم.')
                .removeClass('text-success text-muted')
                .addClass('text-danger');
        });
    });

    $('#coupon_code_input').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('#apply_coupon_btn').trigger('click'); }
    });

    $('#remove_coupon_btn').on('click', function () {
        $('#coupon_id_input').val('');
        $('#coupon_discount_input').val(0);
        $('#coupon_code_input').val('');
        $('#coupon_feedback').text('').removeClass('text-success text-danger').addClass('text-muted');
        $(this).addClass('d-none');
        updateSummary();
    });

    /* =========================================================
       OFFER
    ========================================================= */
    $('#offer_select').select2({
        dir: 'rtl', placeholder: 'ابحث عن عرض ...', allowClear: true,
        ajax: {
            url: ajaxOffersUrl, dataType: 'json', delay: 300,
            data: function (p) { return { search: p.term, page: p.page || 1 }; },
            processResults: function (d) { return { results: d.results, pagination: d.pagination }; },
            cache: true
        }
    });

    function collectItemsForOffer() {
        var items = [];
        $('#items-body .item-row').each(function () {
            var $r = $(this);
            var pid = $r.find('.item-product').val();
            if (!pid) return;
            items.push({
                product_id: pid,
                variant_id: $r.find('.item-variant').val() || '',
                quantity:   $r.find('.item-qty').val()   || 0,
                unit_price: $r.find('.item-price').val() || 0,
                discount:   $r.find('.item-disc').val()  || 0
            });
        });
        return items;
    }

    $('#apply_offer_btn').on('click', function () {
        var offerId = $('#offer_select').val();
        if (!offerId) {
            $('#offer_feedback').text('يرجى اختيار عرض أولاً.').removeClass('text-success text-muted').addClass('text-danger');
            return;
        }
        var items = collectItemsForOffer();
        if (!items.length) {
            $('#offer_feedback').text('أضف منتجات إلى الطلب أولاً.').removeClass('text-success text-muted').addClass('text-danger');
            return;
        }

        $.get(ajaxApplyOfferUrl, { offer_id: offerId, items: items }, function (res) {
            if (res.valid) {
                $('#offer_id_input').val(res.offer_id);
                $('#offer_discount_input').val(res.discount);
                $('#offer_feedback')
                    .text(res.message)
                    .removeClass('text-danger text-muted')
                    .addClass('text-success');
                $('#remove_offer_btn').removeClass('d-none');
                updateSummary();
            } else {
                $('#offer_feedback')
                    .text(res.message)
                    .removeClass('text-success text-muted')
                    .addClass('text-danger');
            }
        }).fail(function () {
            $('#offer_feedback')
                .text('خطأ في الاتصال بالخادم.')
                .removeClass('text-success text-muted')
                .addClass('text-danger');
        });
    });

    $('#remove_offer_btn').on('click', function () {
        $('#offer_id_input').val('');
        $('#offer_discount_input').val(0);
        $('#offer_select').val(null).trigger('change');
        $('#offer_feedback').text('').removeClass('text-success text-danger').addClass('text-muted');
        $(this).addClass('d-none');
        updateSummary();
    });

    $(document).on('click', '.remove-row', function () {
        var $row = $(this).closest('tr');
        var pid  = $row.find('.item-product').val();
        var vid  = $row.find('.item-variant').val() || '';
        $row.remove();
        updateSummary(); updateGlobalStockWarning();
        if (pid) refreshSiblingBadges(pid, vid);
    });

    /* Add empty row manually */
    $('#addRowBtn').on('click', function () {
        addEmptyRow();
    });

    /* =========================================================
       INIT EXISTING ROWS (edit mode)
    ========================================================= */
    @if(isset($data) && $data->items->count())
    var warehouseId = getWarehouseId();
    $('#items-body .item-row').each(function () {
        var $row = $(this);
        var pid  = $row.find('.item-product').val();
        var vid  = $row.find('.item-variant').val() || null;
        var uid  = $row.find('.item-unit').data('selected') || $row.find('.item-unit').val() || null;
        initProductSelect2ForRow($row);
        if (pid && warehouseId) fetchAndSetRowStock($row, pid, vid, warehouseId);
        if (pid) loadUnitsForRow($row, pid, vid, uid);
        calcRowTotal($row);
    });
    @else
    /* Init the default empty first row */
    initProductSelect2ForRow($('#items-body .item-row:first'));
    @endif

    /* Focus scanner on load */
    $('#scanner_input').focus();
    updateSummary();
});
</script>
@endsection
