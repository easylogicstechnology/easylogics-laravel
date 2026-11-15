<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppSenders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > WhatsApp APIs: one WhatsApp number + API account per reseller. Only Admin can see or change these
 * (route group EnsureRole:Admin). A society's bills go out through its reseller's ACTIVE sender, else through the
 * common API in .env. Keys are kept encrypted and never shown again - only their last 4 characters.
 */
class WhatsAppSenderController extends Controller
{
    private function tableReady(): bool
    {
        return Schema::hasTable('reseller_whatsapp_senders') && Schema::hasColumn('reseller_whatsapp_senders', 'api_url');
    }

    public function index()
    {
        $ready = $this->tableReady();

        $resellers = DB::table('users as u')
            ->leftJoin('resellers as r', 'r.user_id', '=', 'u.id')
            ->where('u.role', 'Reseller')
            ->orderBy('r.firstname')->orderBy('u.username')
            ->get(['u.id', 'u.username', 'u.status as user_status', 'r.firstname', 'r.lastname', 'r.contact_no']);

        $senders = $ready ? DB::table('reseller_whatsapp_senders')->get()->keyBy('reseller_id') : collect();
        $societyCounts = DB::table('reseller_societies')->where('status', 1)
            ->groupBy('reseller_id')->selectRaw('reseller_id, COUNT(*) as c')->pluck('c', 'reseller_id');

        $common = (array) config('whatsapp', []);

        return view('admin.whatsapp.senders', [
            'ready' => $ready,
            'resellers' => $resellers,
            'senders' => $senders,
            'societyCounts' => $societyCounts,
            'common' => [
                'provider' => WhatsAppSenders::PROVIDERS[$common['provider'] ?? 'interakt'] ?? ($common['provider'] ?? 'interakt'),
                'enabled' => !empty($common['enabled']),
                'key' => trim((string) ($common['api_key'] ?? '')) !== '',
                'template' => (string) ($common['template_name'] ?? ''),
                'fallback' => (bool) ($common['common_fallback'] ?? true),
            ],
            'providers' => WhatsAppSenders::PROVIDERS,
            'statuses' => WhatsAppSenders::STATUSES,
        ]);
    }

    /** Create or update the sender of one reseller. A blank key / secret keeps the saved one. */
    public function save(Request $request, int $resellerId)
    {
        if (!$this->tableReady()) {
            return back()->with('error', 'Table missing: run the migration 2026_11_12_000002_create_reseller_whatsapp_senders_table.php first.');
        }
        if (!DB::table('users')->where('id', $resellerId)->where('role', 'Reseller')->exists()) {
            return back()->with('error', 'Reseller not found.');
        }

        $data = $request->validate([
            'sender_phone' => ['required', 'string', 'max:20'],
            'provider' => ['required', 'in:' . implode(',', array_keys(WhatsAppSenders::PROVIDERS))],
            'status' => ['required', 'in:' . implode(',', WhatsAppSenders::STATUSES)],
            'api_key' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:500'],
            'api_url' => ['nullable', 'url', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:64'],
            'auth_header' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]*$/'],
            'template_name' => ['nullable', 'string', 'max:100'],
            'body_params' => ['nullable', 'string', 'max:255', 'regex:/^[a-z_,\s]*$/'],
            'language_code' => ['nullable', 'string', 'max:10'],
        ]);

        $phone = WhatsAppService::validatePhone($data['sender_phone']);
        if (!$phone['valid']) {
            return back()->withInput()->with('error', 'Sender number: ' . $phone['error']);
        }

        $existing = DB::table('reseller_whatsapp_senders')->where('reseller_id', $resellerId)->first();
        $key = trim((string) ($data['api_key'] ?? ''));
        if ($key === '' && (!$existing || WhatsAppSenders::decrypt($existing->api_key_enc) === null || WhatsAppSenders::decrypt($existing->api_key_enc) === '')) {
            if ($data['status'] === 'ACTIVE') {
                return back()->withInput()->with('error', 'API key is required to make this sender ACTIVE.');
            }
        }
        if ($data['provider'] === 'meta' && $data['status'] === 'ACTIVE' && empty($data['api_url'])) {
            return back()->withInput()->with('error', 'Meta-format provider needs the API URL (the provider\'s send-message URL).');
        }

        $row = [
            'sender_phone' => $phone['phone'],
            'provider' => $data['provider'],
            'status' => $data['status'],
            'api_url' => $data['api_url'] ?? null,
            'phone_number_id' => $data['phone_number_id'] ?? null,
            'auth_header' => $data['auth_header'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'body_params' => isset($data['body_params']) ? preg_replace('/\s+/', '', $data['body_params']) : null,
            'language_code' => $data['language_code'] ?? null,
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'last_error' => null,
        ];
        if ($key !== '') {
            $row['api_key_enc'] = Crypt::encryptString($key);
            $row['api_key_last4'] = substr($key, -4);
        }
        $secret = trim((string) ($data['webhook_secret'] ?? ''));
        if ($secret !== '') {
            $row['webhook_secret_enc'] = Crypt::encryptString($secret);
        }

        if ($existing) {
            DB::table('reseller_whatsapp_senders')->where('id', $existing->id)->update($row);
        } else {
            DB::table('reseller_whatsapp_senders')->insert($row + [
                'reseller_id' => $resellerId,
                'api_key_enc' => $row['api_key_enc'] ?? '',
                'created_by' => Auth::id(),
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }
        WhatsAppSenders::flush();

        return redirect()->route('admin.whatsappSenders')->with('success', 'WhatsApp API saved for ' . WhatsAppSenders::resellerName($resellerId) . '.');
    }

    /**
     * "Create tables" button: runs only the two WhatsApp migrations (both only create what is missing and never
     * drop anything), so the tables can be set up from this page without a terminal.
     */
    public function migrate()
    {
        $out = [];
        foreach ([
            'database/migrations/2026_11_12_000001_create_whatsapp_bill_tables.php',
            'database/migrations/2026_11_12_000002_create_reseller_whatsapp_senders_table.php',
        ] as $path) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--path' => $path, '--force' => true]);
                $out[] = trim(\Illuminate\Support\Facades\Artisan::output());
            } catch (\Throwable $e) {
                return redirect()->route('admin.whatsappSenders')->with('error', 'Migration failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.whatsappSenders')->with('success', 'WhatsApp tables ready. ' . preg_replace('/\s+/', ' ', implode(' ', $out)));
    }

    /** Remove the saved webhook secret (the key is changed by typing a new one). */
    public function clearSecret(int $resellerId)
    {
        if ($this->tableReady()) {
            DB::table('reseller_whatsapp_senders')->where('reseller_id', $resellerId)->update(['webhook_secret_enc' => null, 'updated_at' => now()->format('Y-m-d H:i:s')]);
        }

        return redirect()->route('admin.whatsappSenders')->with('success', 'Webhook secret removed.');
    }
}
