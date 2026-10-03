<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ResellerSociety;
use App\Models\Society;
use App\Models\User;
use App\Services\FinancialYearService;
use App\Support\ResellerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SocietyController extends Controller
{
    public function create(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'society_name' => 'required|string|max:100',
                'address' => 'required|string|max:300',
            ]);

            $resellerId = ResellerContext::id();
            $societyName = $request->input('society_name');

            $existingUser = User::where('username', $societyName)->first();
            if ($existingUser) {
                return redirect()->route('reseller.societies.create')
                    ->with('error', 'A society with this name already exists. Please use a different name.');
            }

            $user = User::create([
                'username' => $societyName,
                'password' => Hash::make('12345'),
                'added_by' => $resellerId,
                'access_level' => 2,
                'role' => 'Society',
                'status' => 1,
            ]);

            $society = new Society();
            $society->id = $user->id;
            $society->user_id = $user->id;
            $society->society_name = $societyName;
            $society->society_code = $request->input('society_code') ?? '';
            $society->registration_no = $request->input('registration_no') ?? '';
            $society->registration_date = $request->input('registration_date') ?: null;
            $society->address = $request->input('address') ?? '';
            $society->telephone_no = $request->input('telephone_no') ?? '';
            $society->fax_no = $request->input('fax_no') ?: null;
            $society->email_id = $request->input('email_id') ?? '';
            $society->url = $request->input('url') ?? '';
            $society->tan_no = $request->input('tan_no') ?? '';
            $society->pan_no = $request->input('pan_no') ?? '';
            $society->circle = $request->input('circle') ?? '';
            $society->service_tax_no = $request->input('service_tax_no') ?? '';
            $society->gstin_no = $request->input('gstin_no') ?? '';
            $society->cgst_no = $request->input('cgst_no') ?? '';
            $society->igst_no = $request->input('igst_no') ?? '';
            $society->is_conveyance = $request->input('is_conveyance') ?? 0;
            $society->conveynace_date = $request->input('conveynace_date') ?: null;
            $society->authorised_person = $request->input('authorised_person') ?? '';
            $society->enable_sms = 'N';
            $society->op_balance_saved = 0;
            $society->status = (int) ($request->input('status') ?? 1);
            $society->save();

            $existingAssign = ResellerSociety::where('societie_id', $user->id)
                ->where('reseller_id', $resellerId)
                ->where('added_by', $resellerId)
                ->exists();

            if (!$existingAssign) {
                ResellerSociety::create([
                    'societie_id' => $user->id,
                    'user_id' => $user->id,
                    'reseller_id' => $resellerId,
                    'added_by' => $resellerId,
                    'status' => 1,
                ]);
            }

            return redirect()->route('reseller.societies.create')
                ->with('info', 'Society Created Successfully.');
        }

        return view('reseller.societies.create');
    }

    public function switchToSociety(Request $request, $societyId)
    {
        $resellerId = ResellerContext::id();

        // A team login may only open the societies its reseller ticked for it (Permissions page).
        $allowedSocietyIds = ResellerContext::allowedSocietyIds();
        if ($allowedSocietyIds !== null && !in_array((int) $societyId, $allowedSocietyIds, true)) {
            return redirect()->route('reseller.societies.assigned')
                ->with('error', 'You do not have access to that society.');
        }

        $assigned = ResellerSociety::where('reseller_id', $resellerId)
            ->where('societie_id', $societyId)
            ->first();

        if (!$assigned) {
            return redirect()->route('reseller.dashboard')
                ->with('error', 'You do not have access to this society.');
        }

        $societyUser = User::where('id', $societyId)
            ->where('role', 'Society')
            ->first();

        if (!$societyUser) {
            return redirect()->route('reseller.dashboard')
                ->with('error', 'Society user not found.');
        }

        $request->session()->put('reseller_id', $resellerId);
        // The login to return to (for a team login this is not the reseller itself)
        $request->session()->put('reseller_login_id', Auth::id());
        $request->session()->put('reseller_username', Auth::user()->username);

        $request->session()->forget('fy');

        Auth::loginUsingId($societyUser->id);

        $yearId = $request->input('financial_year_id');
        if ($yearId) {
            $fy = \App\Models\FinancialYearMaster::where('id', $yearId)
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
            }
        }

        if (!$request->session()->has('fy.year_id')) {
            app(FinancialYearService::class)->loadForSociety($request, $societyUser->id);
        }

        return redirect()->route('society.dashboard');
    }

    public static function switchBack(Request $request)
    {
        $resellerId = $request->session()->get('reseller_id');

        if (!$resellerId) {
            return redirect()->route('login');
        }

        $loginId = $request->session()->get('reseller_login_id', $resellerId);

        $request->session()->forget('reseller_id');
        $request->session()->forget('reseller_login_id');
        $request->session()->forget('reseller_username');
        $request->session()->forget('fy');

        Auth::loginUsingId($loginId);

        return redirect()->route('reseller.dashboard');
    }

    public function assigned(Request $request)
    {
        $resellerId = ResellerContext::id();

        $assignedSocieties = ResellerSociety::where('reseller_id', $resellerId)
            ->with(['society:id,society_name,society_code,user_id,udate,enable_sms,status', 'society.user:id,username'])
            ->orderBy('id')
            ->get();

        // A team login only sees the societies its reseller ticked for it - deny by default.
        $allowedSocietyIds = ResellerContext::allowedSocietyIds();
        if ($allowedSocietyIds !== null) {
            $assignedSocieties = $assignedSocieties->filter(
                fn ($row) => in_array((int) optional($row->society)->user_id, $allowedSocietyIds, true)
            )->values();
        }

        $societyMemberCounts = [];
        foreach ($assignedSocieties as $assigned) {
            if ($assigned->society) {
                $societyMemberCounts[$assigned->societie_id] = Member::where('society_id', $assigned->societie_id)->count();
            }
        }

        return view('reseller.societies.assigned', compact('assignedSocieties', 'societyMemberCounts'));
    }

    public function getAssignedYears($societyId)
    {
        $resellerId = ResellerContext::id();

        $assigned = ResellerSociety::where('reseller_id', $resellerId)
            ->where('societie_id', $societyId)
            ->exists();

        if (!$assigned) {
            return response()->json([]);
        }

        $yearIds = \App\Models\SocietyYearMapping::where('society_id', $societyId)
            ->where('is_active', 1)
            ->pluck('year_id')
            ->toArray();

        $years = \App\Models\FinancialYearMaster::whereIn('id', $yearIds)
            ->where('is_active', 1)
            ->orderByDesc('year_start_date')
            ->get(['id', 'year', 'year_start_date', 'year_end_date']);

        return response()->json($years);
    }
}
