<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerSociety;
use App\Models\SocietyComplaint;
use App\Support\ResellerContext;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $resellerId = ResellerContext::id();

        if ($request->isMethod('post')) {
            $societyId = $request->input('society_id');

            $isAssigned = ResellerSociety::where('reseller_id', $resellerId)
                ->where('societie_id', $societyId)
                ->where('status', 1)
                ->exists();

            if (!$isAssigned) {
                return redirect()->route('reseller.complaints')
                    ->with('error', 'Please select one of your assigned societies.');
            }

            SocietyComplaint::create([
                'society_id' => $societyId,
                'reseller_id' => $resellerId,
                'complaint_type' => trim((string) $request->input('complaint_type')),
                'description' => trim((string) $request->input('description')),
                'status' => 0,
                'complaint_date' => $request->input('complaint_date') ?: now()->format('Y-m-d'),
            ]);

            return redirect()->route('reseller.complaints')
                ->with('success', 'Complaint logged successfully.');
        }

        $societyList = ResellerSociety::where('reseller_id', $resellerId)
            ->where('status', 1)
            ->whereHas('society', fn ($q) => $q->where('status', 1))
            ->with('society:id,society_name')
            ->get()
            ->mapWithKeys(fn ($rs) => [$rs->societie_id => $rs->society->society_name ?? ('Society #' . $rs->societie_id)])
            ->sort();

        $complaints = SocietyComplaint::where('reseller_id', $resellerId)
            ->with('society:id,society_name')
            ->orderByDesc('cdate')
            ->get();

        foreach ($complaints as $c) {
            $c->society_name = $c->society->society_name ?? ('Society #' . $c->society_id);
        }

        return view('reseller.complaints', compact('complaints', 'societyList'));
    }

    public function resolve(Request $request, $id)
    {
        $complaint = SocietyComplaint::where('id', $id)->where('reseller_id', ResellerContext::id())->firstOrFail();

        $complaint->status = 2;
        $complaint->resolution = trim((string) $request->input('resolution'));
        $complaint->resolved_date = now()->format('Y-m-d');
        $complaint->save();

        return redirect()->route('reseller.complaints')->with('success', 'Complaint marked resolved.');
    }

    public function confirm($id)
    {
        $complaint = SocietyComplaint::where('id', $id)->where('reseller_id', ResellerContext::id())->firstOrFail();

        $complaint->status = 2;
        $complaint->resolved_date = now()->format('Y-m-d');
        $complaint->save();

        return redirect()->route('reseller.complaints')->with('success', "Thanks for confirming - complaint closed.");
    }

    public function reject(Request $request, $id)
    {
        $complaint = SocietyComplaint::where('id', $id)->where('reseller_id', ResellerContext::id())->firstOrFail();

        $reason = trim((string) $request->input('reject_reason'));
        $note = $reason !== '' ? ('Reopened by reseller: ' . $reason) : 'Reopened by reseller - issue not actually fixed.';

        $complaint->status = 0;
        $complaint->resolution = $note;
        $complaint->resolved_date = null;
        $complaint->save();

        return redirect()->route('reseller.complaints')->with('success', 'Complaint reopened and sent back as pending.');
    }
}
