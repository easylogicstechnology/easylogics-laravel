<?php

namespace App\Services;

use App\Models\FinancialYearMaster;
use App\Models\SocietyYearMapping;
use Illuminate\Http\Request;

class FinancialYearService
{
    public function loadForSociety(Request $request, int $societyId): void
    {
        if ($request->session()->has('fy.year_id')) {
            return;
        }

        $assignedYearIds = SocietyYearMapping::where('society_id', $societyId)
            ->where('is_active', 1)
            ->pluck('year_id')
            ->all();

        if (empty($assignedYearIds)) {
            return;
        }

        $today = date('Y-m-d');

        $currentFy = FinancialYearMaster::whereIn('id', $assignedYearIds)
            ->where('is_active', 1)
            ->where('year_start_date', '<=', $today)
            ->where('year_end_date', '>=', $today)
            ->first();

        if (!$currentFy) {
            $currentFy = FinancialYearMaster::whereIn('id', $assignedYearIds)
                ->where('is_active', 1)
                ->orderByDesc('year_start_date')
                ->first();
        }

        if (!$currentFy) {
            return;
        }

        $from = $currentFy->year_start_date;
        $to = $currentFy->year_end_date;

        $request->session()->put('fy.year_id', $currentFy->id);
        $request->session()->put('fy.year_start_date', $from);
        $request->session()->put('fy.year_end_date', $to);
        $request->session()->put('fy.formated_year_start_date', date('d/m/Y', strtotime($from)));
        $request->session()->put('fy.formated_year_end_date', date('d/m/Y', strtotime($to)));
        $request->session()->put('fy.start_year', date('Y', strtotime($from)));
        $request->session()->put('fy.end_year', date('Y', strtotime($to)));
        $request->session()->put('fy.financial_years_arr', [$from, $to]);
    }
}
