<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ResellerSociety;
use App\Models\Society;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $countReseller = User::where('access_level', 3)->count();

        $resellerSocietiesCount = ResellerSociety::active()->count();

        $countSocieties = User::where('access_level', 2)->count();

        $allSocietysMemberCount = Member::active()->count();

        $currentYearSocietiesLists = Society::active()
            ->whereYear('cdate', date('Y'))
            ->count();

        $droppedSocietiesLists = Society::where('status', 0)->count();

        $resellerUsers = User::where('access_level', 3)->get();
        $resellerAssignSocietys = [];

        foreach ($resellerUsers as $rCounter => $resellerUser) {
            $resellerAssignSocietys[$rCounter]['resellerData'] = $resellerUser->toArray();

            $assignedSocieties = ResellerSociety::active()
                ->where('reseller_id', $resellerUser->id)
                ->with('society:id,society_name')
                ->get();

            foreach ($assignedSocieties as $aCounter => $assigned) {
                $resellerAssignSocietys[$rCounter]['assignSocietiesData'][$aCounter] = [
                    'id' => $assigned->society->id ?? '',
                    'society_name' => $assigned->society->society_name ?? '',
                ];
            }
        }

        $allSocietiesLists = User::where('status', 1)
            ->where('access_level', 2)
            ->with('societies')
            ->orderBy('username', 'asc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'countSocieties',
            'resellerSocietiesCount',
            'countReseller',
            'allSocietysMemberCount',
            'currentYearSocietiesLists',
            'droppedSocietiesLists',
            'resellerAssignSocietys',
            'allSocietiesLists'
        ));
    }
}
