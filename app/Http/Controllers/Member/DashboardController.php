<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member as MemberModel;
use App\Models\MemberBillSettlement;
use App\Models\MemberBillSummary;
use App\Models\MemberPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $societyId = $request->session()->get('society_id');
        $memberData = $request->session()->get('member', []);
        $memberId = $memberData['id'] ?? null;

        $societyMemberDetails = ['status' => 1];
        $societyMemberBillPaymentData = [];

        if ($memberId) {
            $memberRecord = MemberModel::where('id', $memberId)
                ->where('society_id', $societyId)
                ->where('status', 1)
                ->first();

            if ($memberRecord) {
                $societyMemberDetails = $memberRecord->toArray();
            }

            $billSummaries = MemberBillSummary::where('society_id', $societyId)
                ->where('member_id', $memberId)
                ->get();

            $paymentIds = [];

            foreach ($billSummaries as $bill) {
                $settlements = MemberBillSettlement::where('bill_summary_id', $bill->id)
                    ->where('member_id', $memberId)
                    ->get();

                foreach ($settlements as $settlement) {
                    $paymentIds[] = $settlement->payment_id;
                }
            }

            $paymentIds = array_unique(array_filter($paymentIds));

            if (!empty($paymentIds)) {
                $societyMemberBillPaymentData = MemberPayment::whereIn('id', $paymentIds)
                    ->get()
                    ->toArray();
            }
        }

        return view('member.dashboard', compact(
            'societyMemberDetails',
            'societyMemberBillPaymentData'
        ));
    }
}
