<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp bill sending through Interakt - the helpers of CakePHP SocietyBillsController
 * (callWhatsAppApi, _validatePhone, _whatsappBodyParams, _whatsappBodyParamSafe, _generateBatchId,
 * _updateBatchCounts, _whatsappWebhookSignatureOk), with the same rules and the same answers.
 */
class WhatsAppService
{
    /** Bill log statuses that "Retry Failed" sends again. */
    public const RETRYABLE = ['FAILED', 'API_LIMIT_REACHED', 'PENDING'];

    /** Webhook status order: a lower status never overwrites a higher one (FAILED always wins). */
    public const STATUS_PRIORITY = [
        'PENDING' => 0, 'SENT' => 1, 'DELIVERED' => 2, 'READ' => 3,
        'FAILED' => -1, 'API_LIMIT_REACHED' => -2, 'INVALID_PHONE' => -3, 'PDF_FAILED' => -4,
    ];

    public function __construct(private array $config = [])
    {
        $this->config = $config ?: (array) config('whatsapp', []);
    }

    /** The service for a society's bills: its reseller's own sender when Admin set one up, else the common API. */
    public static function forSociety(int $societyUserId): self
    {
        return new self(WhatsAppSenders::configForSociety($societyUserId));
    }

    public function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Cake _validatePhone(): ['valid' => true, 'phone' => '91XXXXXXXXXX'] or ['valid' => false, 'error' => ..].
     */
    public static function validatePhone($phone): array
    {
        $phone = (string) $phone;
        if (trim($phone) === '' || trim($phone) === '-') {
            return ['valid' => false, 'error' => 'No phone number'];
        }
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleaned) < 10) {
            return ['valid' => false, 'error' => 'Phone number too short'];
        }
        if (strlen($cleaned) == 11 && $cleaned[0] === '0') {
            return ['valid' => false, 'error' => 'Landline number'];
        }
        if (strlen($cleaned) == 10) {
            $cleaned = '91' . $cleaned;
        }
        if (strlen($cleaned) == 12 && substr($cleaned, 0, 2) !== '91') {
            return ['valid' => false, 'error' => 'Non-Indian number'];
        }

        return ['valid' => true, 'phone' => $cleaned];
    }

    /** Cake _whatsappBodyParamSafe(): no tabs/newlines and never more than 2 spaces in a row. */
    public static function bodyParamSafe($value): string
    {
        $value = str_replace(["\t", "\r", "\n"], ' ', (string) $value);
        $value = preg_replace('/ {3,}/', '  ', $value);

        return trim($value);
    }

    /**
     * Cake _whatsappBodyParams(): the template's body values in the order of whatsapp.body_params.
     *
     * @param  array  $log  member_name, flat_no, amount, bill_month, bill_no, due_date
     */
    public function bodyParams(array $log, string $societyName): array
    {
        $fields = [
            'member_name' => $log['member_name'] ?? '',
            'flat_no' => $log['flat_no'] ?? '',
            'amount' => number_format((float) ($log['amount'] ?? 0), 2),
            'society_name' => $societyName,
            'bill_month' => $log['bill_month'] ?? '',
            'bill_no' => (string) ($log['bill_no'] ?? ''),
            'due_date' => $log['due_date'] ?? '',
            // "April 2026" - for a template like "Here is your Maintenance Bill for {{1}}" (K3 maintainanence_bill)
            'bill_month_name' => self::monthName($log['bill_month'] ?? '', $log['bill_year'] ?? ''),
        ];
        $fields = array_map([self::class, 'bodyParamSafe'], $fields);

        $wanted = trim((string) ($this->config['body_params'] ?? ''));
        $cakeSeven = array_diff_key($fields, ['bill_month_name' => 1]);
        if ($wanted === '') {
            return array_values($cakeSeven);
        }
        $params = [];
        foreach (explode(',', $wanted) as $name) {
            $name = trim($name);
            if (array_key_exists($name, $fields)) {
                $params[] = $fields[$name];
            }
        }

        return empty($params) ? array_values($cakeSeven) : $params;
    }

    /** "April 2026" from bill month 4 and year 2026 ("April" without a year); anything else is returned as it is. */
    public static function monthName($month, $year = ''): string
    {
        $m = (int) $month;
        if ($m < 1 || $m > 12 || (string) $m !== trim((string) $month)) {
            return (string) $month;
        }
        $name = date('F', mktime(0, 0, 0, $m, 1, 2000));

        return trim($name . ' ' . $year);
    }

    public static function generateBatchId(int $societyId, string $prefix = 'WA'): string
    {
        return $prefix . '_' . $societyId . '_' . date('Ymd_His') . '_' . strtoupper(substr(md5((string) mt_rand()), 0, 4));
    }

    /** Interakt payload of Cake callWhatsAppApi() (phone without the country code; PDF as the header document). */
    public function payload(string $phone, array $bodyValues, string $pdfUrl = '', $billNo = '', array $options = []): array
    {
        $localPhone = $phone;
        if (strlen($localPhone) == 12 && substr($localPhone, 0, 2) === '91') {
            $localPhone = substr($localPhone, 2);
        }

        $payload = [
            'countryCode' => $this->config['country_code'] ?? '+91',
            'phoneNumber' => $localPhone,
            'callbackData' => $options['callback'] ?? 'bill_send',
            'type' => 'Template',
            'template' => [
                'name' => !empty($options['template_name']) ? $options['template_name'] : ($this->config['template_name'] ?? ''),
                'languageCode' => $this->config['language_code'] ?? 'en',
                'bodyValues' => array_values($bodyValues),
            ],
        ];
        if ($pdfUrl !== '') {
            $payload['template']['headerValues'] = [$pdfUrl];
            $payload['template']['fileName'] = !empty($options['file_name'])
                ? $options['file_name']
                : ((string) $billNo !== '' ? 'Bill_' . $billNo . '.pdf' : 'Bill.pdf');
        }

        return $payload;
    }

    /**
     * Cake callWhatsAppApi(): success / rate_limited / auth_failed / message_id / error / response.
     */
    public function send(string $phone, array $bodyValues, string $pdfUrl = '', $billNo = '', array $options = []): array
    {
        if (empty($this->config['enabled'])) {
            return ['success' => false, 'rate_limited' => false, 'error' => 'WhatsApp API is disabled in configuration.', 'response' => null];
        }
        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '' || in_array($apiKey, ['REPLACE_WITH_YOUR_INTERAKT_API_KEY', 'sandbox_test_key_placeholder'], true)) {
            return ['success' => false, 'rate_limited' => false, 'error' => 'WhatsApp API key not configured.', 'response' => null];
        }

        $isMeta = $this->isMetaFormat();
        if ($isMeta && $this->endpoint() === '') {
            return ['success' => false, 'rate_limited' => false, 'error' => 'WhatsApp API URL not configured for this sender.', 'response' => null];
        }
        if (($this->config['provider'] ?? '') === 'k3' && trim((string) ($this->config['wanumber'] ?? '')) === '') {
            return ['success' => false, 'rate_limited' => false, 'error' => 'K3 WABA number (wanumber) not configured for this sender.', 'response' => null];
        }
        $payload = $isMeta
            ? $this->metaPayload($phone, $bodyValues, $pdfUrl, $billNo, $options)
            : $this->payload($phone, $bodyValues, $pdfUrl, $billNo, $options);

        try {
            $res = Http::withHeaders($this->headers($apiKey))
                ->acceptJson()
                ->timeout(30)
                ->post($this->endpoint(), $payload);
        } catch (\Throwable $e) {
            return ['success' => false, 'rate_limited' => false, 'error' => 'cURL error: ' . $e->getMessage(), 'response' => null];
        }

        $httpCode = $res->status();
        $body = (string) $res->body();
        $decoded = json_decode($body, true);

        if ($httpCode === 429) {
            return [
                'success' => false, 'rate_limited' => true, 'httpCode' => $httpCode, 'response' => $decoded,
                'error' => is_array($decoded) && isset($decoded['message']) ? $decoded['message'] : 'Rate limit exceeded',
            ];
        }

        $ok = $httpCode >= 200 && $httpCode < 300;
        $error = '';
        if (!$ok) {
            if (is_array($decoded) && !empty($decoded['message'])) {
                $error = is_string($decoded['message']) ? $decoded['message'] : json_encode($decoded['message']);
            } elseif (is_array($decoded) && !empty($decoded['error'])) {
                // Meta Cloud API style: {"error": {"message": "...", "code": 132001}}
                $e = $decoded['error'];
                $error = is_array($e) ? trim(($e['message'] ?? '') . (isset($e['code']) ? ' (code: ' . $e['code'] . ')' : '')) : (string) $e;
            } else {
                $error = 'API error HTTP ' . $httpCode . ': ' . substr(trim($body), 0, 300);
            }
            Log::error('WhatsApp API HTTP ' . $httpCode . ' for phone ' . ($payload['phoneNumber'] ?? $payload['to'] ?? '') . ': ' . $body);
        }

        return [
            'success' => $ok,
            'rate_limited' => false,
            'httpCode' => $httpCode,
            'response' => is_array($decoded) ? $decoded : ['raw' => $body],
            'message_id' => $decoded['data']['id'] ?? ($decoded['id'] ?? ($decoded['messages'][0]['id'] ?? '')),
            'error' => $error,
            'auth_failed' => in_array($httpCode, [401, 403], true),
        ];
    }

    /** K3 Digital Media's send-message URL (K3 Partners API v2, Pinbot). */
    public const K3_URL = 'https://partners.pinbot.ai/v2/messages';

    /** k3 and meta both send the Meta Cloud API message body. */
    public function isMetaFormat(): bool
    {
        return in_array($this->config['provider'] ?? 'interakt', ['meta', 'k3'], true);
    }

    /** All request headers: the auth header, plus "wanumber: <WABA number>" for K3. */
    public function headers(string $apiKey): array
    {
        [$name, $value] = $this->authHeader($apiKey);
        $headers = [$name => $value];
        if (($this->config['provider'] ?? '') === 'k3') {
            $headers['wanumber'] = (string) ($this->config['wanumber'] ?? '');
        }

        return $headers;
    }

    /** The URL a message is posted to; a Meta-format URL may hold {phone_number_id}. K3 without a URL = K3_URL. */
    public function endpoint(): string
    {
        $url = trim((string) ($this->config['api_url'] ?? ''));
        if ($url === '' && ($this->config['provider'] ?? '') === 'k3') {
            $url = self::K3_URL;
        }

        return str_replace('{phone_number_id}', rawurlencode((string) ($this->config['phone_number_id'] ?? '')), $url);
    }

    /**
     * [header name, value]. Interakt: "Authorization: Basic <key>". Meta-format providers: the sender's auth_header
     * - empty / "Bearer" = "Authorization: Bearer <key>", any other name (e.g. "apikey") = "<name>: <key>".
     */
    public function authHeader(string $apiKey): array
    {
        $provider = $this->config['provider'] ?? 'interakt';
        $h = trim((string) ($this->config['auth_header'] ?? ''));
        if ($provider === 'k3') {
            // K3 Partners API: "apikey: YOUR_WABA_API_KEY"
            return [$h !== '' ? $h : 'apikey', $apiKey];
        }
        if ($provider !== 'meta') {
            return ['Authorization', 'Basic ' . $apiKey];
        }
        if ($h === '' || strcasecmp($h, 'bearer') === 0 || strcasecmp($h, 'authorization') === 0) {
            return ['Authorization', 'Bearer ' . $apiKey];
        }

        return [$h, $apiKey];
    }

    /**
     * Meta WhatsApp Cloud API template message (used by K3 Digital Media / Pinbot and other Meta-format providers):
     * the bill PDF as the document header, the values as body parameters.
     */
    public function metaPayload(string $phone, array $bodyValues, string $pdfUrl = '', $billNo = '', array $options = []): array
    {
        $components = [];
        if ($pdfUrl !== '') {
            $components[] = ['type' => 'header', 'parameters' => [[
                'type' => 'document',
                'document' => [
                    'link' => $pdfUrl,
                    'filename' => !empty($options['file_name']) ? $options['file_name'] : ((string) $billNo !== '' ? 'Bill_' . $billNo . '.pdf' : 'Bill.pdf'),
                ],
            ]]];
        }
        if (!empty($bodyValues)) {
            $components[] = ['type' => 'body', 'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($bodyValues))];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => !empty($options['template_name']) ? $options['template_name'] : ($this->config['template_name'] ?? ''),
                'language' => ['code' => $this->config['language_code'] ?? 'en'],
                'components' => $components,
            ],
        ];
    }

    /**
     * Status updates in a webhook body, for both formats:
     * Interakt {type, data: {message: {id, message_status, channel_failure_reason}}} and
     * Meta {entry: [{changes: [{value: {statuses: [{id, status, errors}]}}]}]}.
     *
     * @return array<int, array{id: string, status: string, error: string}>  status = SENT|DELIVERED|READ|FAILED|''
     */
    public static function parseWebhook(array $payload): array
    {
        $out = [];
        $message = $payload['data']['message'] ?? null;
        if (is_array($message) && isset($message['id'])) {
            $map = ['Sent' => 'SENT', 'Delivered' => 'DELIVERED', 'Read' => 'READ', 'Failed' => 'FAILED'];
            $err = (string) ($message['channel_failure_reason'] ?? '');
            if (!empty($message['channel_error_code'])) {
                $err .= ' (code: ' . $message['channel_error_code'] . ')';
            }
            $out[] = ['id' => (string) $message['id'], 'status' => $map[(string) ($message['message_status'] ?? '')] ?? '', 'raw_status' => (string) ($message['message_status'] ?? ''), 'error' => $err];
        }
        // Meta {entry:[{changes:[{value:{statuses}}]}]}; some BSPs forward only the value ({statuses}) or {value:{statuses}}
        $statusLists = [];
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $statusLists[] = (array) ($change['value']['statuses'] ?? []);
            }
        }
        $statusLists[] = (array) ($payload['statuses'] ?? []);
        $statusLists[] = (array) ($payload['value']['statuses'] ?? []);
        foreach ($statusLists as $list) {
            foreach ($list as $st) {
                if (!isset($st['id'])) {
                    continue;
                }
                $raw = strtolower((string) ($st['status'] ?? ''));
                $e = $st['errors'][0] ?? [];
                $err = trim(($e['title'] ?? ($e['message'] ?? '')) . (isset($e['code']) ? ' (code: ' . $e['code'] . ')' : ''));
                $out[] = ['id' => (string) $st['id'], 'status' => in_array($raw, ['sent', 'delivered', 'read', 'failed'], true) ? strtoupper($raw) : '', 'raw_status' => $raw, 'error' => $err];
            }
        }

        return $out;
    }

    /**
     * Cake _updateBatchCounts(): recount the batch's logs by status and set its status
     * (PARTIAL = some sent + some pending/limit, STOPPED = nothing sent but pending left, else COMPLETED).
     */
    public function updateBatchCounts(string $batchId): void
    {
        $data = self::countsFromStatuses(
            DB::table('whatsapp_bill_logs')->where('batch_id', $batchId)
                ->groupBy('status')->selectRaw('status, COUNT(*) as cnt')->pluck('cnt', 'status')->all()
        );

        DB::table('whatsapp_bill_batches')->where('batch_id', $batchId)->update($data);
    }

    /** The batch columns for [status => count] (pure, so it can be tested without a database). */
    public static function countsFromStatuses(array $byStatus): array
    {
        $map = [
            'SENT' => 'sent_count', 'DELIVERED' => 'delivered_count', 'READ' => 'read_count', 'FAILED' => 'failed_count',
            'PENDING' => 'pending_count', 'API_LIMIT_REACHED' => 'limit_count', 'INVALID_PHONE' => 'invalid_phone_count',
            'PDF_FAILED' => 'pdf_failed_count',
        ];
        $data = array_fill_keys(array_values($map), 0);
        foreach ($byStatus as $status => $cnt) {
            if (isset($map[$status])) {
                $data[$map[$status]] = (int) $cnt;
            }
        }

        $hasPending = $data['pending_count'] > 0 || $data['limit_count'] > 0;
        $hasSent = $data['sent_count'] > 0 || $data['delivered_count'] > 0 || $data['read_count'] > 0;
        if ($hasPending && $hasSent) {
            $data['status'] = 'PARTIAL';
        } elseif ($hasPending) {
            $data['status'] = 'STOPPED';
        } else {
            $data['status'] = 'COMPLETED';
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Webhook verification. With no secret set for the sender, nothing is checked (as in Cake).
     * With a secret set, the request must carry one of:
     *  - X-Webhook-Secret: <secret>                      (K3 / Pinbot: custom header set via /v2/setwebhooks)
     *  - Interakt-Signature / X-Hub-Signature-256: sha256=<HMAC-SHA256 of the raw body>   (Interakt / Meta)
     * A request with neither header is refused (Cake let it through; that allowed forged status updates).
     */
    public static function signatureOk(string $rawBody, ?string $secret, ?string $signature, ?string $plainHeader = null): bool
    {
        if ($secret === null || $secret === '') {
            return true;
        }
        $plainHeader = trim((string) $plainHeader);
        $signature = trim((string) $signature);
        if ($plainHeader === '' && $signature === '') {
            return false;
        }
        if ($plainHeader !== '' && hash_equals($secret, $plainHeader)) {
            return true;
        }
        if ($signature !== '' && hash_equals('sha256=' . hash_hmac('sha256', $rawBody, $secret), $signature)) {
            return true;
        }

        return false;
    }

    /**
     * Cake _sanitizeHtmlForPdf(): drop invalid UTF-8 and C0/C1 control characters, which make DOMPDF throw
     * "Invalid Character Error" (Word/Excel text pasted into member or society fields).
     */
    public static function sanitizeHtmlForPdf(string $html): string
    {
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $html);
        if ($clean !== false) {
            $html = $clean;
        }

        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\x9F]/u', '', $html);
    }
}
