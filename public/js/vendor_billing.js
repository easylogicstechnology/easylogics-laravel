/* Vendor Detail & Vendor Billing (port of CakePHP webroot/js/vendor_billing.js).
 *
 * Opened from the Vendor Detail / Vendor Billing list pages, and from the "+" next to Select Vendor / Bill
 * Particulars for creating a brand new ledger head directly. The three modals (vendor_detail_modal,
 * vendor_billing_modal, quick_ledger_head_modal) are never stacked: swapModal() always closes whichever modal
 * is open before showing the next one, so at most one is ever visible.
 *
 * Endpoint URLs come from window.vendorUrls (set by society/vendor/_modals.blade.php).
 */
(function ($) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function showModal(sel) { $(sel).css('display', 'flex'); }
    function hideModal(sel) { $(sel).css('display', 'none'); }

    window.swapModal = function (hideSelector, showSelector) {
        if (hideSelector) { hideModal(hideSelector); }
        showModal(showSelector);
    };

    function toast(message) {
        var el = $('<div style="position:fixed;top:70px;right:20px;z-index:3000;background:#27ae60;color:#fff;padding:10px 16px;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.3);font-size:14px;"></div>').text(message);
        $('body').append(el);
        setTimeout(function () { el.fadeOut(300, function () { el.remove(); }); }, 2500);
    }

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    window.showFormError = function (containerId, message) {
        $('#' + containerId).html('<div class="alert alert-error">' + esc(message) + '</div>');
    };
    window.clearFormError = function (containerId) { $('#' + containerId).html(''); };

    /* ---------------------------- Vendor Detail ---------------------------- */
    // Set right before opening Vendor Detail from Billing's "+ Add New Vendor", so saveVendorDetail() knows to hand
    // control back to the Billing modal afterwards.
    var vendorDetailReturnToBilling = false;
    // The modal (if any) that was hidden to open Vendor Detail, so Cancel/x can bring it back.
    var vendorDetailOpenedFrom = null;

    window.openVendorDetailModal = function (ledgerHeadId, hideSelector) {
        vendorDetailOpenedFrom = hideSelector || null;
        $.ajax({
            type: 'POST', url: vendorUrls.detailForm, data: { ledgerHeadId: ledgerHeadId || 0 }, dataType: 'html',
            success: function (resp) {
                $('#vendor_detail_modal_body').html(resp);
                swapModal(hideSelector, '#vendor_detail_modal');
            },
            error: function () { alert('Could not load Vendor Detail. Please try again.'); }
        });
    };

    window.cancelVendorDetailModal = function () {
        var from = vendorDetailOpenedFrom;
        vendorDetailOpenedFrom = null;
        vendorDetailReturnToBilling = false;
        hideModal('#vendor_detail_modal');
        if (from) { showModal(from); }
    };

    window.onVendorLedgerHeadChange = function (value) {
        if (value === 'new') {
            quickLedgerHeadTarget = 'vendor';
            openQuickLedgerHeadModal('#vendor_detail_modal');
        }
    };

    window.onVendorFacilitySelectChange = function (value) {
        if (value === 'new') {
            $('#vendor_facility_select').hide();
            $('#vendor_facility_text').val('').show().focus();
        }
    };

    // Facility "+" must create a real vendor_facilities row, so the textbox commits via AJAX find-or-create on blur.
    $(document).on('blur', '#vendor_facility_text', function () {
        var title = $(this).val();
        var self = $(this);
        if (!title) { $('#vendor_facility_select').show(); self.hide(); return; }
        $.ajax({
            type: 'POST', url: vendorUrls.saveFacility, data: { title: title }, dataType: 'json',
            success: function (resp) {
                if (resp.error === 0) {
                    $('#vendor_facility_select').append('<option value="' + resp.id + '" selected>' + esc(resp.title) + '</option>');
                    $('#vendor_facility_select').val(resp.id).show();
                    self.hide();
                }
            }
        });
    });

    window.saveVendorDetail = function () {
        clearFormError('vendor_detail_notify_error');
        $.ajax({
            type: 'POST', url: vendorUrls.saveDetail, data: $('#vendorDetailForm').serialize(), dataType: 'json',
            success: function (resp) {
                if (resp.error === 0) {
                    toast(resp.error_message);
                    if (vendorDetailReturnToBilling) {
                        vendorDetailReturnToBilling = false;
                        refreshVendorDropdownAndSelect(resp.ledger_head_id);
                    } else {
                        openVendorDetailModal(resp.ledger_head_id);
                    }
                } else {
                    showFormError('vendor_detail_notify_error', resp.error_message);
                }
            },
            error: function () { showFormError('vendor_detail_notify_error', 'Could not save. Please try again.'); }
        });
    };

    /* --------------------------- Vendor Billing ----------------------------- */
    var vendorBillingOpenedFrom = null;

    window.openVendorBillingModal = function (vendorBillId, hideSelector) {
        vendorBillingOpenedFrom = hideSelector || null;
        $.ajax({
            type: 'POST', url: vendorUrls.billingForm, data: { vendorBillId: vendorBillId || 0 }, dataType: 'html',
            success: function (resp) {
                $('#vendor_billing_modal_body').html(resp);
                swapModal(hideSelector, '#vendor_billing_modal');
                recalculateVendorBillingTotals();
            },
            error: function () { alert('Could not load Vendor Billing. Please try again.'); }
        });
    };

    window.cancelVendorBillingModal = function () {
        var from = vendorBillingOpenedFrom;
        vendorBillingOpenedFrom = null;
        hideModal('#vendor_billing_modal');
        if (from) { showModal(from); }
    };

    window.onBillingVendorChange = function (value) {
        if (value === 'new') {
            vendorDetailReturnToBilling = true;
            openVendorDetailModal(0, '#vendor_billing_modal');
        }
    };

    window.refreshVendorDropdownAndSelect = function (newVendorId) {
        $.ajax({
            type: 'POST', url: vendorUrls.vendorList, dataType: 'html',
            success: function (resp) {
                $('#vendor_bill_vendor_id').html(resp);
                $('#vendor_bill_vendor_id').val(newVendorId);
                swapModal('#vendor_detail_modal', '#vendor_billing_modal');
            }
        });
    };

    window.onBillParticularChange = function (selectEl) {
        if ($(selectEl).val() === 'new') {
            quickLedgerHeadTarget = $(selectEl).closest('tr');
            openQuickLedgerHeadModal('#vendor_billing_modal');
        }
    };

    window.addVendorBillingLine = function () {
        var index = $('#vendor_billing_lines_container tr').length;
        var optionsHtml = window.billParticularOptionsHtml || '<option value="">Select</option>';
        var row = '<tr class="vendor-billing-line">' +
            '<td style="min-width:180px;"><select class="form-control vendor-line-particular" name="VendorBillDetail[' + index + '][ledger_head_id]" onchange="onBillParticularChange(this);">' + optionsHtml + '</select></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-amount" name="VendorBillDetail[' + index + '][amount]" placeholder="0.00"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-sgst-rate" name="VendorBillDetail[' + index + '][sgst_rate]" placeholder="%"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-sgst-amount" readonly value="0.00"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-cgst-rate" name="VendorBillDetail[' + index + '][cgst_rate]" placeholder="%"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-cgst-amount" readonly value="0.00"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-igst-rate" name="VendorBillDetail[' + index + '][igst_rate]" placeholder="%"></td>' +
            '<td><input type="text" class="form-control text-right vendor-line-igst-amount" readonly value="0.00"></td>' +
            '<td><input type="text" class="form-control vendor-line-hsn" name="VendorBillDetail[' + index + '][hsn_sac]"></td>' +
            '<td><button type="button" class="btn btn-danger btn-sm btn-remove-vendor-line">&times;</button></td>' +
            '</tr>';
        $('#vendor_billing_lines_container').append(row);
    };

    // Rows are added after page load, so bind on the document rather than on individual rows/inputs.
    $(document).on('click', '.btn-remove-vendor-line', function () {
        $(this).closest('tr').remove();
        recalculateVendorBillingTotals();
    });

    function calculateVendorBillingLine(row) {
        var amount = parseFloat(row.find('.vendor-line-amount').val()) || 0;
        var sgstRate = parseFloat(row.find('.vendor-line-sgst-rate').val()) || 0;
        var cgstRate = parseFloat(row.find('.vendor-line-cgst-rate').val()) || 0;
        var igstRate = parseFloat(row.find('.vendor-line-igst-rate').val()) || 0;
        row.find('.vendor-line-sgst-amount').val((amount * sgstRate / 100).toFixed(2));
        row.find('.vendor-line-cgst-amount').val((amount * cgstRate / 100).toFixed(2));
        row.find('.vendor-line-igst-amount').val((amount * igstRate / 100).toFixed(2));
    }

    window.recalculateVendorBillingTotals = function () {
        var total = 0;
        $('#vendor_billing_lines_container tr').each(function () {
            var row = $(this);
            calculateVendorBillingLine(row);
            total += parseFloat(row.find('.vendor-line-amount').val()) || 0;
            total += parseFloat(row.find('.vendor-line-sgst-amount').val()) || 0;
            total += parseFloat(row.find('.vendor-line-cgst-amount').val()) || 0;
            total += parseFloat(row.find('.vendor-line-igst-amount').val()) || 0;
        });
        var tdsPercent = parseFloat($('#vendor_bill_tds_percent').val()) || 0;
        var deduct = parseFloat($('#vendor_bill_deduct').val()) || 0;
        var tdsAmount = total * tdsPercent / 100;
        var totalBillBeforeRound = total - tdsAmount - deduct;
        var totalBillRounded = Math.round(totalBillBeforeRound);
        var roundOff = totalBillRounded - totalBillBeforeRound;
        $('#vendor_bill_total').val(total.toFixed(2));
        $('#vendor_bill_tds_amount').val(tdsAmount.toFixed(2));
        $('#vendor_bill_total_bill').val(totalBillRounded.toFixed(2));
        $('#vendor_bill_round_off').val(roundOff.toFixed(2));
    };

    $(document).on('input change keyup', '.vendor-line-amount, .vendor-line-sgst-rate, .vendor-line-cgst-rate, .vendor-line-igst-rate, #vendor_bill_tds_percent, #vendor_bill_deduct', window.recalculateVendorBillingTotals);

    window.saveVendorBill = function (andPrint) {
        clearFormError('vendor_billing_notify_error');
        $.ajax({
            type: 'POST', url: vendorUrls.saveBill, data: $('#vendorBillingForm').serialize(), dataType: 'json',
            success: function (resp) {
                if (resp.error === 0) {
                    toast('Vendor bill saved successfully.');
                    openVendorBillingModal(resp.id);
                    if (andPrint) { printVendorBill(resp.id); }
                } else {
                    showFormError('vendor_billing_notify_error', resp.error_message);
                }
            },
            error: function () { showFormError('vendor_billing_notify_error', 'Could not save. Please try again.'); }
        });
    };

    window.printVendorBill = function (vendorBillId) {
        if (!vendorBillId) { vendorBillId = $('#vendor_bill_id').val(); }
        if (!vendorBillId) { alert('Please save the bill first.'); return; }
        window.open(vendorUrls.printBill.replace('__ID__', vendorBillId), '_blank');
    };

    /* ------------------------ Quick Add Ledger Head ------------------------- */
    // Where to feed the newly created ledger head id: 'vendor' (the picker inside Vendor Detail), or a specific
    // billing-line <tr> jQuery object.
    var quickLedgerHeadTarget = null;
    var quickLedgerHeadOpenedFrom = null;

    window.openQuickLedgerHeadModal = function (hideSelector) {
        quickLedgerHeadOpenedFrom = hideSelector || null;
        $('#quickLedgerHeadForm')[0].reset();
        $('#quick_ledger_category_id').html('<option value="">Select Subgroup first</option>').prop('disabled', true);
        $('#quick_ledger_head_id').html('<option value="">Select Subgroup first</option>').prop('disabled', true);
        clearFormError('quick_ledger_head_notify_error');
        $.ajax({
            type: 'POST', url: vendorUrls.subCategories, dataType: 'html',
            success: function (resp) { $('#quick_ledger_sub_category_id').html('<option value="">Select</option>' + resp); }
        });
        swapModal(hideSelector, '#quick_ledger_head_modal');
    };

    window.cancelQuickLedgerHead = function () {
        var from = quickLedgerHeadOpenedFrom;
        quickLedgerHeadOpenedFrom = null;
        quickLedgerHeadTarget = null;
        hideModal('#quick_ledger_head_modal');
        if (from) { showModal(from); }
    };

    window.onQuickLedgerSubCategoryChange = function (headSubCategoryId) {
        if (!headSubCategoryId) { return; }
        $.ajax({
            type: 'POST', url: vendorUrls.accountCategories, data: { headSubCategoryId: headSubCategoryId }, dataType: 'html',
            success: function (resp) { $('#quick_ledger_category_id').html(resp).prop('disabled', false); }
        });
        $.ajax({
            type: 'POST', url: vendorUrls.accountHeads, data: { headSubCategoryId: headSubCategoryId }, dataType: 'html',
            success: function (resp) { $('#quick_ledger_head_id').html(resp).prop('disabled', false); }
        });
    };

    window.saveQuickLedgerHead = function () {
        clearFormError('quick_ledger_head_notify_error');
        var postData = {
            'SocietyLedgerHeads[title]': $('#quick_ledger_title').val(),
            'SocietyLedgerHeads[short_code]': $('#quick_ledger_short_code').val(),
            'SocietyLedgerHeads[opening_amount]': $('#quick_ledger_opening_amount').val(),
            'SocietyLedgerHeads[society_head_sub_category_id]': $('#quick_ledger_sub_category_id').val(),
            'SocietyLedgerHeads[account_category_id]': $('#quick_ledger_category_id').val(),
            'SocietyLedgerHeads[account_head_id]': $('#quick_ledger_head_id').val()
        };
        if (!postData['SocietyLedgerHeads[title]'] || !postData['SocietyLedgerHeads[society_head_sub_category_id]']) {
            showFormError('quick_ledger_head_notify_error', 'Particulars and Subgroup are required.');
            return;
        }
        $.ajax({
            type: 'POST', url: vendorUrls.saveLedgerHeadInline, data: postData, dataType: 'json',
            success: function (resp) {
                if (resp.error === 0) {
                    if (quickLedgerHeadTarget === 'vendor') {
                        $('#vendor_ledger_head_id').append('<option value="' + resp.id + '" selected>' + esc(resp.title) + '</option>');
                        $('#vendor_ledger_head_id').val(resp.id);
                        quickLedgerHeadTarget = null;
                        swapModal('#quick_ledger_head_modal', '#vendor_detail_modal');
                    } else if (quickLedgerHeadTarget && quickLedgerHeadTarget.jquery) {
                        // A specific billing line row - refresh only that row.
                        var row = quickLedgerHeadTarget;
                        quickLedgerHeadTarget = null;
                        $.ajax({
                            type: 'POST', url: vendorUrls.particularHeads, dataType: 'html',
                            success: function (optionsResp) {
                                window.billParticularOptionsHtml = '<option value="">Select</option>' + optionsResp;
                                row.find('.vendor-line-particular').html(optionsResp).val(resp.id);
                                swapModal('#quick_ledger_head_modal', '#vendor_billing_modal');
                            }
                        });
                    } else {
                        quickLedgerHeadTarget = null;
                        hideModal('#quick_ledger_head_modal');
                    }
                } else {
                    showFormError('quick_ledger_head_notify_error', resp.error_message);
                }
            },
            error: function () { showFormError('quick_ledger_head_notify_error', 'Could not save. Please try again.'); }
        });
    };
})(jQuery);
