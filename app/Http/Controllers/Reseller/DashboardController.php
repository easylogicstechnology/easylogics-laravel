<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Reseller;
use App\Models\ResellerPayment;
use App\Models\ResellerSociety;
use App\Models\Society;
use App\Models\SocietyComplaint;
use App\Support\ResellerContext;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $resellerId = ResellerContext::id();
        $currentYear = date('Y');
        $yearStart = $currentYear . '-01-01';

        $sellerInfo = Reseller::where('user_id', $resellerId)->first();
        $subscriptionExpiry = $sellerInfo->subscription_expiry ?? null;

        $awaitingConfirmationCount = SocietyComplaint::where('reseller_id', $resellerId)
            ->where('status', 1)
            ->count();

        $assignedSocieties = ResellerSociety::where('reseller_id', $resellerId)
            ->with('society:id,society_name,society_code,status,cdate')
            ->get();

        // A team login only gets the societies its reseller ticked for it - deny by default.
        $allowedSocietyIds = ResellerContext::allowedSocietyIds();
        if ($allowedSocietyIds !== null) {
            $assignedSocieties = $assignedSocieties->filter(
                fn ($row) => in_array((int) $row->societie_id, $allowedSocietyIds, true)
            )->values();
        }

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

        return view('reseller.dashboard', compact(
            'assignedSocieties',
            'totalSocieties',
            'activeSocieties',
            'totalMembers',
            'newThisYear',
            'droppedThisYear',
            'societyMemberCounts',
            'subscriptionExpiry',
            'awaitingConfirmationCount'
        ));
    }

    public function paymentDashboard()
    {
        $resellerId = ResellerContext::id();

        $sellerInfo = Reseller::where('user_id', $resellerId)->first();
        $purchaseDate = $sellerInfo->cdate ?? null;
        $subscriptionExpiry = $sellerInfo->subscription_expiry ?? null;

        $payments = ResellerPayment::where('reseller_id', $resellerId)
            ->orderByDesc('payment_date')
            ->get();

        return view('reseller.paymentDashboard', compact('purchaseDate', 'subscriptionExpiry', 'payments'));
    }
}
