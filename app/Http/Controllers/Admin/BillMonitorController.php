<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\AdminBillMonitor;
use Illuminate\Http\Request;

class BillMonitorController extends Controller
{
    /** Month-by-month totals of a financial year. */
    public function months(Request $request, AdminBillMonitor $monitor)
    {
        return response()->json($monitor->monthly((int) $request->query('fy')));
    }

    /** One month: totals, resellers and societies. */
    public function month(Request $request, AdminBillMonitor $monitor)
    {
        return response()->json($monitor->month((int) $request->query('fy'), (int) $request->query('month')));
    }

    /** Bill Summary Update check of one society. */
    public function check(Request $request, AdminBillMonitor $monitor)
    {
        return response()->json($monitor->check((int) $request->query('society_id'), (int) $request->query('fy'), $request->boolean('refresh')));
    }
    /** Reconcile one society's members: list=1 for the member list, else preview (or save=1 update) of member_ids[]. */
    public function reconcile(Request $request, AdminBillMonitor $monitor)
    {
        $societyId = (int) $request->input('society_id');
        if ($request->boolean('list')) {
            return response()->json(['success' => true, 'members' => $monitor->members($societyId)]);
        }

        return response()->json($monitor->reconcile(
            $societyId,
            (int) $request->input('fy'),
            array_values(array_filter(array_map('intval', (array) $request->input('member_ids', [])))),
            (string) $request->input('save') === '1'
        ));
    }
}
