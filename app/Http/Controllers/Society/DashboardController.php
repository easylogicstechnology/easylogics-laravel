<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\FinancialYearMaster;
use App\Models\Member;
use App\Models\MemberBillSummary;
use App\Models\MemberPayment;
use App\Models\SocietyPayment;
use App\Models\SocietyYearMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $societyId = Auth::id();

        if (!$request->session()->has('fy.year_id')) {
            app(\App\Services\FinancialYearService::class)->loadForSociety($request, $societyId);
        }

        $financialYearId = $request->session()->get('fy.year_id');

        $society = \App\Models\Society::where('user_id', $societyId)->first();

        $assignedYearIds = SocietyYearMapping::where('society_id', $societyId)
            ->where('is_active', 1)
            ->pluck('year_id')
            ->toArray();

        $assignedYears = [];
        if (!empty($assignedYearIds)) {
            $assignedYears = FinancialYearMaster::whereIn('id', $assignedYearIds)
                ->where('is_active', 1)
                ->orderBy('year_start_date')
                ->get();
        }

        $societyCollectionSummary = [];

        $cashPayment = SocietyPayment::where('society_id', $societyId)
            ->where('payment_type', 'Cash')
            ->where('financial_year_id', $financialYearId)
            ->sum('amount');
        $societyCollectionSummary['cashPayment'] = $cashPayment ?? 0;

        $bankPayment = SocietyPayment::where('society_id', $societyId)
            ->where('payment_type', 'Bank')
            ->where('financial_year_id', $financialYearId)
            ->sum('amount');
        $societyCollectionSummary['bankPayment'] = $bankPayment ?? 0;

        $societyFlatsSummary = [];
        $societyFlatsSummary['flatCount'] = Member::where('society_id', $societyId)->active()->count();
        $societyFlatsSummary['commercialFlats'] = Member::where('society_id', $societyId)
            ->active()->where('unit_type', 'C')->count();
        $societyFlatsSummary['residentialCount'] = Member::where('society_id', $societyId)
            ->active()->where('unit_type', 'R')->count();

        $totalExpenses = SocietyPayment::where('society_id', $societyId)
            ->active()
            ->where('financial_year_id', $financialYearId)
            ->sum('amount');

        $getSocietyExpensesDetails = SocietyPayment::where('society_id', $societyId)
            ->active()
            ->where('financial_year_id', $financialYearId)
            ->select('id', 'particulars', 'amount')
            ->orderByDesc('payment_date')
            ->limit(5)
            ->get();

        $getDuesFromMemberDetails = MemberBillSummary::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->with(['member:id,member_name,flat_no'])
            ->select('id', 'flat_no', 'member_id', 'amount_payable')
            ->orderByDesc('amount_payable')
            ->limit(5)
            ->get();

        $totalOutstanding = MemberBillSummary::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->sum('amount_payable');

        $totalCollection = MemberPayment::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->sum('amount_paid');

        $getReceiptDetails = MemberPayment::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->with(['member:id,member_name,flat_no'])
            ->select('id', 'member_id', 'bill_generated_id', 'amount_paid')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $getSocietyLatestPaymentDetails = SocietyPayment::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->active()
            ->select('id', 'payment_date', 'particulars', 'amount')
            ->orderByDesc('payment_date')
            ->limit(5)
            ->get();

        $dashboardBillSummaryDetails = [];
        $monthList = MemberBillSummary::where('society_id', $societyId)
            ->where('financial_year_id', $financialYearId)
            ->distinct()
            ->orderBy('month', 'asc')
            ->limit(5)
            ->pluck('month')
            ->all();

        foreach ($monthList as $month) {
            $summary = MemberBillSummary::where('society_id', $societyId)
                ->where('financial_year_id', $financialYearId)
                ->where('month', $month)
                ->selectRaw('SUM(amount_payable) as amount_payable, SUM(principal_paid) as principal_paid, SUM(op_due_amount) as op_due_amount')
                ->first();

            $dashboardBillSummaryDetails[$month] = [
                'amount' => $summary->amount_payable ?? 0,
                'collectionAmount' => $summary->principal_paid ?? 0,
                'dueAmount' => $summary->op_due_amount ?? 0,
                'monthName' => date('F', mktime(0, 0, 0, (int)$month, 1)),
            ];
        }

        return view('society.dashboard', compact(
            'society',
            'societyFlatsSummary',
            'societyCollectionSummary',
            'getSocietyExpensesDetails',
            'getDuesFromMemberDetails',
            'getReceiptDetails',
            'getSocietyLatestPaymentDetails',
            'dashboardBillSummaryDetails',
            'assignedYears',
            'financialYearId',
            'totalOutstanding',
            'totalExpenses',
            'totalCollection'
        ));
    }

    public function changeFinancialYear(Request $request, $yearId)
    {
        $societyId = Auth::id();
        $yearId = (int) $yearId;

        if ($societyId && $yearId > 0) {
            $assigned = SocietyYearMapping::where('society_id', $societyId)
                ->where('year_id', $yearId)
                ->where('is_active', 1)
                ->exists();

            if ($assigned) {
                $fy = FinancialYearMaster::where('id', $yearId)
                    ->where('is_active', 1)
                    ->first();

                if ($fy) {
                    $from = $fy->year_start_date;
                    $to = $fy->year_end_date;
                    $request->session()->put('fy.year_id', $fy->id);
                    $request->session()->put('fy.year_start_date', $from);
                    $request->session()->put('fy.year_end_date', $to);
                    $request->session()->put('fy.formated_year_start_date', date('d/m/Y', strtotime($from)));
                    $request->session()->put('fy.formated_year_end_date', date('d/m/Y', strtotime($to)));
                    $request->session()->put('fy.start_year', date('Y', strtotime($from)));
                    $request->session()->put('fy.end_year', date('Y', strtotime($to)));
                    $request->session()->put('fy.financial_years_arr', [$from, $to]);

                    return redirect()->route('society.dashboard')
                        ->with('info', 'Financial year changed to ' . $fy->year . '.');
                }
            }
        }

        return redirect()->route('society.dashboard')
            ->with('error', 'Could not change financial year.');
    }
}
