<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\SocietyParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Month-wise interest rates of a society (CakePHP MonthInterestRatesController, table society_month_interest_rates).
 *
 * A month with no rate here (or a society with none at all) keeps using the Interest Rate of Society Parameters,
 * which this screen never changes. Until the table exists the page says so instead of listing anything.
 */
class MonthInterestRateController extends Controller
{
    private const TABLE = 'society_month_interest_rates';

    private const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public function index(Request $request)
    {
        $societyId = (int) $request->user()->id;
        $migrated = Schema::hasTable(self::TABLE);

        if ($request->isMethod('post') && $migrated) {
            return $this->handlePost($request, $societyId);
        }

        $rows = $migrated
            ? DB::table(self::TABLE)->where('society_id', $societyId)->orderByDesc('rate_year')->orderByDesc('rate_month')->get()
            : collect();

        $parameterRate = SocietyParameter::where('society_id', $societyId)->value('interest_rate');

        return view('society.modules.month-interest-rates', [
            'migrated' => $migrated,
            'rows' => $rows,
            'parameterRate' => $parameterRate,
            'months' => self::MONTHS,
            'thisYear' => (int) date('Y'),
        ]);
    }

    private function handlePost(Request $request, int $societyId)
    {
        $back = redirect()->route('society.monthInterestRates');

        if (!empty($request->input('delete_id'))) {
            $ok = DB::table(self::TABLE)
                ->where('id', (int) $request->input('delete_id'))->where('society_id', $societyId)->delete() > 0;

            return $back->with($ok ? 'success' : 'error', $ok
                ? 'The rate was removed. That month now uses the Interest Rate of Society Parameters.'
                : 'The rate could not be removed.');
        }

        $rate = trim((string) $request->input('interest_rate', ''));
        $fromYear = (int) $request->input('from_year', 0);
        $fromMonth = (int) $request->input('from_month', 0);
        $toYear = (int) ($request->input('to_year') !== null && $request->input('to_year') !== '' ? $request->input('to_year') : $fromYear);
        $toMonth = (int) ($request->input('to_month') !== null && $request->input('to_month') !== '' ? $request->input('to_month') : $fromMonth);

        if ($rate === '' || !is_numeric($rate) || $rate < 0 || $rate > 100) {
            return $back->with('error', 'Interest rate must be a number from 0 to 100.');
        }
        if ($fromYear < 2000 || $fromYear > 2100 || $toYear < 2000 || $toYear > 2100
            || $fromMonth < 1 || $fromMonth > 12 || $toMonth < 1 || $toMonth > 12) {
            return $back->with('error', 'Choose a valid month and year.');
        }

        $fromIndex = $fromYear * 12 + $fromMonth;
        $toIndex = $toYear * 12 + $toMonth;
        if ($toIndex < $fromIndex || ($toIndex - $fromIndex) >= 60) {
            return $back->with('error', '"To" must not be before "From", and a range can cover at most 60 months.');
        }

        $saved = 0;
        $now = date('Y-m-d H:i:s');
        for ($i = $fromIndex; $i <= $toIndex; $i++) {
            $year = (int) floor(($i - 1) / 12);
            $month = $i - $year * 12;

            try {
                $where = ['society_id' => $societyId, 'rate_year' => $year, 'rate_month' => $month];
                if (DB::table(self::TABLE)->where($where)->exists()) {
                    DB::table(self::TABLE)->where($where)->update(['interest_rate' => round((float) $rate, 2), 'is_active' => 1, 'udate' => $now]);
                } else {
                    DB::table(self::TABLE)->insert($where + ['interest_rate' => round((float) $rate, 2), 'is_active' => 1, 'cdate' => $now, 'udate' => $now]);
                }
                $saved++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $total = $toIndex - $fromIndex + 1;
        if ($saved === $total) {
            return $back->with('success', $saved . ' month(s) saved at ' . number_format((float) $rate, 2) . '%.');
        }

        return $back->with('error', 'Only ' . $saved . ' of ' . $total . ' months could be saved.');
    }
}
