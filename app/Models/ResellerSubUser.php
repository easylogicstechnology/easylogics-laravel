<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A reseller's team login (CakePHP: ResellerSubUser, table reseller_sub_users). One row per sub-user,
 * one 0/1 column per {module}_{action}. created/modified are set by the controller, like Cake does.
 */
class ResellerSubUser extends Model
{
    protected $table = 'reseller_sub_users';

    public $timestamps = false;

    protected $guarded = [];

    /** ResellersController::$modules */
    public const MODULES = [
        'dashboard', 'software', 'members', 'bills', 'payments', 'settlement', 'reports', 'settings', 'bill_generated',
        'sb_society', 'sb_member', 'sb_registers', 'sb_employee', 'sb_sms', 'sb_reports', 'sb_tds', 'sb_gst', 'sb_utilities', 'sb_billprint',
        'tb_ledgerheads', 'tb_flatshop', 'tb_bill', 'tb_memberreceipts', 'tb_bulkpaste', 'tb_paymententry', 'tb_generatebill', 'tb_printbill', 'tb_emailbill', 'tb_whatsappbill', 'tb_sendsms',
        'mm_membertariff', 'mm_memberpayments', 'mm_importmemberpayments', 'mm_importsocietypayments', 'mm_allgeneratedbills',
        'sm_societyparameters', 'sm_societyledgerheads', 'sm_societypayments', 'sm_bankreconciliation', 'sm_societycashcontra',
    ];

    /** ResellersController::$moduleLabels (Permissions page only; other modules get ucwords(str_replace('_',' ',...))) */
    public const MODULE_LABELS = [
        'bill_generated' => 'Bill Generated',
        'sb_society' => 'Society (in-society sidebar)',
        'sb_member' => 'Member (in-society sidebar)',
        'sb_registers' => 'Registers (in-society sidebar)',
        'sb_employee' => 'Employee (in-society sidebar)',
        'sb_sms' => 'Send Sms (in-society sidebar)',
        'sb_reports' => 'Reports (in-society sidebar)',
        'sb_tds' => 'TDS (in-society sidebar)',
        'sb_gst' => 'GST (in-society sidebar)',
        'sb_utilities' => 'Utilities (in-society sidebar)',
        'sb_billprint' => 'Bill Print (in-society sidebar)',
        'tb_ledgerheads' => 'Ledger Heads (in-society top menu)',
        'tb_flatshop' => 'Flat / Shop Detail (in-society top menu)',
        'tb_bill' => 'Bill (in-society top menu)',
        'tb_memberreceipts' => 'Member Receipts (in-society top menu)',
        'tb_bulkpaste' => 'Bulk Paste (in-society top menu)',
        'tb_paymententry' => 'Payment Entry (in-society top menu)',
        'tb_generatebill' => 'Generate Bill (in-society top menu)',
        'tb_printbill' => 'Print Bill (in-society top menu)',
        'tb_emailbill' => 'Email Bill (in-society top menu)',
        'tb_whatsappbill' => 'WhatsApp Bill (in-society top menu)',
        'tb_sendsms' => 'Send SMS (in-society top menu)',
        'mm_membertariff' => 'Member Tariff (in Member submenu)',
        'mm_memberpayments' => 'Member Payments (in Member submenu)',
        'mm_importmemberpayments' => 'Import Member Payments (in Member submenu)',
        'mm_importsocietypayments' => 'Import Society Payments (in Member submenu)',
        'mm_allgeneratedbills' => 'All Generated Bills (in Member submenu)',
        'sm_societyparameters' => 'Society Parameters (in Society submenu)',
        'sm_societyledgerheads' => 'Society Ledger Heads (in Society submenu)',
        'sm_societypayments' => 'Society Payments (in Society submenu)',
        'sm_bankreconciliation' => 'Bank Reconciliation (in Society submenu)',
        'sm_societycashcontra' => 'Society Cash Contra (in Society submenu)',
    ];

    /** ResellersController::$permActions */
    public const PERM_ACTIONS = ['add', 'edit', 'delete', 'generate', 'update', 'view'];

    /** Modules shown on the Edit User screen (edit_user.ctp defines its own, shorter list). */
    public const EDIT_SCREEN_MODULES = [
        'dashboard', 'software', 'members', 'bills', 'payments', 'settlement', 'reports', 'settings', 'bill_generated',
    ];

    /** @return string[] every {module}_{action} column, in Cake's loop order */
    public static function permissionColumns(): array
    {
        $columns = [];
        foreach (self::MODULES as $module) {
            foreach (self::PERM_ACTIONS as $action) {
                $columns[] = $module . '_' . $action;
            }
        }

        return $columns;
    }
}
