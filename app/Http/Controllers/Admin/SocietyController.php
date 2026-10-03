<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\AccountHead;
use App\Models\BillingFrequency;
use App\Models\InterestType;
use App\Models\Member;
use App\Models\ResellerSociety;
use App\Models\Society;
use App\Models\TariffType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SocietyController extends Controller
{
    public function create(Request $request, $societyId = null)
    {
        $singleSocietyRecord = null;

        if ($societyId) {
            $singleSocietyRecord = User::with('societies')->find($societyId);
        }

        return view('admin.societies.create', compact('singleSocietyRecord'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'access_level' => 'required|in:2,3',
            'username' => 'required|string|max:75',
        ]);

        $userId = $request->input('society_user_id');
        $accessLevel = (int) $request->input('access_level');

        $userData = [
            'username' => $request->input('username'),
            'added_by' => Auth::id(),
            'access_level' => $accessLevel,
            'role' => $accessLevel === 2 ? 'Society' : 'Reseller',
            'status' => 1,
        ];

        if (empty($userId)) {
            $request->validate([
                'password' => 'required|string|min:4',
                'username' => 'required|string|max:75|unique:users,username',
            ]);
            $userData['password'] = Hash::make($request->input('password'));
        } else {
            $existing = User::where('username', $request->input('username'))
                ->where('id', '!=', $userId)
                ->exists();
            if ($existing) {
                return response()->json([
                    'error' => 1,
                    'error_message' => 'This username is already in use. Please use a different one.',
                ]);
            }
        }

        if (!empty($userId)) {
            $user = User::find($userId);
            if ($user) {
                $user->fill($userData);
                $user->save();
            }
        } else {
            $user = User::create($userData);
            $userId = $user->id;
        }

        if ($accessLevel === 2) {
            $existingSociety = Society::where('user_id', $userId)->first();
            if ($existingSociety) {
                $existingSociety->society_name = $request->input('society_name') ?? '';
                $existingSociety->society_code = $request->input('society_code') ?? '';
                $existingSociety->enable_sms = $request->input('enable_sms') ?? 'N';
                $existingSociety->status = 1;
                $existingSociety->save();
                $message = 'Society login has been updated successfully';
            } else {
                $society = new Society();
                $society->id = $userId;
                $society->user_id = $userId;
                $society->society_name = $request->input('society_name') ?? '';
                $society->society_code = $request->input('society_code') ?? '';
                $society->enable_sms = $request->input('enable_sms') ?? 'N';
                $society->cgst_no = '';
                $society->igst_no = '';
                $society->op_balance_saved = 0;
                $society->status = 1;
                $society->save();
                $message = 'Society login has been created successfully';
            }
        } else {
            $message = 'Reseller login has been created successfully';
        }

        return response()->json([
            'error' => 0,
            'error_message' => $message,
        ]);
    }

    public function index()
    {
        $societyData = User::where('added_by', Auth::id())
            ->with('societies')
            ->get();

        return view('admin.societies.index', compact('societyData'));
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->status = 0;
        $user->save();

        return redirect()->route('admin.societies.index')
            ->with('info', 'The society has been deleted.');
    }

    public function parameters()
    {
        $billingFrequencyData = BillingFrequency::pluck('frequency_type');
        $interestTypeData = InterestType::pluck('interest_type');
        $tariffTypeData = TariffType::pluck('tariff_type');
        $accountCategoryData = AccountCategory::pluck('title');
        $accountHeadData = AccountHead::select('title', 'transaction_type')->get();

        return view('admin.societies.parameters', compact(
            'billingFrequencyData',
            'interestTypeData',
            'tariffTypeData',
            'accountCategoryData',
            'accountHeadData'
        ));
    }

    public function assignSocieties(Request $request)
    {
        $resellerList = User::where('added_by', Auth::id())
            ->where('access_level', 3)
            ->orderBy('username')
            ->pluck('username', 'id');

        $societyList = Society::where('status', 1)
            ->where('society_name', '!=', '')
            ->orderBy('society_name')
            ->select('id', 'society_name', 'user_id')
            ->get();

        if ($request->isMethod('post')) {
            $resellerId = $request->input('reseller_id');
            $societyIds = $request->input('societie_id', []);

            ResellerSociety::where('reseller_id', $resellerId)
                ->where('added_by', Auth::id())
                ->delete();

            foreach ($societyIds as $societyId) {
                if (!$societyId) {
                    continue;
                }

                $societyUserID = Society::where('id', $societyId)->value('user_id');

                ResellerSociety::create([
                    'reseller_id' => $resellerId,
                    'added_by' => Auth::id(),
                    'status' => 1,
                    'societie_id' => $societyId,
                    'user_id' => $societyUserID,
                ]);
            }

            return redirect()->route('admin.societies.assign')
                ->with('info', 'Society assigned successfully.');
        }

        return view('admin.societies.assign', compact('resellerList', 'societyList'));
    }

    public function getAssignedSocieties(Request $request)
    {
        $resellerId = $request->input('resellerId');

        if (!$resellerId) {
            return response()->json(['error_flag' => 1, 'error_message' => 'Invalid reseller']);
        }

        $assignedSocietyIds = ResellerSociety::where('reseller_id', $resellerId)
            ->where('added_by', Auth::id())
            ->pluck('societie_id')
            ->toArray();

        $inactiveSocieties = Society::where('status', 0)
            ->where('society_name', '!=', '')
            ->orderBy('society_name')
            ->select('id', 'society_name', 'user_id')
            ->get();

        return response()->json([
            'error_flag' => 0,
            'assignedSocietyList' => array_values($assignedSocietyIds),
            'societyList' => $inactiveSocieties,
        ]);
    }

}
