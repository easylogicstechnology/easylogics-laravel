<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reseller;
use App\Models\FinancialYearMaster;
use App\Models\ResellerPayment;
use App\Models\ResellerPlan;
use App\Models\Society;
use App\Models\SocietyYearMapping;
use App\Models\SocietyComplaint;
use App\Models\User;
use App\Services\Razorpay;
use App\Services\Reports\TrialBalanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReportsController extends Controller
{
    /**
     * Aggregates across member_bill_generates for every reseller-assigned
     * society - cached for an hour since the underlying query is heavy,
     * mirroring the CakePHP admin_reseller_society_report cache.
     */
    public function resellerSocietyReport()
    {
        $rows = Cache::remember('admin_reseller_society_report', 3600, function () {
            return DB::table('reseller_societies as rs')
                ->join('users as u', 'u.id', '=', 'rs.reseller_id')
                ->leftJoin('resellers as r', 'r.user_id', '=', 'u.id')
                ->join('societies as s', 's.id', '=', 'rs.societie_id')
                ->leftJoin(DB::raw('(SELECT society_id, COUNT(*) AS total_members FROM members WHERE status = 1 GROUP BY society_id) mem'), 'mem.society_id', '=', 's.id')
                ->leftJoin(DB::raw('(SELECT society_id, COUNT(*) AS total_bills FROM member_bill_generates GROUP BY society_id) bills'), 'bills.society_id', '=', 's.id')
                ->leftJoin(DB::raw('(SELECT society_id, COUNT(*) AS bills_this_month FROM member_bill_generates WHERE MONTH(bill_generated_date) = MONTH(CURDATE()) AND YEAR(bill_generated_date) = YEAR(CURDATE()) GROUP BY society_id) billsmonth'), 'billsmonth.society_id', '=', 's.id')
                ->where('u.access_level', 3)
                ->orderByDesc('u.id')
                ->orderByDesc('s.cdate')
                ->select([
                    'u.id as reseller_user_id',
                    'u.username as reseller_username',
                    'u.status as reseller_status',
                    'u.cdate as reseller_cdate',
                    DB::raw("TRIM(CONCAT(IFNULL(r.firstname,''), ' ', IFNULL(r.lastname,''))) as reseller_name"),
                    'r.city as reseller_area',
                    's.id as society_id',
                    's.society_name as society_name',
                    's.cdate as society_cdate',
                    's.status as society_status',
                    DB::raw('IFNULL(mem.total_members, 0) as total_members'),
                    DB::raw('IFNULL(bills.total_bills, 0) as total_bills'),
                    DB::raw('IFNULL(billsmonth.bills_this_month, 0) as bills_this_month'),
                ])
                ->get();
        });

        return view('admin.reports.resellerSociety', compact('rows'));
    }

    public function resellerPayments(Request $request)
    {
        if ($request->isMethod('post')) {
            $resellerId = $request->input('reseller_id');
            $amount = (float) $request->input('amount', 0);

            if (empty($resellerId) || $amount <= 0) {
                return redirect()->route('admin.reports.resellerPayments')
                    ->with('error', 'Please select a reseller and enter a valid amount.');
            }

            $sellerInfo = Reseller::where('user_id', $resellerId)->first();
            $currentExpiry = $sellerInfo->subscription_expiry ?? null;
            $baseDate = (!empty($currentExpiry) && $currentExpiry > now()->format('Y-m-d')) ? $currentExpiry : now()->format('Y-m-d');
            $newExpiry = date('Y-m-d', strtotime($baseDate . ' +365 days'));

            ResellerPayment::create([
                'reseller_id' => $resellerId,
                'amount' => $amount,
                'payment_date' => $request->input('payment_date') ?: now()->format('Y-m-d'),
                'payment_mode' => trim((string) $request->input('payment_mode')),
                'remarks' => trim((string) $request->input('remarks')),
                'extended_till' => $newExpiry,
                'added_by' => auth()->id(),
            ]);

            if ($sellerInfo) {
                $sellerInfo->subscription_expiry = $newExpiry;
                $sellerInfo->save();
                Cache::forget('admin_reseller_society_report');
            }

            return redirect()->route('admin.reports.resellerPayments')
                ->with('success', 'Payment logged and subscription extended to ' . date('d-M-Y', strtotime($newExpiry)) . '.');
        }

        $resellersList = User::where('access_level', 3)
            ->where('status', 1)
            ->orderBy('username')
            ->pluck('username', 'id');

        $payments = ResellerPayment::orderByDesc('cdate')->limit(100)->get();
        foreach ($payments as $p) {
            $p->reseller_username = $resellersList[$p->reseller_id] ?? ('User #' . $p->reseller_id);
        }

        return view('admin.reports.resellerPayments', compact('resellersList', 'payments'));
    }

    public function complaintsRegister()
    {
        $complaints = SocietyComplaint::with(['society:id,society_name', 'reseller:id,username'])
            ->orderByDesc('cdate')
            ->get();

        return view('admin.reports.complaints', compact('complaints'));
    }

    public function solveComplaint(Request $request, $id)
    {
        $complaint = SocietyComplaint::findOrFail($id);
        $complaint->status = 1;
        $complaint->resolution = trim((string) $request->input('resolution'));
        $complaint->resolved_date = now()->format('Y-m-d');
        $complaint->save();

        return redirect()->route('admin.reports.complaints')
            ->with('success', 'Complaint marked as solved.');
    }

    public function closeComplaint($id)
    {
        $complaint = SocietyComplaint::findOrFail($id);
        $complaint->status = 2;
        $complaint->resolved_date = now()->format('Y-m-d');
        $complaint->save();

        return redirect()->route('admin.reports.complaints')
            ->with('success', 'Complaint closed.');
    }

    public function reopenComplaint($id)
    {
        $complaint = SocietyComplaint::findOrFail($id);
        $complaint->status = 0;
        $complaint->resolved_date = null;
        $complaint->save();

        return redirect()->route('admin.reports.complaints')
            ->with('success', 'Complaint reopened.');
    }

    /**
     * Trial Balance Diff Report (CakePHP admin/trial_balance_diff_report): the Transaction debit and credit
     * totals of every active society, one row per financial year the society is mapped to, 15 societies a page.
     * Rows where Dr and Cr differ are highlighted red. Uses the same Trial Balance engine as the society report.
     */
    public function trialBalanceDiffReport(Request $request)
    {
        @set_time_limit(2700);

        $pageSize = 15;
        $page = max(1, (int) $request->query('page'));

        $totalSocieties = Society::where('status', 1)->count();
        $totalPages = max(1, (int) ceil($totalSocieties / $pageSize));
        $page = min($page, $totalPages);

        $societies = Society::where('status', 1)
            ->orderBy('society_name')
            ->offset(($page - 1) * $pageSize)->limit($pageSize)
            ->get(['id', 'society_name']);

        $rows = [];
        foreach ($societies as $society) {
            $years = $this->societyFinancialYears($society->id);

            if (empty($years)) {
                $rows[] = [
                    'society_id' => $society->id, 'society_name' => $society->society_name, 'year_label' => null,
                    'debit' => null, 'credit' => null, 'diff' => null,
                    'error' => 'No active financial year mapped for this society',
                ];
                continue;
            }

            // One row per financial year the society has ever been mapped to - not just "today's" year - since
            // real bookkeeping runs behind the calendar and a mismatch could sit in any of them.
            foreach ($years as $fy) {
                $result = [
                    'society_id' => $society->id, 'society_name' => $society->society_name,
                    'year_label' => date('d/m/Y', strtotime($fy['start'])) . ' - ' . date('d/m/Y', strtotime($fy['end'])),
                    'debit' => null, 'credit' => null, 'diff' => null, 'error' => null,
                ];

                try {
                    $report = (new TrialBalanceReport($society->id, $fy['year_id'], $fy['start'], $fy['end']))
                        ->run(['payment_date' => $fy['start'], 'payment_date_to' => $fy['end']]);

                    $debit = 0.0;
                    $credit = 0.0;
                    foreach ($report['heads'] as $group) {
                        if (empty($group['ledgers'])) {
                            continue;
                        }
                        foreach ($group['ledgers'] as $ledger) {
                            $debit += (float) $ledger['transactions']['debit'];
                            $credit += (float) $ledger['transactions']['credit'];
                        }
                    }
                    $result['debit'] = $debit;
                    $result['credit'] = $credit;
                    $result['diff'] = round($debit - $credit, 2);
                } catch (\Throwable $e) {
                    $result['error'] = 'Could not compute: ' . $e->getMessage();
                }

                $rows[] = $result;
            }
        }

        return view('admin.reports.trialBalanceDiff', compact('rows', 'page', 'totalPages', 'totalSocieties', 'pageSize'));
    }

    /** Every active year the society is actively mapped to, oldest first (CakePHP resolveSocietyFinancialYears) */
    private function societyFinancialYears(int $societyId): array
    {
        $assignedIds = SocietyYearMapping::where('society_id', $societyId)->where('is_active', 1)->pluck('year_id')->all();
        if (empty($assignedIds)) {
            return [];
        }

        return FinancialYearMaster::whereIn('id', $assignedIds)->where('is_active', 1)
            ->orderBy('year_start_date')->get()
            ->map(fn ($y) => ['year_id' => $y->id, 'start' => $y->year_start_date, 'end' => $y->year_end_date])
            ->all();
    }

    /**
     * Reseller Plans & Prices (CakePHP admin/reseller_plans): the subscription plans resellers can buy. Until the
     * reseller_plans table exists CakePHP says so instead of listing anything, and so does this.
     */
    public function resellerPlans()
    {
        $plans = [];
        $tableMissing = false;

        try {
            $plans = ResellerPlan::orderBy('sort_order')->orderBy('id')->get()->all();
        } catch (\Throwable $e) {
            Log::error('reseller_plans: ' . $e->getMessage());
            $tableMissing = true;
        }

        // username of each "only for reseller" id, so the Admin sees who it is
        $ids = collect($plans)->pluck('only_reseller_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $usernames = $ids ? User::whereIn('id', $ids)->pluck('username', 'id')->all() : [];

        return view('admin.reports.resellerPlans', compact('plans', 'tableMissing', 'usernames'));
    }

    /**
     * Creates a plan (no id) or updates one (CakePHP admin/save_reseller_plan). The plan key of an existing plan
     * never changes - orders refer to it. A plan under Rs. 100 must be limited to one reseller, so a test price
     * can never be shown to everybody by accident.
     */
    public function saveResellerPlan(Request $request, $planId = null)
    {
        // Return to the tab the Admin was on (special plans live on their own tab).
        $back = route('admin.reports.resellerPlans') . '#' . ($request->input('require_reseller') ? 'special' : 'general');

        $name = trim((string) $request->input('name', ''));
        $days = trim((string) $request->input('days', ''));
        $amount = trim((string) $request->input('amount', ''));
        $only = trim((string) $request->input('only_reseller_id', ''));
        $sort = trim((string) $request->input('sort_order', '0'));
        $active = $request->input('is_active') ? 1 : 0;

        $error = '';
        if ($name === '' || strlen($name) > 100) {
            $error = 'Plan name is required (up to 100 characters).';
        } elseif (!ctype_digit($days) || (int) $days < 1 || (int) $days > 3650) {
            $error = 'Days must be a whole number between 1 and 3650.';
        } elseif (!preg_match('/^[0-9]{1,7}(\.[0-9]{1,2})?$/', $amount) || (float) $amount <= 0) {
            $error = 'Price must be more than 0, at most 2 decimals (e.g. 1 or 3000 or 5500.50).';
        } elseif ($only !== '' && (!ctype_digit($only) || (int) $only < 1)) {
            $error = 'Only-for reseller ID must be a user ID number (or left empty for everyone).';
        } elseif (!ctype_digit($sort) || strlen($sort) > 3) {
            $error = 'Order must be a number between 0 and 999.';
        } elseif ($request->input('require_reseller') && $only === '') {
            $error = 'Please pick a reseller from the search list for a special plan.';
        } elseif ((float) $amount < 100 && $only === '') {
            $error = 'A plan under Rs. 100 must be limited to one reseller (fill "Only for reseller ID") so it is never shown to everyone.';
        } elseif ($only !== '') {
            $exists = User::where('id', (int) $only)->where('role', 'Reseller')->where('access_level', 3)->exists();
            if (!$exists) {
                $error = 'No reseller found with ID ' . (int) $only . '.';
            }
        }

        if ($error !== '') {
            return redirect()->to($back)->with('error', $error);
        }

        $now = now('Asia/Kolkata')->format('Y-m-d H:i:s');
        $fields = [
            'name' => $name,
            'days' => (int) $days,
            'amount' => number_format((float) $amount, 2, '.', ''),
            'is_active' => $active,
            'only_reseller_id' => $only === '' ? null : (int) $only,
            'sort_order' => (int) $sort,
            'udate' => $now,
        ];

        $existing = $planId ? ResellerPlan::findOrFail((int) $planId) : null;

        try {
            if ($existing) {
                $existing->update($fields);
                $what = 'updated';
                $key = $existing->plan_key;
            } else {
                $key = 'plan_' . date('ymdHis') . '_' . mt_rand(10, 99);
                ResellerPlan::create($fields + ['plan_key' => $key, 'cdate' => $now]);
                $what = 'added';
            }
        } catch (\Throwable $e) {
            Log::error('save_reseller_plan: ' . $e->getMessage());

            return redirect()->to($back)->with('error', 'Could not save the plan. Please try again.');
        }

        Log::info('Reseller plan ' . $key . ' ' . $what . ' by admin user ' . $request->user()->id . ': ' . json_encode($fields));

        return redirect()->to($back)->with('success', 'Plan ' . $what . ': ' . $name . ' - Rs. ' . $fields['amount'] . ' for ' . $fields['days'] . ' days' . ($active ? '' : ' (inactive - resellers will not see it)') . '.');
    }

    /** Type-ahead for the plan page's reseller picker: up to 15 matching resellers as JSON (CakePHP admin/search_resellers) */
    public function searchResellers(Request $request)
    {
        $term = trim((string) $request->query('q'));
        $out = [];

        if (strlen($term) >= 2 || ctype_digit($term)) {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $rows = DB::select("SELECT u.id, u.username, u.status, u.cdate,
                    TRIM(CONCAT(IFNULL(r.firstname,''), ' ', IFNULL(r.lastname,''))) AS name, r.city, r.contact_no, r.subscription_expiry,
                    (SELECT COUNT(*) FROM reseller_societies rs2 WHERE rs2.reseller_id = u.id) AS society_count
                FROM users u LEFT JOIN resellers r ON r.user_id = u.id
                WHERE u.access_level = ? AND u.role = 'Reseller'
                  AND (u.username LIKE ? OR r.firstname LIKE ? OR r.lastname LIKE ? OR CONCAT(r.firstname, ' ', r.lastname) LIKE ?
                       OR r.city LIKE ? OR r.contact_no LIKE ? OR u.id = ?
                       OR u.id IN (SELECT rs.reseller_id FROM reseller_societies rs INNER JOIN societies s ON s.id = rs.societie_id WHERE s.society_name LIKE ?))
                ORDER BY u.username LIMIT 15", [3, $like, $like, $like, $like, $like, $like, ctype_digit($term) ? (int) $term : -1, $like]);

            foreach ($rows as $f) {
                $out[] = [
                    'id' => (int) $f->id,
                    'username' => $f->username,
                    'name' => $f->name,
                    'area' => $f->city,
                    'active' => ((int) $f->status === 1),
                    'societies' => (int) $f->society_count,
                ];
            }
        }

        return response()->json($out);
    }

    /**
     * Payment Setup Check (CakePHP admin/payment_setup): what THIS server actually sees for the online-payment
     * setup - whether the config loads, which mode is active, whether the key for that mode is filled in (only its
     * rzp_test / rzp_live prefix is shown, never a secret), whether the payment tables exist - plus a button that
     * asks Razorpay whether it accepts the keys (a read-only call).
     */
    public function paymentSetup(Request $request)
    {
        $configFile = config_path('payment.php');
        $envFile = base_path('.env');

        $probe = function (string $name, string $path, bool $loads, string $error = '') {
            return [
                'name' => $name, 'path' => $path, 'exists' => file_exists($path),
                'readable' => is_readable($path), 'loads' => $loads, 'error' => $error, 'stray_output' => false,
            ];
        };
        $razorpayConfig = config('payment.Razorpay');
        $base = $probe('config/payment.php', $configFile, is_array($razorpayConfig) && !empty($razorpayConfig));
        $local = $probe('.env', $envFile, file_exists($envFile) && is_readable($envFile));
        if ($local['exists'] && !$local['readable']) {
            $local['error'] = 'The file is there but PHP cannot read it (file permission).';
        }

        // Only the prefix of a key id and "filled in / empty" for the secrets - never a credential.
        $describeBlock = function ($block) {
            $block = is_array($block) ? $block : [];
            $keyId = trim((string) ($block['key_id'] ?? ''));
            $secret = trim((string) ($block['key_secret'] ?? ''));
            $webhook = trim((string) ($block['webhook_secret'] ?? ''));
            if ($keyId === '') {
                $prefix = 'empty';
            } elseif (preg_match('/^rzp_(test|live)_/', $keyId, $m)) {
                $prefix = 'rzp_' . $m[1] . '_...';
            } else {
                $prefix = 'NOT a rzp_test_ / rzp_live_ key';
            }

            return [
                'key_id' => $prefix,
                'key_secret' => $secret === '' ? 'empty' : (trim($secret, '*') === '' ? 'only asterisks (masked text, not the real secret)' : 'filled in'),
                'webhook_secret' => $webhook === '' ? 'empty' : 'filled in',
            ];
        };

        $rz = new Razorpay();
        $mode = $rz->mode();
        $problem = $rz->configProblem();

        $advice = [];
        if (!$base['exists'] || !$base['loads']) {
            $advice[] = 'config/payment.php is missing or broken. Upload the clean copy from the project again.';
        }
        if (!$local['exists']) {
            $advice[] = '.env is NOT on this server at the path shown below. Create it exactly there (project root). Keys live only in that file.';
        } elseif (!$local['readable'] || !$local['loads']) {
            $advice[] = '.env exists but cannot be used: ' . $local['error'];
        }
        if ($problem === 'missing') {
            $advice[] = 'RAZORPAY_MODE is "' . $mode . '" but the "' . $mode . '" block has no key id and/or key secret. Fill RAZORPAY_' . strtoupper($mode) . '_KEY_ID / _KEY_SECRET in .env, or set RAZORPAY_MODE to the block that has the keys.';
        } elseif ($problem === 'mode_mismatch') {
            $advice[] = 'The key id in the "' . $mode . '" block does not start with rzp_' . $mode . '_. A rzp_live_ key belongs in the "live" block with mode "live"; a rzp_test_ key in the "test" block with mode "test".';
        } elseif ($problem === 'invalid_secret') {
            $advice[] = 'The key secret is only asterisks - that is the masked text the Razorpay Dashboard shows. Paste the real secret (shown once when the key is generated).';
        }

        $tables = [];
        foreach (['reseller_payments', 'reseller_plan_orders', 'reseller_plans'] as $t) {
            $tables[$t] = Schema::hasTable($t);
        }

        $test = null;
        if ($request->isMethod('post')) {
            if ($problem !== '') {
                $test = ['ok' => false, 'message' => 'Fix the setup problems above first - the keys are not usable yet.'];
            } else {
                try {
                    $rz->checkCredentials();
                    $test = ['ok' => true, 'message' => 'Razorpay accepted the ' . $mode . ' keys (read-only check, nothing was created or charged).'];
                } catch (\Throwable $e) {
                    $test = ['ok' => false, 'message' => $e->getMessage()];
                }
            }
        }

        return view('admin.reports.paymentSetup', [
            'files' => [$base, $local],
            'blocks' => [
                'test' => $describeBlock($razorpayConfig['test'] ?? []),
                'live' => $describeBlock($razorpayConfig['live'] ?? []),
            ],
            'mode' => $mode,
            'problem' => $problem,
            'advice' => $advice,
            'ready' => ($problem === '' && $base['loads'] && $local['loads']),
            'tables' => $tables,
            'test' => $test,
            'server' => [
                'php' => PHP_VERSION,
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
            ],
        ]);
    }
}
