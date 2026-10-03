<?php

namespace App\Support;

/**
 * Which society sidebar sections / items a reseller's team login may see (CakePHP MenuComponent, the block that
 * runs when Auth.sub_reseller is set). The reseller itself and a real Society login always see everything.
 */
class SocietyMenuAccess
{
    /** sidebar section => permission module (its "view" flag) */
    private const SECTION_MODULE = [
        'Society' => 'sb_society',
        'Member' => 'sb_member',
        'Registers' => 'sb_registers',
        'Employee' => 'sb_employee',
        'Send Sms' => 'sb_sms',
        'Reports' => 'sb_reports',
        'TDS' => 'sb_tds',
        'GST' => 'sb_gst',
        'Utilities' => 'sb_utilities',
        'Bill Print' => 'sb_billprint',
    ];

    /** the sections that also have a permission row per item, matched by the item's label */
    private const ITEM_MODULE = [
        'Member' => [
            'Member Tariff' => 'mm_membertariff',
            'Member Payments' => 'mm_memberpayments',
            'Import Member Payments' => 'mm_importmemberpayments',
            'Import Society Payments' => 'mm_importsocietypayments',
            'All Generated Bills' => 'mm_allgeneratedbills',
        ],
        'Society' => [
            'Society Parameters' => 'sm_societyparameters',
            'Society Ledger Heads' => 'sm_societyledgerheads',
            'Society Payments' => 'sm_societypayments',
            'Bank Reconciliation' => 'sm_bankreconciliation',
            'Society Cash Contra' => 'sm_societycashcontra',
        ],
    ];

    private static function granted(string $module): bool
    {
        return ResellerContext::can($module, 'view');
    }

    /**
     * A section is dropped only when its own box AND every one of its item rows are unticked - granting just
     * "Member Payments" is enough to see the Member section.
     */
    public static function section(string $section): bool
    {
        if (empty(session('sub_reseller'))) {
            return true;
        }

        if (self::granted(self::SECTION_MODULE[$section])) {
            return true;
        }

        foreach (self::ITEM_MODULE[$section] ?? [] as $module) {
            if (self::granted($module)) {
                return true;
            }
        }

        return false;
    }

    /**
     * An item with a row of its own follows that row; any other item of Member / Society (e.g. "Building
     * Identity") shows only when the section's own box is ticked. Items of the other sections just follow the section.
     */
    public static function item(string $section, string $label): bool
    {
        if (empty(session('sub_reseller'))) {
            return true;
        }

        $items = self::ITEM_MODULE[$section] ?? null;
        if ($items === null) {
            return self::section($section);
        }

        $module = $items[trim($label)] ?? null;

        return $module !== null ? self::granted($module) : self::granted(self::SECTION_MODULE[$section]);
    }

    /** Top menu bar button ("tb_*" module) */
    public static function topBar(string $module): bool
    {
        return self::granted($module);
    }
}
