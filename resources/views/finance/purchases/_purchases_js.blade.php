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

    // ── Supplier Select2 ─────────────────────
    $('#supplier_select').select2({
        theme: 'bootstrap-5',
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
            theme: 'bootstrap-5',
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
            loadVariants($(this).closest('tr'), e.params.data.id);
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
        var total = (qty * cost - disc) * (1 + tax / 100);
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
            var lineBase = qty * cost - disc;
            var lineTax  = lineBase * tax / 100;
            subtotal += qty * cost;
            discTotal += disc;
            taxTotal  += lineTax;
            total     += lineBase + lineTax;
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
                        name="items[${idx}][variant_id]">
                    <option value="">بدون متغير</option>
                </select>
            </td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-qty"
                       name="items[${idx}][quantity]" value="1" min="0.001" step="0.001"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-cost"
                       name="items[${idx}][unit_cost]" value="0" min="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-disc"
                       name="items[${idx}][discount]" value="0" min="0" step="0.01"></td>
            <td><input type="number" class="form-control form-control-solid form-control-sm item-tax"
                       name="items[${idx}][tax_rate]" value="15" min="0" max="100" step="0.01"></td>
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

    // ── Variant price fill ────────────────────
    $('#items-body').on('change', '.item-variant', function () {
        var price = $(this).find(':selected').data('price');
        if (price) {
            $(this).closest('tr').find('.item-cost').val(price).trigger('input');
        }
    });

    // ── Init existing rows (edit mode) ───────
    $('#items-body .item-row').each(function () {
        initProductSelect2($(this).find('.item-product'));
    });

    // ── Initial summary ───────────────────────
    updateSummary();
});
</script>
@endsection
