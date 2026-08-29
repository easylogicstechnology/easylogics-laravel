<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ResellerSociety;
use App\Models\Society;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $resellerId = Auth::id();
        $reseller = Auth::user();
        $currentYear = date('Y');
        $yearStart = $currentYear . '-01-01';

        $assignedSocieties = ResellerSociety::where('reseller_id', $resellerId)
            ->with('society:id,society_name,society_code,status,cdate')
            ->get();

        $societyIds = $assignedSocieties->pluck('societie_id')->all();

        $totalSocieties = $assignedSocieties->count();
        $activeSocieties = 0;
        $newThisYear = 0;
        $droppedThisYear = 0;

        foreach ($assignedSocieties as $assigned) {
            if ($assigned->society && $assigned->society->status == 1) {
                $activeSocieties++;
            }
            if ($assigned->society && $assigned->society->status == 0) {
                $droppedThisYear++;
            }
            if ($assigned->society && $assigned->society->cdate && $assigned->society->cdate >= $yearStart) {
                $newThisYear++;
            }
        }

        $totalMembers = 0;
        $societyMemberCounts = [];
        if (!empty($societyIds)) {
            $totalMembers = Member::whereIn('society_id', $societyIds)->count();

            $counts = Member::whereIn('society_id', $societyIds)
                ->select('society_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('society_id')
                ->pluck('cnt', 'society_id')
                ->toArray();
            $societyMemberCounts = $counts;
        }

        $creditInfo = [
            'credit' => $reseller->member_credit,
            'used' => $totalMembers,
            'remaining' => $reseller->member_credit > 0 ? max(0, $reseller->member_credit - $totalMembers) : 0,
        ];

        return view('reseller.dashboard', compact(
            'assignedSocieties',
            'totalSocieties',
            'activeSocieties',
            'totalMembers',
            'newThisYear',
            'droppedThisYear',
            'societyMemberCounts',
            'creditInfo'
        ));
    }
}
