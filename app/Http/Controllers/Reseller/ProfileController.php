<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\FinancialYearMaster;
use App\Models\Reseller;
use App\Models\ResellerSociety;
use App\Models\Society;
use App\Models\SocietyYearMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $resellerId = Auth::id();
        $profile = Reseller::where('user_id', $resellerId)->first();

        if ($request->isMethod('post')) {
            $request->validate([
                'firstname' => 'required|string|max:50',
                'lastname' => 'required|string|max:50',
                'email' => 'required|email|max:50',
                'contact_no' => 'required|string|max:15',
            ]);

            $data = [
                'user_id' => $resellerId,
                'firstname' => $request->input('firstname') ?? '',
                'lastname' => $request->input('lastname') ?? '',
                'email' => $request->input('email') ?? '',
                'contact_no' => $request->input('contact_no') ?? '',
                'date_of_birth' => $request->input('date_of_birth') ?? '',
                'address' => $request->input('address') ?? '',
                'city' => $request->input('city') ?? '',
                'state' => (int) ($request->input('state') ?? 0),
                'country' => (int) ($request->input('country') ?? 0),
                'license_no' => $request->input('license_no') ?? '',
                'job_role' => $request->input('job_role') ?? '',
                'status' => 1,
            ];

            if ($profile) {
                $profile->fill($data);
                $profile->save();
            } else {
                Reseller::create($data);
            }

            return redirect()->route('reseller.profile')
                ->with('info', 'Profile updated successfully.');
        }

        $countries = \Illuminate\Support\Facades\DB::table('countries')->orderBy('country_name')->get();
        $states = \Illuminate\Support\Facades\DB::table('states')->orderBy('state_name')->get();

        return view('reseller.profile', compact('profile', 'countries', 'states'));
    }

    public function financeYearMapping(Request $request)
    {
        $resellerId = Auth::id();

        $societyIds = ResellerSociety::where('reseller_id', $resellerId)
            ->pluck('societie_id')
            ->toArray();

        $societies = Society::whereIn('id', $societyIds)
            ->where('status', 1)
            ->orderBy('society_name')
            ->select('id', 'society_name')
            ->get();

        $financialYears = FinancialYearMaster::where('is_active', 1)
            ->orderBy('year_start_date', 'desc')
            ->get();

        if ($request->isMethod('post')) {
            $request->validate([
                'society_id' => 'required|integer',
                'financial_year_id' => 'required|integer',
            ]);

            $societyId = $request->input('society_id');
            $yearId = $request->input('financial_year_id');

            $exists = SocietyYearMapping::where('society_id', $societyId)
                ->where('year_id', $yearId)
                ->exists();

            if ($exists) {
                return redirect()->route('reseller.financeYearMapping')
                    ->with('error', 'This society is already mapped to this financial year.');
            }

            SocietyYearMapping::create([
                'society_id' => $societyId,
                'year_id' => $yearId,
                'is_active' => 1,
            ]);

            return redirect()->route('reseller.financeYearMapping')
                ->with('info', 'Society mapped to financial year successfully.');
        }

        $mappings = SocietyYearMapping::whereIn('society_id', $societyIds)
            ->with(['society:id,society_name'])
            ->orderBy('id', 'desc')
            ->get();

        $yearMap = $financialYears->keyBy('id');

        return view('reseller.finance_year_mapping', compact('societies', 'financialYears', 'mappings', 'yearMap'));
    }
}
