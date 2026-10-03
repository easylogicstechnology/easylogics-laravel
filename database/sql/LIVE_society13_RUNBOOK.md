# LIVE `society13` — add the 44 missing tables (additive only)

Audit date 2026-10-03. LIVE has 96 tables, schema needs 140, so 44 are missing.
Rehearsed end-to-end on a local scratch DB (`zz_plan_rehearsal`: 96 tables -> 140, migrations table 5 rows, 0 diff).
NEVER run: `migrate:fresh`, `migrate:refresh`, `DROP TABLE`, any command against the CakePHP DB.

## Step 0 — Preconditions (do not skip)
1. Full backup of `society13` (phpMyAdmin -> Export -> Custom -> all tables, structure+data).
2. Confirm the live Laravel app really uses `society13`: check `DB_DATABASE` in the server `.env`.
   If it is anything else, STOP and re-audit.
3. Open question: `users`, `states`, `tenants`, `user_logins`, `wings`, `society_payments` are absent on LIVE.
   Decide whether LIVE login works today (and from which DB) BEFORE creating an empty `users` table.
4. Confirm count before: `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='society13' AND table_type='BASE TABLE';` -> 96.

## Order matters (foreign keys)
Group 3 (base tables) -> Group 2 (vendor_*, whatsapp, interest) -> Group 1 (artisan migrate).
`tds_deductees` -> `vendor_details`; `tds_sections` -> `society_ledger_heads`; `gst_*` -> `vendor_bill_details`.

## Step 1 — Group 3: 25 base tables (structure only, no data)
Run in phpMyAdmin (database `society13` selected) -> Import, or paste into SQL tab:
`database/sql/group3_missing_tables.sql`
- `CREATE TABLE IF NOT EXISTS` only; no DROP/INSERT. Idempotent (re-run tested).
- It relaxes `sql_mode` for the session only: `user_logins.time` has a `0000-00-00` default that strict mode rejects.
- Tables come out EMPTY. `states`, `society_parameters`, `society_payments`, etc. may need data — separate decision.
- Check: count -> 121.

## Step 2 — Group 2: 7 tables from Cake SQL scripts (in `easylogicstechnology/app/Config/Schema/`)
Run in this order, each with IF NOT EXISTS, no DROP/INSERT (verified by grep):
1. `vendor_billing_tables.sql`   (vendor_details, vendor_facilities, vendor_bills, vendor_bill_details)
2. `whatsapp_tables.sql`        (whatsapp_bill_logs, whatsapp_bill_batches)
3. `society_month_interest_rates.sql`
- Check: count -> 128.

## Step 3 — Group 1: 12 tables via Laravel migrations (4 gst, 7 tds, user_permissions)
On the server (PHP 8.4), in the Laravel root:
```
php artisan migrate --pretend      # review the SQL first
php artisan migrate                # NOT --force-fresh; plain migrate only
```
- Expected: 5 migrations run (batch 1), creating `user_permissions`, 7 `tds_*`, 4 `gst_*` (the 8 existing gst tables are skipped by hasTable guards).
- Also adds `can_update` to `user_permissions` and Cake permission columns to `reseller_sub_users` (existing table; column-guarded).
- Check: count -> 140; `SELECT * FROM migrations;` -> 5 rows.

## Step 4 — Verify
- Count = 140. Re-run the 44-name missing query: must return 0 rows.
- Smoke test: login, TDS screen, GST screen, vendor bill screen, WhatsApp/email bill screen.

## Rollback
Only the new tables were created, so rollback = restore the Step 0 backup (or drop just the newly created tables).
Existing 96 tables are never altered, except `reseller_sub_users` (added columns) in Step 3.
