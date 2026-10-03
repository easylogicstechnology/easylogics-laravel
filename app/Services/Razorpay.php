<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Razorpay credentials check (CakePHP: app/Lib/Razorpay.php - mode(), configProblem(), checkCredentials()).
 *
 * Credentials come from config('payment.Razorpay'). Only the block named by its 'mode' ('test' or 'live') is
 * used, and the key id must carry the matching prefix (rzp_test_ / rzp_live_), so test and live credentials can
 * never be mixed up. Only what the Payment Setup Check page needs is ported; the order / payment calls belong
 * to the reseller payment flow, which is not migrated yet.
 */
class Razorpay
{
    public const API_BASE = 'https://api.razorpay.com/v1/';

    private string $mode;
    private string $keyId;
    private string $keySecret;

    public function __construct(?array $config = null)
    {
        $config ??= (array) config('payment.Razorpay');

        $this->mode = (($config['mode'] ?? '') === 'live') ? 'live' : 'test';
        $block = (array) ($config[$this->mode] ?? []);
        $this->keyId = trim((string) ($block['key_id'] ?? ''));
        $this->keySecret = trim((string) ($block['key_secret'] ?? ''));
    }

    /** 'test' or 'live' - the block credentials are read from. */
    public function mode(): string
    {
        return $this->mode;
    }

    /**
     * Why the keys are unusable: '' (configured), 'missing' (no keys), 'invalid_secret' (the secret is only
     * asterisks - the masked text the Razorpay Dashboard shows) or 'mode_mismatch' (key id prefix does not
     * match the mode).
     */
    public function configProblem(): string
    {
        if ($this->keyId === '' || $this->keySecret === '') {
            return 'missing';
        }
        if (trim($this->keySecret, '*') === '') {
            return 'invalid_secret';
        }
        if (!str_starts_with($this->keyId, 'rzp_' . $this->mode . '_')) {
            return 'mode_mismatch';
        }

        return '';
    }

    /**
     * Read-only call (lists at most one order) that shows whether Razorpay accepts these keys. Creates and
     * charges nothing, in test or live mode.
     *
     * @throws RuntimeException with Razorpay's reason (e.g. Authentication failed)
     */
    public function checkCredentials(): bool
    {
        if ($this->configProblem() !== '') {
            throw new RuntimeException('Razorpay keys are not configured.');
        }

        try {
            $response = Http::withBasicAuth($this->keyId, $this->keySecret)
                ->connectTimeout(10)->timeout(15)
                ->acceptJson()->get(self::API_BASE . 'orders', ['count' => 1]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Razorpay request failed: ' . $e->getMessage());
        }

        $data = $response->json();
        if (!$response->successful() || !is_array($data)) {
            $reason = (is_array($data) && isset($data['error']['description'])) ? $data['error']['description'] : ('HTTP ' . $response->status());
            throw new RuntimeException('Razorpay error: ' . $reason);
        }

        return true;
    }
}
