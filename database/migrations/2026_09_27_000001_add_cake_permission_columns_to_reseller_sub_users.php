<?php

use App\Models\ResellerSubUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings reseller_sub_users up to the CakePHP schema (Neweasylogics_live): allowed_society_ids plus one
 * TINYINT(1) NOT NULL DEFAULT 0 column per {module}_{action} of ResellerSubUser::MODULES x PERM_ACTIONS
 * (the "Manage Users" permission grid). Cake's own scripts are app/Config/Schema/user_rights_production_deploy.sql,
 * add_sidebar_modules.sql, add_topbar_modules.sql, add_member_submenu_modules.sql, add_society_submenu_modules.sql.
 *
 * Only ever ADDS a column that is missing - nothing is dropped, renamed or changed, existing rows keep their values.
 * (The legacy, unused mm_memberidentity_* columns that exist in the Cake live table are not part of Cake's module
 * list and are not added.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reseller_sub_users')) {
            return;
        }

        $existing = array_flip(Schema::getColumnListing('reseller_sub_users'));

        $missing = array_values(array_filter(
            ResellerSubUser::permissionColumns(),
            fn (string $column) => !isset($existing[$column])
        ));
        $needsSocietyIds = !isset($existing['allowed_society_ids']);

        if (!$missing && !$needsSocietyIds) {
            return;
        }

        Schema::table('reseller_sub_users', function (Blueprint $table) use ($missing, $needsSocietyIds) {
            foreach ($missing as $column) {
                $table->boolean($column)->default(0);
            }
            if ($needsSocietyIds) {
                $table->text('allowed_society_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Additive, data-preserving migration: nothing to undo (the columns are part of the CakePHP schema).
    }
};
