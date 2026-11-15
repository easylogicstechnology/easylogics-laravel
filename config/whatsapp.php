<?php

/*
 * WhatsApp bill sending through Interakt - the same settings as the CakePHP app's app/Config/whatsapp.php
 * (+ whatsapp_local.php). The API key and webhook secret are NOT kept here: set them in .env only.
 *
 * body_params = the approved template's {{1}} {{2}} ... in order (comma separated). Fields: member_name,
 * flat_no, amount, society_name, bill_month, bill_no, due_date. Empty = all seven in that order. The count
 * must match the template, else Interakt answers "Number of Body's variable values ... does not match".
 */
return [
    'enabled' => (bool) env('WHATSAPP_ENABLED', true),
    // The common (Admin's own) account. Each reseller can get its own account in Admin > WhatsApp APIs;
    // a society then sends through its reseller's account, and through this one only when the reseller has none.
    'provider' => env('WHATSAPP_PROVIDER', 'interakt'),   // interakt | k3 (K3 Digital Media / Pinbot) | meta (other Meta-format BSP)
    // false = a society whose reseller has no active API of its own cannot send at all
    'common_fallback' => (bool) env('WHATSAPP_COMMON_FALLBACK', true),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
    'auth_header' => env('WHATSAPP_AUTH_HEADER', ''),
    'wanumber' => env('WHATSAPP_WANUMBER', ''),   // k3: the WABA number, e.g. 919323451048
    'api_url' => env('WHATSAPP_API_URL', 'https://api.interakt.ai/v1/public/message/'),
    'api_key' => env('WHATSAPP_API_KEY', ''),
    'template_name' => env('WHATSAPP_TEMPLATE_NAME', 'maintenance_bill_notification'),
    'language_code' => env('WHATSAPP_LANGUAGE_CODE', 'en'),
    'country_code' => env('WHATSAPP_COUNTRY_CODE', '+91'),
    'body_params' => env('WHATSAPP_BODY_PARAMS', 'member_name,flat_no,bill_no,amount,due_date'),
    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),
    // Public base URL Interakt fetches the bill PDF from (it must reach it from the internet).
    // Empty = the app's own URL (APP_URL / the request host).
    'pdf_base_url' => env('WHATSAPP_PDF_BASE_URL', ''),
];
