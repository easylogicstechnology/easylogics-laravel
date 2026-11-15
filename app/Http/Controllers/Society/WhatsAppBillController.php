<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Services\Reports\BillPrintReport;
use App\Services\Reports\MemberBillPrintReport;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\CakeUtil;
use App\Support\LegacySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Send WhatsApp Bill (CakePHP society_bills/send_bill_whatsapp, whatsapp_send_batch, whatsapp_batch_status,
 * whatsapp_retry_failed, whatsapp_batch_history, whatsapp_webhook). Same tables, same statuses, same JSON, so the
 * page behaves as in CakePHP: one click creates a batch, every matching bill gets a log row, each bill's PDF
 * (the carried-over email_member_bills view) is sent through Interakt, a rate limit stops the batch, and
 * "Retry Failed" sends the FAILED / API_LIMIT_REACHED / PENDING ones again.
 */
class WhatsAppBillController extends Controller
{
    private ?WhatsAppService $svc = null;

    /** The society's sender: its reseller's own WhatsApp API (Admin > WhatsApp APIs), else the common one. */
    private function wa(): WhatsAppService
    {
        return $this->svc ??= WhatsAppService::forSociety($this->societyId());
    }

    private function societyId(): int
    {
        return (int) Auth::id();
    }

    private function fyId(): ?int
    {
        $id = session('fy.year_id');

        return $id === null ? null : (int) $id;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------ pages

    /** CakePHP: society_bills/send_bill_whatsapp */
    public function index()
    {
        $societyId = $this->societyId();
        $params = (new BillPrintReport($societyId, $this->fyId()))->societyParameters();

        return view('society.whatsapp.send-bill', [
            'societyBuildingLists' => DB::table('buildings')->where('society_id', $societyId)->orderBy('id')->pluck('building_name', 'id')->all(),
            'billingFrequencyId' => (int) ($params['billing_frequency_id'] ?? 1),
            'senderLabel' => (string) $this->wa()->config('sender', 'Common API'),
        ]);
    }

    /** CakePHP: society_bills/whatsapp_batch_history (last 50 batches) */
    public function history()
    {
        $batches = DB::table('whatsapp_bill_batches')
            ->where('society_id', $this->societyId())
            ->orderByDesc('id')->limit(50)->get();

        return view('society.whatsapp.batch-history', compact('batches'));
    }

    // ------------------------------------------------------------------ send / status / retry

    /** CakePHP: society_bills/whatsapp_send_batch */
    public function sendBatch(Request $request)
    {
        @set_time_limit(1800);

        try {
            $societyId = $this->societyId();
            $fyId = $this->fyId();
            $billMonth = (string) $request->input('month', '');
            $from = (int) $request->input('bill_from', 0);
            $to = (int) $request->input('bill_to', 0);
            if ($from && $to && $from > $to) {
                [$from, $to] = [$to, $from];
            }
            $billType = $request->filled('bill_type') ? (string) $request->input('bill_type') : 'reg';
            $receiptPeriod = ['From' => (string) $request->input('receipt_from', ''), 'To' => (string) $request->input('receipt_to', '')];

            $q = DB::table('member_bill_summaries')
                ->where('society_id', $societyId)
                ->where('financial_year_id', $fyId)
                ->where('bill_type', $billType);
            if ($billMonth !== '') {
                $q->where('month', $billMonth);
            }
            if ($request->filled('flat_no')) {
                $q->where('flat_no', $request->input('flat_no'));
            }
            if ($from && $to) {
                $q->whereBetween('bill_no', [$from, $to]);
            } elseif ($to) {
                $q->where('bill_no', '<=', $to);
            } elseif ($from) {
                $q->where('bill_no', '>=', $from);
            }
            $bills = $q->orderBy('bill_no')->get(['id', 'member_id', 'bill_no', 'month', 'amount_payable']);

            if ($bills->isEmpty()) {
                return response()->json(['error' => 'No bills found for the selected criteria.']);
            }

            $batchId = WhatsAppService::generateBatchId($societyId);
            DB::table('whatsapp_bill_batches')->insert([
                'batch_id' => $batchId,
                'society_id' => $societyId,
                'bill_month' => $billMonth,
                'financial_year_id' => $fyId,
                'total_bills' => $bills->count(),
                'pending_count' => $bills->count(),
                'status' => 'IN_PROGRESS',
                'created_by' => Auth::id(),
                'created_at' => $this->now(),
            ]);

            $members = DB::table('members')->whereIn('id', $bills->pluck('member_id')->unique()->all())
                ->get(['id', 'flat_no', 'member_name', 'member_phone'])->keyBy('id');
            foreach ($bills as $bill) {
                $m = $members->get($bill->member_id);
                DB::table('whatsapp_bill_logs')->insert([
                    'batch_id' => $batchId,
                    'society_id' => $societyId,
                    'member_id' => $bill->member_id,
                    'bill_summary_id' => $bill->id,
                    'bill_no' => $bill->bill_no,
                    'bill_month' => $bill->month,
                    'financial_year_id' => $fyId,
                    'flat_no' => $m->flat_no ?? '',
                    'member_name' => $m->member_name ?? '',
                    'phone' => $m->member_phone ?? '',
                    'amount' => $bill->amount_payable,
                    'status' => 'PENDING',
                    'created_at' => $this->now(),
                ]);
            }

            $logs = DB::table('whatsapp_bill_logs')->where('batch_id', $batchId)->where('status', 'PENDING')->orderBy('id')->get();
            $this->process($batchId, $logs, $billType, $receiptPeriod, false);
            $this->wa()->updateBatchCounts($batchId);

            return response()->json(['success' => true, 'batch_id' => $batchId, 'summary' => $this->summary($batchId)]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /** CakePHP: society_bills/whatsapp_batch_status (no batch_id = the society's latest batch) */
    public function batchStatus(Request $request)
    {
        $societyId = $this->societyId();
        $batchId = (string) $request->query('batch_id', '');

        $batch = DB::table('whatsapp_bill_batches')->where('society_id', $societyId)
            ->when($batchId !== '', fn ($q) => $q->where('batch_id', $batchId), fn ($q) => $q->orderByDesc('id'))
            ->first();
        if (!$batch) {
            return response()->json(['error' => $batchId === '' ? 'No batch found.' : 'Batch not found.']);
        }

        $bills = DB::table('whatsapp_bill_logs')->where('batch_id', $batch->batch_id)->orderBy('id')->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'bill_no' => $l->bill_no,
                'flat_no' => $l->flat_no,
                'member_name' => $l->member_name,
                'phone' => $l->phone,
                'amount' => number_format((float) $l->amount, 2),
                'status' => $l->status,
                'sent_at' => $l->sent_at,
                'delivered_at' => $l->delivered_at,
                'read_at' => $l->read_at,
                'error' => $l->error_message,
                'retry_count' => $l->retry_count,
                'api_message_id' => $l->api_message_id,
            ])->all();

        return response()->json(['batch_id' => $batch->batch_id, 'summary' => $this->summary($batch), 'bills' => $bills]);
    }

    /** CakePHP: society_bills/whatsapp_retry_failed (batch_id, optional log_ids[]) */
    public function retryFailed(Request $request)
    {
        @set_time_limit(1800);

        $batchId = (string) $request->input('batch_id', '');
        if ($batchId === '') {
            return response()->json(['error' => 'batch_id is required.']);
        }
        $batch = DB::table('whatsapp_bill_batches')->where('batch_id', $batchId)->where('society_id', $this->societyId())->first();
        if (!$batch) {
            return response()->json(['error' => 'Batch not found.']);
        }

        $logs = DB::table('whatsapp_bill_logs')->where('batch_id', $batchId)->whereIn('status', WhatsAppService::RETRYABLE)
            ->when($request->filled('log_ids'), fn ($q) => $q->whereIn('id', array_map('intval', (array) $request->input('log_ids'))))
            ->orderBy('id')->get();
        if ($logs->isEmpty()) {
            return response()->json(['error' => 'No bills to retry.']);
        }

        DB::table('whatsapp_bill_batches')->where('id', $batch->id)->update(['status' => 'IN_PROGRESS', 'stopped_at_bill' => null]);

        try {
            // as in CakePHP: a retry uses the regular bill and no receipt period
            $this->process($batchId, $logs, 'reg', ['From' => '', 'To' => ''], true);
            $this->wa()->updateBatchCounts($batchId);

            return response()->json(['success' => true, 'batch_id' => $batchId, 'summary' => $this->summary($batchId)]);
        } catch (\Throwable $e) {
            $this->wa()->updateBatchCounts($batchId);

            return response()->json(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Send each log's bill. Stops at the first rate limit (that bill becomes API_LIMIT_REACHED and the batch
     * records where it stopped); the rest stay PENDING for "Retry Failed".
     */
    private function process(string $batchId, $logs, string $billType, array $receiptPeriod, bool $isRetry): void
    {
        $societyId = $this->societyId();
        $societyNames = [];

        foreach ($logs as $log) {
            $upd = fn (array $d) => DB::table('whatsapp_bill_logs')->where('id', $log->id)->update($d + ['updated_at' => $this->now()]);

            if ($isRetry) {
                $upd(['retry_count' => (int) $log->retry_count + 1]);
            }

            $phone = WhatsAppService::validatePhone($log->phone);
            if (!$phone['valid']) {
                $upd(['status' => 'INVALID_PHONE', 'error_message' => $phone['error']]);
                continue;
            }

            $bill = DB::table('member_bill_summaries')->where('id', $log->bill_summary_id)->first();
            $member = DB::table('members')->where('id', $log->member_id)->first();
            if (!$bill || !$member) {
                $upd(['status' => 'FAILED', 'error_message' => 'Bill or member data not found']);
                continue;
            }

            // a retry sends the PDF already made for this bill, when it is still there
            $pdfPath = '';
            if ($isRetry && !empty($log->pdf_path) && is_file($log->pdf_path)) {
                $pdfPath = $log->pdf_path;
            } else {
                try {
                    $pdfPath = $this->saveBillPdf($log, $billType, $receiptPeriod);
                } catch (\Throwable $e) {
                    Log::error('WhatsApp PDF generation failed for whatsapp_bill_logs #' . $log->id . ': ' . $e->getMessage());
                    $upd(['status' => 'PDF_FAILED', 'error_message' => 'PDF generation failed: ' . $e->getMessage()]);
                    continue;
                }
            }
            $pdfUrl = $this->pdfUrl($pdfPath);

            $sid = (int) $member->society_id;
            if (!array_key_exists($sid, $societyNames)) {
                $societyNames[$sid] = (string) (DB::table('societies')->where('id', $sid)->value('society_name') ?? '');
            }
            $logRow = (array) $log;
            $logRow['due_date'] = !empty($bill->bill_due_date) ? date('d/m/Y', strtotime($bill->bill_due_date)) : '';
            // bill months 4-12 fall in the financial year's first calendar year, 1-3 in its second
            $logRow['bill_year'] = (int) $log->bill_month >= 4 ? (string) session('fy.start_year', '') : (string) session('fy.end_year', '');
            $params = $this->wa()->bodyParams($logRow, $societyNames[$sid]);

            $api = $this->wa()->send($phone['phone'], $params, $pdfUrl, $log->bill_no);

            if (!empty($api['rate_limited'])) {
                $upd([
                    'status' => 'API_LIMIT_REACHED',
                    'error_message' => $api['error'] ?? 'Rate limit exceeded',
                    'api_response' => json_encode($api['response'] ?? null),
                    'pdf_path' => $pdfPath,
                ]);
                DB::table('whatsapp_bill_batches')->where('batch_id', $batchId)->update([
                    'stopped_at_bill' => 'Bill ' . $log->bill_no . ', Flat ' . $log->flat_no,
                ]);
                break;
            }

            if (!empty($api['success'])) {
                $upd([
                    'status' => 'SENT',
                    'api_message_id' => $api['message_id'] ?? '',
                    'api_response' => json_encode($api['response'] ?? null),
                    'pdf_path' => $pdfPath,
                    'sent_at' => $this->now(),
                ]);
            } else {
                $upd([
                    'status' => 'FAILED',
                    'error_message' => $api['error'] ?: 'Unknown API error',
                    'api_response' => json_encode($api['response'] ?? null),
                    'pdf_path' => $pdfPath,
                ]);
            }
        }
    }

    /** The batch counters as the page reads them (summary of Cake's JSON). */
    private function summary($batch): array
    {
        if (is_string($batch)) {
            $batch = DB::table('whatsapp_bill_batches')->where('batch_id', $batch)->first();
        }

        return [
            'total' => (int) $batch->total_bills,
            'sent' => (int) $batch->sent_count,
            'delivered' => (int) $batch->delivered_count,
            'read' => (int) $batch->read_count,
            'failed' => (int) $batch->failed_count,
            'pending' => (int) $batch->pending_count,
            'api_limit' => (int) $batch->limit_count,
            'invalid_phone' => (int) $batch->invalid_phone_count,
            'pdf_failed' => (int) $batch->pdf_failed_count,
            'status' => $batch->status,
            'stopped_at' => $batch->stopped_at_bill,
            'created_at' => $batch->created_at,
            'completed_at' => $batch->completed_at,
        ];
    }

    // ------------------------------------------------------------------ bill PDF

    /**
     * The member's bill as a PDF (the same carried-over email_member_bills view CakePHP renders for WhatsApp),
     * saved under public/tmp so Interakt can fetch it. Returns the file path.
     */
    private function saveBillPdf(object $log, string $billType, array $receiptPeriod): string
    {
        // the live MemberBillPrintReport's single-bill mode (as the bill window's Download PDF, AccountReportController::billPdf):
        // the bill is pinned by id, month/type and current-or-previous owner are read from the bill itself
        $q = [
            'bill_id' => (int) $log->bill_summary_id,
            'billFormat' => 'full',
            'remove_details' => [],
        ];
        if ($receiptPeriod['From'] !== '' && $receiptPeriod['To'] !== '') {
            $q['receipt_from'] = $receiptPeriod['From'];
            $q['receipt_to'] = $receiptPeriod['To'];
        }

        $data = (new MemberBillPrintReport($this->societyId(), $this->fyId()))->run($q);
        if (empty($data['printBillDetails'])) {
            // say why, so the log row shows what to fix
            $b = DB::table('member_bill_summaries')->where('id', (int) $log->bill_summary_id)
                ->first(['society_id', 'financial_year_id', 'month', 'bill_type', 'member_id', 'member_transfer']);
            $why = !$b ? 'bill row #' . $log->bill_summary_id . ' missing'
                : 'bill society ' . $b->society_id . '/fy ' . $b->financial_year_id . '/month ' . $b->month . '/type ' . $b->bill_type
                  . '/transfer ' . (int) $b->member_transfer . ' vs member transfer ' . (int) DB::table('members')->where('id', $b->member_id)->value('member_transfer')
                  . '; screen society ' . $this->societyId() . '/fy ' . var_export($this->fyId(), true);
            throw new \RuntimeException('bill ' . $log->bill_no . ' (flat ' . $log->flat_no . ') not found [' . $why . ']');
        }
        $data += [
            'q' => $q,
            // as billPdf: no words_type sent = the English amount in words
            'wordsFunction' => fn ($n) => \App\Support\BillWords::english($n),
            'html' => new \App\Support\LegacyHtml(),
            'utilObj' => new CakeUtil(),
            'societyObj' => new CakeUtil(),
            'session' => new LegacySession(),
        ];

        // the carried-over CakePHP view reads keys that may be missing; as AccountReportController::legacyView
        set_error_handler(fn () => true);
        try {
            $html = view('society.reports.member-bill-pdf', $data)->render();
        } finally {
            restore_error_handler();
        }

        $output = \Barryvdh\DomPDF\Facade\Pdf::loadHTML(WhatsAppService::sanitizeHtmlForPdf($html))->output();

        $dir = public_path('tmp');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safeFlat = str_replace([' ', '/'], ['_', '-'], (string) $log->flat_no);
        $safeFlat = preg_replace('/[^A-Za-z0-9_.-]/', '_', $safeFlat);
        $path = $dir . DIRECTORY_SEPARATOR . 'Bill_' . $safeFlat . '_' . $log->bill_month . '_' . time() . '_' . $log->id . '.pdf';
        if (file_put_contents($path, $output) === false) {
            throw new \RuntimeException('could not write ' . basename($path));
        }

        return $path;
    }

    private function pdfUrl(string $path): string
    {
        $base = rtrim((string) config('whatsapp.pdf_base_url'), '/');
        if ($base === '') {
            $base = rtrim(url('/'), '/');
        }

        return $base . '/tmp/' . rawurlencode(basename($path));
    }

    // ------------------------------------------------------------------ Interakt webhook

    /**
     * CakePHP: society_bills/whatsapp_webhook - Interakt's message status callback (Sent / Delivered / Read /
     * Failed). Public (no login, no CSRF); only a message id we sent is updated, and a status never goes back
     * (Read is not replaced by Delivered), except Failed.
     */
    public function webhook(Request $request)
    {
        $raw = (string) $request->getContent();
        if ($raw === '') {
            return response()->json(['error' => 'Empty request body'], 400);
        }
        $payload = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            return response()->json(['error' => 'Invalid JSON'], 400);
        }

        $updates = WhatsAppService::parseWebhook($payload);
        if (empty($updates)) {
            return response()->json(['status' => 'ignored', 'reason' => 'no message data']);
        }

        $results = [];
        foreach ($updates as $u) {
            $results[] = $this->applyStatus($u, $raw, $request);
        }

        // a signature that does not match any update's sender is refused as a whole
        foreach ($results as $r) {
            if (($r['error'] ?? '') === 'Invalid signature') {
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        return response()->json(count($results) === 1 ? $results[0] : ['status' => 'ok', 'results' => $results]);
    }

    /** One status update: only a message id we sent, never backwards (Read is not replaced by Delivered), except FAILED. */
    private function applyStatus(array $u, string $raw, Request $request): array
    {
        if ($u['status'] === '') {
            return ['status' => 'ignored', 'reason' => 'unknown status: ' . $u['raw_status']];
        }
        $log = DB::table('whatsapp_bill_logs')->where('api_message_id', $u['id'])->first();
        if (!$log) {
            return ['status' => 'ignored', 'reason' => 'unknown message_id'];
        }

        // each reseller's own account has its own webhook secret
        $secret = (string) WhatsAppService::forSociety((int) $log->society_id)->config('webhook_secret', '');
        $signature = $request->header('Interakt-Signature') ?: $request->header('X-Hub-Signature-256');
        if (!WhatsAppService::signatureOk($raw, $secret, $signature, $request->header('X-Webhook-Secret'))) {
            return ['error' => 'Invalid signature'];
        }

        $dbStatus = $u['status'];
        $current = WhatsAppService::STATUS_PRIORITY[$log->status] ?? -99;
        $new = WhatsAppService::STATUS_PRIORITY[$dbStatus] ?? -99;
        if ($dbStatus !== 'FAILED' && $current >= $new) {
            return ['status' => 'ok', 'action' => 'no_change', 'reason' => 'status already ' . $log->status];
        }

        $now = $this->now();
        $update = ['status' => $dbStatus, 'updated_at' => $now];
        if (in_array($dbStatus, ['DELIVERED', 'READ'], true) && empty($log->delivered_at)) {
            $update['delivered_at'] = $now;
        }
        if ($dbStatus === 'READ' && empty($log->read_at)) {
            $update['read_at'] = $now;
        }
        if ($dbStatus === 'FAILED') {
            $update['error_message'] = $u['error'];
        }

        DB::table('whatsapp_bill_logs')->where('id', $log->id)->update($update);
        if (!empty($log->batch_id)) {
            (new WhatsAppService())->updateBatchCounts($log->batch_id);
        }

        return ['status' => 'ok', 'action' => 'updated', 'new_status' => $dbStatus];
    }
}
