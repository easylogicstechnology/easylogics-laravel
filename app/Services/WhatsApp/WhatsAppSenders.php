<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Which WhatsApp account a society's messages go out from (Admin > WhatsApp APIs).
 *
 * society login (users.id) -> its active reseller (reseller_societies) -> that reseller's ACTIVE row in
 * reseller_whatsapp_senders. No reseller, no row, a row that is not ACTIVE or whose key cannot be decrypted:
 * the common API from .env (config/whatsapp.php), unless WHATSAPP_COMMON_FALLBACK=false.
 *
 * Keys are stored encrypted with the app key (Crypt); only the last 4 characters are ever shown.
 */
class WhatsAppSenders
{
    public const PROVIDERS = [
        'interakt' => 'Interakt',
        'k3' => 'K3 Digital Media (Partners API v2 / Pinbot)',
        'meta' => 'Meta Cloud API format (K3 Digital Media / Pinbot / other BSP)',
    ];

    public const STATUSES = ['ACTIVE', 'PENDING', 'DISCONNECTED', 'DISABLED'];

    private static array $cache = [];

    /** The reseller (users.id) a society login belongs to, or null. */
    public static function resellerIdForSociety(int $societyUserId): ?int
    {
        $id = DB::table('reseller_societies')->where('user_id', $societyUserId)->where('status', 1)
            ->orderByDesc('id')->value('reseller_id');

        return $id ? (int) $id : null;
    }

    /** The reseller's sender row (any status), or null. */
    public static function senderRow(?int $resellerId): ?object
    {
        if (!$resellerId || !Schema::hasTable('reseller_whatsapp_senders')) {
            return null;
        }

        return DB::table('reseller_whatsapp_senders')->where('reseller_id', $resellerId)->first();
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return '';
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null; // e.g. a key saved by the CakePHP app or under another APP_KEY
        }
    }

    /**
     * The whatsapp config for a society: config/whatsapp.php with the reseller's sender laid over it, plus
     * 'sender' = a label for the screen and the log ('Common API' or 'Reseller <name> (<phone>, <provider>)').
     */
    public static function configForSociety(int $societyUserId): array
    {
        if (isset(self::$cache[$societyUserId])) {
            return self::$cache[$societyUserId];
        }
        $common = (array) config('whatsapp', []);
        $common['provider'] = $common['provider'] ?? 'interakt';
        $common['sender'] = 'Common API';
        $common['sender_reseller_id'] = null;

        $resellerId = self::resellerIdForSociety($societyUserId);
        $row = self::senderRow($resellerId);
        $cfg = $common;

        if ($row && $row->status === 'ACTIVE') {
            $key = self::decrypt($row->api_key_enc);
            if ($key === null || $key === '') {
                Log::warning('WhatsApp sender of reseller ' . $resellerId . ' has no usable API key; using the common API.');
            } else {
                $cfg = array_merge($common, array_filter([
                    'provider' => $row->provider ?: 'interakt',
                    'api_key' => $key,
                    'webhook_secret' => (string) self::decrypt($row->webhook_secret_enc),
                    'api_url' => $row->api_url ?? null,
                    'phone_number_id' => $row->phone_number_id ?? null,
                    'auth_header' => $row->auth_header ?? null,
                    'template_name' => $row->template_name ?? null,
                    'body_params' => $row->body_params ?? null,
                    'language_code' => $row->language_code ?? null,
                ], fn ($v) => $v !== null && $v !== ''));
                // a reseller on Interakt with no URL of its own uses Interakt's URL
                if ($cfg['provider'] === 'interakt' && empty($row->api_url)) {
                    $cfg['api_url'] = (string) config('whatsapp.api_url');
                }
                if (in_array($cfg['provider'], ['meta', 'k3'], true) && empty($row->api_url)) {
                    $cfg['api_url'] = ''; // k3: WhatsAppService::K3_URL
                }
                if ($cfg['provider'] === 'k3') {
                    $cfg['wanumber'] = $row->sender_phone; // K3 "wanumber" header = the WABA number
                }
                $cfg['sender'] = 'Reseller ' . self::resellerName($resellerId) . ' (' . $row->sender_phone . ', ' . (self::PROVIDERS[$cfg['provider']] ?? $cfg['provider']) . ')';
                $cfg['sender_reseller_id'] = $resellerId;
            }
        }

        if ($cfg['sender_reseller_id'] === null && !config('whatsapp.common_fallback', true)) {
            $cfg['enabled'] = false;
            $cfg['sender'] = 'None - this society\'s reseller has no active WhatsApp API and the common API is switched off';
        }

        return self::$cache[$societyUserId] = $cfg;
    }

    public static function resellerName(?int $resellerId): string
    {
        if (!$resellerId) {
            return '';
        }
        $r = DB::table('resellers')->where('user_id', $resellerId)->first(['firstname', 'lastname']);
        $name = $r ? trim($r->firstname . ' ' . $r->lastname) : '';
        if ($name === '') {
            // the live users table has only some of these columns (no `name`), so read the row as it is
            $u = DB::table('users')->where('id', $resellerId)->first();
            $name = $u ? (string) (($u->full_name ?? '') ?: (($u->name ?? '') ?: ($u->username ?? ''))) : '';
            $name = $name !== '' ? $name : '#' . $resellerId;
        }

        return $name;
    }

    /** Forget the per-request cache (after Admin saves a sender). */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
