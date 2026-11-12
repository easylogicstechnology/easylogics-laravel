<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin dashboard "Bill Summary Monitor": for a billing month, which societies generated bills (grouped by
 * reseller), and whether each society's Bill Summary Update (BillSummaryUpdateReport) shows correct or wrong
 * member balances. The balance check is expensive (a trial balance per society), so it is run one society at
 * a time on request and the result is cached.
 */
class AdminBillMonitor
{
    private const CHECK_TTL = 21600; // 6 hours

    /** The financial years that have bills, newest first: [id => year label]. */
    public function years(): array
    {
        $ids = DB::table('member_bill_summaries')->distinct()->pluck('financial_year_id');

        return DB::table('financial_year_master')->whereIn('id', $ids)->orderByDesc('id')->pluck('year', 'id')->all();
    }

    /** Month-by-month totals of one financial year: societies / bills generated. */
    public function monthly(int $fyId): array
    {
        $rows = DB::table('member_bill_summaries')->where('financial_year_id', $fyId)
            ->groupBy('month')->select('month', DB::raw('count(*) as bills'), DB::raw('count(distinct society_id) as societies'))->get();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->month] = ['month' => (int) $r->month, 'bills' => (int) $r->bills, 'societies' => (int) $r->societies];
        }
        uksort($out, fn ($a, $b) => (($a + 8) % 12) <=> (($b + 8) % 12)); // April first

        return array_values($out);
    }

    /** One month: totals, per-reseller breakup and the society list. */
    public function month(int $fyId, int $month): array
    {
        $bySociety = DB::table('member_bill_summaries as b')
            ->leftJoin('societies as s', 's.id', '=', 'b.society_id')
            ->where('b.financial_year_id', $fyId)->where('b.month', $month)
            ->groupBy('b.society_id', 's.society_name')
            ->select('b.society_id', 's.society_name', DB::raw('count(*) as bills'), DB::raw('count(distinct b.member_id) as members'))
            ->orderBy('s.society_name')->get();

        $resellerOf = $this->resellersOf($bySociety->pluck('society_id')->all());

        $societies = [];
        $resellers = [];
        $totalBills = 0;
        foreach ($bySociety as $r) {
            $names = $resellerOf[$r->society_id] ?? [];
            $societies[] = [
                'id' => (int) $r->society_id, 'name' => $r->society_name ?: ('Society #' . $r->society_id),
                'bills' => (int) $r->bills, 'members' => (int) $r->members, 'resellers' => array_values($names),
                'check' => Cache::store('file')->get($this->key((int) $r->society_id, $fyId)),
            ];
            $totalBills += (int) $r->bills;
            foreach ($names ?: [0 => 'Direct (no reseller)'] as $rid => $rname) {
                $resellers[$rid]['name'] = $rname;
                $resellers[$rid]['societies'] = ($resellers[$rid]['societies'] ?? 0) + 1;
                $resellers[$rid]['bills'] = ($resellers[$rid]['bills'] ?? 0) + (int) $r->bills;
                $resellers[$rid]['societyIds'][] = (int) $r->society_id;
            }
        }
        uasort($resellers, fn ($a, $b) => $b['bills'] <=> $a['bills']);

        return [
            'fyId' => $fyId, 'month' => $month, 'totalBills' => $totalBills, 'totalSocieties' => count($societies),
            'resellerCount' => count(array_filter(array_keys($resellers))), 'resellers' => array_values($resellers), 'societies' => $societies,
        ];
    }

    /** Run (or load from cache) the Bill Summary Update check of one society: correct / wrong members and the wrong list. */
    public function check(int $societyId, int $fyId, bool $refresh = false): array
    {
        $key = $this->key($societyId, $fyId);
        $cache = Cache::store('file');
        if (!$refresh && ($hit = $cache->get($key))) {
            return $hit;
        }

        $fy = DB::table('financial_year_master')->where('id', $fyId)->first(['year_start_date', 'year_end_date']);
        if (!$fy) {
            return ['error' => 'Financial year not found'];
        }
        @set_time_limit(300);
        try {
            $data = (new BillSummaryUpdateReport($societyId, $fyId, $fy->year_start_date, $fy->year_end_date))->run();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }

        $correct = 0;
        $wrong = [];
        foreach ($data['societyMemberDetails'] as $d) {
            if ($d['difference'] == 0) {
                $correct++;
                continue;
            }
            $wrong[] = [
                'flat' => $d['flat_no'], 'member' => $d['member_name'],
                'closing' => number_format(abs($d['closing2018']), 2) . $d['closingCrDr'],
                'opening' => $d['opening2019'] . $d['openingCrDr'],
                'difference' => number_format(abs($d['difference']), 2) . $d['differenceCrDr'],
            ];
        }
        $result = ['correct' => $correct, 'wrong' => count($wrong), 'rows' => $wrong, 'checkedAt' => date('d-m-Y H:i')];
        $cache->put($key, $result, self::CHECK_TTL);

        return $result;
    }

    /** The society's active members for the reconcile panel: [{id, label}]. */
    public function members(int $societyId): array
    {
        return DB::table('members')->where('society_id', $societyId)->where('status', 1)->orderBy('id')->get(['id', 'member_name', 'flat_no'])
            ->map(fn ($m) => ['id' => (int) $m->id, 'label' => trim($m->flat_no . ' - ' . $m->member_name)])->all();
    }

    /**
     * Member Balance Reconciliation of one society (same engine as Bill Summary Update's "Check All & Select Wrong",
     * "Preview Reconciliation", "Confirm & Update Selected"): per member what the last bill should carry; with $save the
     * last bill row is rewritten.
     */
    public function reconcile(int $societyId, int $fyId, array $memberIds, bool $save): array
    {
        $fy = DB::table('financial_year_master')->where('id', $fyId)->first(['year_start_date', 'year_end_date']);
        if (!$fy) {
            return ['success' => false, 'error' => 'Financial year not found'];
        }
        $valid = DB::table('members')->where('society_id', $societyId)->where('status', 1)->whereIn('id', $memberIds)->pluck('id')->all();
        if (!$valid) {
            return ['success' => false, 'error' => 'No members selected'];
        }
        @set_time_limit(900);
        $report = new BillSummaryUpdateReport($societyId, $fyId, $fy->year_start_date, $fy->year_end_date);
        $results = [];
        foreach ($valid as $memberId) {
            try {
                $results[$memberId] = $report->reconcile((int) $memberId, $save);
            } catch (\Throwable $e) {
                $results[$memberId] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        if ($save) {
            Cache::store('file')->forget($this->key($societyId, $fyId)); // the saved check result is stale now
        }

        return ['success' => true, 'saved' => $save, 'results' => $results];
    }

    private function key(int $societyId, int $fyId): string
    {
        return "admin_bill_monitor.$fyId.$societyId";
    }

    /** [society_id => [reseller_id => name]] for real resellers (access_level 3) only. */
    private function resellersOf(array $societyIds): array
    {
        $out = [];
        foreach (array_chunk($societyIds, 1000) as $chunk) {
            $rows = DB::table('reseller_societies as rs')
                ->join('users as u', 'u.id', '=', 'rs.reseller_id')
                ->where('rs.status', 1)->where('u.access_level', 3)->whereIn('rs.societie_id', $chunk)
                ->select('rs.societie_id', 'u.id', 'u.username', 'u.full_name')->get();
            foreach ($rows as $r) {
                $out[$r->societie_id][$r->id] = $r->full_name ?: $r->username;
            }
        }

        return $out;
    }
}
