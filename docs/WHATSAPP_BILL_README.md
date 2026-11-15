# Send WhatsApp Bill (Laravel 13, PHP 8.4)

Port of the CakePHP screen `society_bills/send_bill_whatsapp` (Interakt). Same tables, statuses, counters, table and retry
behaviour; only the URLs are Laravel's.

| CakePHP | Laravel |
|---|---|
| society_bills/send_bill_whatsapp | GET /society/whatsapp-bill (top bar "WhatsApp Bill") |
| society_bills/whatsapp_send_batch | POST /society/whatsapp-bill/send |
| society_bills/whatsapp_batch_status | GET /society/whatsapp-bill/status |
| society_bills/whatsapp_retry_failed | POST /society/whatsapp-bill/retry |
| society_bills/whatsapp_batch_history | GET /society/whatsapp-bill/history |
| society_bills/whatsapp_webhook | POST /whatsapp/webhook (the Cake path /society_bills/whatsapp_webhook also works) |

Files: `app/Http/Controllers/Society/WhatsAppBillController.php`, `app/Services/WhatsApp/WhatsAppService.php`,
`config/whatsapp.php`, `resources/views/society/whatsapp/*`, migration `2026_11_12_000001_create_whatsapp_bill_tables.php`,
`tests/WhatsApp/WhatsAppServiceTest.php`.

## Enable
1. Tables (skipped if the CakePHP SQL already created them):
   `php artisan migrate --path=database/migrations/2026_11_12_000001_create_whatsapp_bill_tables.php`
2. `.env`: `WHATSAPP_API_KEY=` (the Interakt key from the Cake server's app/Config/whatsapp_local.php), optional
   `WHATSAPP_WEBHOOK_SECRET=`, `WHATSAPP_PDF_BASE_URL=` (public URL; Interakt must be able to download the PDF).
   Then `php artisan config:clear`.
3. Team logins need the `tb_whatsappbill` permission (view to open, generate to send / retry), as in Cake.

## Reseller-wise WhatsApp APIs (Admin > WhatsApp APIs)
- Admin sets, per reseller: WhatsApp number, provider (Interakt, or Meta Cloud API format for K3 Digital Media / Pinbot),
  API key, webhook secret, template name / variables, and for Meta format the API URL, phone number ID and auth header.
- A society's bills go out through its reseller's ACTIVE sender (reseller_societies -> reseller_whatsapp_senders);
  otherwise through the common API in .env (switch off with WHATSAPP_COMMON_FALLBACK=false). The send page shows which one.
- Keys are encrypted with APP_KEY (Laravel Crypt) and never shown again; keys saved by the CakePHP app cannot be read and
  must be typed in again. Only Admin can open the page.
- Table: `php artisan migrate --path=database/migrations/2026_11_12_000002_create_reseller_whatsapp_senders_table.php`
  (creates reseller_whatsapp_senders if missing, adds the new columns; nothing is dropped).
- The webhook accepts both Interakt and Meta (entry/changes/statuses) bodies and checks each message against its own
  reseller's webhook secret (Interakt-Signature or X-Hub-Signature-256, or K3's custom X-Webhook-Secret header).
  With a secret saved, a callback without any of these headers is refused with 401, so after saving a secret the
  provider's webhook must be set to send it; with no secret saved, callbacks are not verified.
- K3 / Pinbot: the Meta-format request is the standard WhatsApp Cloud API template message. Confirm the exact URL and auth
  header from the K3 panel's API docs before switching a reseller to ACTIVE.

## Notes
- The bill PDF is the carried-over `email_member_bills` view (`society.reports.member-bill-pdf`), built by the same
  report as Print Bill, saved in `public/tmp`.
- On localhost Interakt cannot download `http://127.0.0.1/...` PDFs, so real sends need a public URL. Without an API key
  every bill is marked FAILED "WhatsApp API key not configured." - nothing is sent.
- Small differences from Cake: the PDF follows Laravel's Print Bill logic (current owner's bill only - a previous
  owner's bill becomes PDF_FAILED); the member name / flat / phone in the table are HTML-escaped.
- Not ported yet: "WhatsApp Member Receipt" (whatsapp_member_receipts / whatsapp_send_receipts / whatsapp_receipt_pdf).
- Tests: `php vendor/bin/phpunit tests/WhatsApp` (pure logic, no DB / network).
