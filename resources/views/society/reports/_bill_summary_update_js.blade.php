{{-- Carried over from CakePHP app/View/AccountReports/comparison_2018closing_2019opening.ctp lines 181-421: only the helper calls changed --}}
<script type="text/javascript">
jQuery(function ($) {
    function updateSelectedCount() {
        var checked = $('.mismatch-checkbox:checked').length;
        $('#mismatch_update_bill_btn').prop('disabled', checked === 0);
    }

    $('#mismatch_select_all').on('change', function () {
        $('.mismatch-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    $('#mismatch_select_all_btn').on('click', function () {
        $('.mismatch-checkbox').prop('checked', true);
        $('#mismatch_select_all').prop('checked', true);
        updateSelectedCount();
    });

    $('.mismatch-checkbox').on('change', function () {
        if (!$(this).is(':checked')) {
            $('#mismatch_select_all').prop('checked', false);
        }
        updateSelectedCount();
    });

    $('#mismatch_update_bill_btn').on('click', function () {
        var memberIds = $('.mismatch-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (memberIds.length === 0) { return; }

        var $btn = $(this);
        var $status = $('#mismatch_fix_status');
        $btn.prop('disabled', true).text('Updating...');
        $status.html('');

        $.ajax({
            type: 'POST',
            url: @json(route('society.reports.fixBalanceMismatches')),
            data: { memberIds: memberIds },
            dataType: 'json',
            success: function (resp) {
                $btn.text('Update Bill');
                if (resp && resp.success) {
                    var failed = [];
                    var correctedCount = 0;
                    $.each(resp.results, function (memberId, r) {
                        if (r.success) { correctedCount++; } else { failed.push(memberId + ': ' + r.error); }
                    });
                    if (failed.length === 0) {
                        $status.html('<div class="alert alert-success">' + correctedCount + ' member' + (correctedCount === 1 ? '' : 's') + ' corrected. Reloading...</div>');
                        setTimeout(function () { location.reload(); }, 1800);
                    } else {
                        $status.html('<div class="alert alert-warning">' + correctedCount + ' member' + (correctedCount === 1 ? '' : 's') + ' corrected. Failed: ' + failed.join('; ') + '</div>');
                        updateSelectedCount();
                    }
                } else {
                    $status.html('<div class="alert alert-danger">' + (resp && resp.error ? resp.error : 'Update failed') + '</div>');
                    updateSelectedCount();
                }
            },
            error: function () {
                $btn.text('Update Bill');
                $status.html('<div class="alert alert-danger">Update failed - please try again.</div>');
                updateSelectedCount();
            }
        });
    });
});
</script>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {
    jQuery(function ($) {
        var previewedIds = [];

        $('#recon_select_all').on('change', function () {
            $('.recon-checkbox').prop('checked', $(this).is(':checked'));
        });

        function currentChecked() {
            return $('.recon-checkbox:checked').map(function () { return $(this).val(); }).get();
        }

        function allMemberIds() {
            return $('.recon-checkbox').map(function () { return $(this).val(); }).get();
        }

        function renderRow(memberId, r) {
            var name = $('.recon-checkbox[value="' + memberId + '"]').closest('label').text().trim();
            if (!r.success) {
                return '<tr><td>' + name + '</td><td colspan="9">' + r.error + '</td></tr>';
            }
            var c = r.current, n = r.computed;
            var diff = (n.balance_amount - c.balance_amount).toFixed(2);
            return '<tr data-member-id="' + memberId + '">' +
                '<td>' + name + '</td>' +
                '<td class="text-right">' + c.tax_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + c.interest_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + c.principal_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + c.balance_amount.toFixed(2) + '</td>' +
                '<td class="text-right">' + n.tax_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + n.interest_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + n.principal_balance.toFixed(2) + '</td>' +
                '<td class="text-right">' + n.balance_amount.toFixed(2) + '</td>' +
                '<td class="text-right" bgcolor="yellow">' + diff + '</td>' +
                '</tr>';
        }

        $('#recon_scan_all_btn').on('click', function () {
            var memberIds = allMemberIds();
            if (memberIds.length === 0) { return; }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Scanning...');
            $('#recon_preview_btn').prop('disabled', true);
            $('#recon_apply_btn').prop('disabled', true);
            $('#recon_status').html('');
            $('#recon_summary_counts').html('');

            $.ajax({
                type: 'POST',
                url: @json(route('society.reports.reconcilePreview')),
                data: { memberIds: memberIds },
                dataType: 'json',
                success: function (resp) {
                    $btn.prop('disabled', false).text('Check All & Select Wrong');
                    $('#recon_preview_btn').prop('disabled', false);
                    if (resp && resp.success) {
                        var rows = '';
                        var wrongCount = 0, correctCount = 0, failedCount = 0;
                        $.each(resp.results, function (memberId, r) {
                            rows += renderRow(memberId, r);
                            var $cb = $('.recon-checkbox[value="' + memberId + '"]');
                            if (!r.success) {
                                $cb.prop('checked', false);
                                failedCount++;
                                return;
                            }
                            var isWrong = Math.abs(r.computed.balance_amount - r.current.balance_amount) > 0.01;
                            $cb.prop('checked', isWrong);
                            if (isWrong) { wrongCount++; } else { correctCount++; }
                        });
                        $('#recon_results_tbody').html(rows);
                        previewedIds = memberIds.slice();
                        $('#recon_summary_counts').html(
                            '<span style="color:#3c763d;font-weight:bold;">' + correctCount + ' Correct</span>' +
                            '&nbsp;|&nbsp;<span style="color:#a94442;font-weight:bold;">' + wrongCount + ' Wrong</span>' +
                            (failedCount ? ('&nbsp;|&nbsp;<span style="color:#8a6d3b;font-weight:bold;">' + failedCount + ' Skipped (no bill this year)</span>') : '')
                        );
                        $('#recon_apply_btn').prop('disabled', wrongCount === 0);
                    } else {
                        $('#recon_status').html('<div class="alert alert-danger">' + (resp && resp.error ? resp.error : 'Scan failed') + '</div>');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Check All & Select Wrong');
                    $('#recon_preview_btn').prop('disabled', false);
                    $('#recon_status').html('<div class="alert alert-danger">Scan failed - please try again.</div>');
                }
            });
        });

        $('#recon_preview_btn').on('click', function () {
            var memberIds = currentChecked();
            if (memberIds.length === 0) { return; }
            var $btn = $(this);
            $btn.prop('disabled', true).text('Computing...');
            $('#recon_apply_btn').prop('disabled', true);
            $('#recon_status').html('');

            $.ajax({
                type: 'POST',
                url: @json(route('society.reports.reconcilePreview')),
                data: { memberIds: memberIds },
                dataType: 'json',
                success: function (resp) {
                    $btn.prop('disabled', false).text('Preview Reconciliation');
                    if (resp && resp.success) {
                        var rows = '';
                        $.each(resp.results, function (memberId, r) { rows += renderRow(memberId, r); });
                        $('#recon_results_tbody').html(rows);
                        previewedIds = memberIds.slice();
                        $('#recon_apply_btn').prop('disabled', false);
                    } else {
                        $('#recon_status').html('<div class="alert alert-danger">' + (resp && resp.error ? resp.error : 'Preview failed') + '</div>');
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Preview Reconciliation');
                    $('#recon_status').html('<div class="alert alert-danger">Preview failed - please try again.</div>');
                }
            });
        });

        $('#recon_apply_btn').on('click', function () {
            var memberIds = currentChecked();
            if (memberIds.length === 0) { return; }
            // Every checked member must have been part of the last preview/scan
            // response - a superset preview (e.g. "Check All") is fine, applying
            // to something never actually computed/shown is not.
            var notPreviewed = memberIds.filter(function (id) { return previewedIds.indexOf(id) === -1; });
            if (notPreviewed.length > 0) {
                $('#recon_status').html('<div class="alert alert-warning">Selection changed - please Preview again before confirming.</div>');
                return;
            }
            if (!window.confirm('This will permanently update the last bill balances for ' + memberIds.length + ' member(s). Continue?')) { return; }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Updating...');

            $.ajax({
                type: 'POST',
                url: @json(route('society.reports.reconcileApply')),
                data: { memberIds: memberIds },
                dataType: 'json',
                success: function (resp) {
                    $btn.text('Confirm & Update Selected');
                    if (resp && resp.success) {
                        var failed = [];
                        var okCount = 0;
                        $.each(resp.results, function (memberId, r) {
                            if (r.success) { okCount++; } else { failed.push(memberId + ': ' + r.error); }
                        });
                        var msg = okCount + ' member' + (okCount === 1 ? '' : 's') + ' updated.';
                        if (failed.length) {
                            $('#recon_status').html('<div class="alert alert-warning">' + msg + ' Failed: ' + failed.join('; ') + '</div>');
                        } else {
                            $('#recon_status').html('<div class="alert alert-success">' + msg + '</div>');
                        }
                        $btn.prop('disabled', true);
                        previewedIds = [];
                    } else {
                        $('#recon_status').html('<div class="alert alert-danger">' + (resp && resp.error ? resp.error : 'Update failed') + '</div>');
                    }
                },
                error: function () {
                    $btn.text('Confirm & Update Selected');
                    $('#recon_status').html('<div class="alert alert-danger">Update failed - please try again.</div>');
                }
            });
        });
    });
});
</script>