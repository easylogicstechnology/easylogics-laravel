<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Models\Society;
use App\Support\ReportUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Port of CakePHP RegistersController: FD Register (+ add/edit), Shares Register,
 * Lien Register, Nominee Register, Form I and Form J.
 * Lien / Nominee are "Coming Soon" placeholders in Cake as well.
 */
class RegisterController extends Controller
{
    private const FD_FIELDS = [
        'fd_no', 'bank_name', 'branch', 'fd_type', 'society_bank_id',
        'investment_date', 'principal_amount', 'interest_rate', 'period_tenure', 'maturity_date', 'maturity_amount',
        'interest_earned', 'tds_deducted', 'net_interest_received', 'interest_receipt_date',
        'renewal_date', 'renewed_fd_no', 'closure_date', 'amount_received', 'fd_status', 'remarks',
    ];

    private function societyId()
    {
        return Auth::id();
    }

    /** $societyDetails['Society'][...] as the Cake views read it. */
    private function societyDetails(): array
    {
        $society = Society::where('user_id', $this->societyId())->first();
        $row = $society ? $society->toArray() : [];
        foreach (['society_name', 'registration_no', 'address'] as $k) {
            if (isset($row[$k])) {
                $row[$k] = ReportUtil::plain($row[$k]);
            }
        }

        return $row;
    }

    private function yearRange(): array
    {
        $from = session('fy.year_start_date');
        $to = session('fy.year_end_date');

        return [
            $from ? date('d/m/Y', strtotime($from)) : '',
            $to ? date('d/m/Y', strtotime($to)) : '',
        ];
    }

    private function base(): array
    {
        [$yearFrom, $yearTo] = $this->yearRange();

        return ['society' => $this->societyDetails(), 'yearFrom' => $yearFrom, 'yearTo' => $yearTo];
    }

    /** CakePHP: registers/fd_register */
    public function fdRegister()
    {
        $entries = DB::table('fd_registers as f')
            ->leftJoin('society_banks as b', 'b.id', '=', 'f.society_bank_id')
            ->where('f.society_id', $this->societyId())
            ->where('f.status', 1)
            ->orderBy('f.investment_date')
            ->select('f.*', 'b.account_no as bank_account_no')
            ->get();

        return view('society.registers.fd-register', $this->base() + ['entries' => $entries]);
    }

    /** CakePHP: registers/add_fd_register */
    public function addFdRegister(Request $request, $id = null)
    {
        $societyId = $this->societyId();

        if ($request->isMethod('post')) {
            $data = [];
            foreach (self::FD_FIELDS as $field) {
                $value = $request->input($field);
                // CakePHP wrote '' as NULL only for non-text columns (dates / numbers / ids); varchar keeps ''
                $isText = in_array($field, ['fd_no', 'bank_name', 'branch', 'fd_type', 'period_tenure', 'renewed_fd_no', 'fd_status', 'remarks'], true);
                $data[$field] = ($value === null || ($value === '' && !$isText)) ? null : $value;
            }
            $data['society_id'] = $societyId;
            $data['financial_year_id'] = session('fy.year_id');
            $data['udate'] = now();

            $editId = $request->input('id');
            if ($editId) {
                $updated = DB::table('fd_registers')->where('id', $editId)->where('society_id', $societyId)->update($data);
                $ok = $updated !== false;
            } else {
                $data['cdate'] = now();
                $ok = DB::table('fd_registers')->insert($data);
            }

            if ($ok) {
                return redirect()->route('society.fdRegister')->with('success', 'FD Register entry saved successfully');
            }

            return back()->withInput()->with('error', 'FD Register entry could not be saved');
        }

        $entry = $id
            ? DB::table('fd_registers')->where('id', $id)->where('society_id', $societyId)->first()
            : null;

        $banks = DB::table('society_banks')->where('society_id', $societyId)->pluck('account_no', 'id');

        return view('society.registers.add-fd-register', ['entry' => $entry, 'banks' => $banks]);
    }

    /** CakePHP: registers/shares_register */
    public function sharesRegister()
    {
        $entries = DB::table('member_identifications')
            ->where('society_id', $this->societyId())
            ->where('no_of_shares', '>', 0)
            ->orderBy('from_share_no')
            ->get();

        $memberNames = [];
        $memberIds = $entries->pluck('member_id')->filter()->unique()->all();
        if ($memberIds) {
            foreach (DB::table('members')->whereIn('id', $memberIds)->get(['id', 'member_prefix', 'member_name']) as $m) {
                $memberNames[$m->id] = trim($m->member_prefix . ' ' . $m->member_name);
            }
        }

        return view('society.registers.shares-register', $this->base() + ['entries' => $entries, 'memberNames' => $memberNames]);
    }

    /** CakePHP: registers/lien_register (empty action, "Coming Soon") */
    public function lienRegister()
    {
        return view('society.registers.coming-soon', ['title' => 'Lien Register']);
    }

    /** CakePHP: registers/nominee_register (empty action, "Coming Soon") */
    public function nomineeRegister()
    {
        return view('society.registers.coming-soon', ['title' => 'Nominee Register']);
    }

    /** CakePHP: registers/form_i */
    public function formI(Request $request)
    {
        $societyId = $this->societyId();

        $memberFlatLists = [];
        $members = DB::table('members')
            ->where('society_id', $societyId)->where('status', 1)
            ->orderBy('flat_no')
            ->get(['id', 'member_prefix', 'member_name', 'flat_no']);
        foreach ($members as $m) {
            $memberFlatLists[$m->id] = $m->flat_no . ' - ' . trim($m->member_prefix . ' ' . $m->member_name);
        }

        $selectedMemberId = $request->isMethod('post') ? ($request->input('member_id') ?: null) : null;

        $selectedMember = null;
        $history = collect();
        $wingName = '';

        if ($selectedMemberId) {
            $selectedMember = DB::table('members')->where('id', $selectedMemberId)->where('society_id', $societyId)->first();

            if ($selectedMember && !empty($selectedMember->wing_id)) {
                $wingName = DB::table('wings')->where('id', $selectedMember->wing_id)->value('wing_name') ?? '';
            }

            $history = DB::table('member_identifications')
                ->where('member_id', $selectedMemberId)->where('society_id', $societyId)
                ->orderBy('id')
                ->get();
        }

        return view('society.registers.form-i', $this->base() + [
            'memberFlatLists' => $memberFlatLists,
            'selectedMemberId' => $selectedMemberId,
            'selectedMember' => $selectedMember,
            'history' => $history,
            'wingName' => $wingName,
        ]);
    }

    /** CakePHP: registers/form_j */
    public function formJ()
    {
        $members = DB::table('members')
            ->where('society_id', $this->societyId())->where('status', 1)
            ->orderBy('flat_no')
            ->get(['id', 'member_prefix', 'member_name', 'unit_type', 'flat_no']);

        $latest = [];
        $memberIds = $members->pluck('id')->all();
        if ($memberIds) {
            $rows = DB::table('member_identifications')
                ->whereIn('member_id', $memberIds)
                ->orderByDesc('id')
                ->get(['member_id', 'age', 'gender', 'class']);
            foreach ($rows as $r) {
                if (!isset($latest[$r->member_id])) {
                    $latest[$r->member_id] = $r;
                }
            }
        }

        return view('society.registers.form-j', $this->base() + ['members' => $members, 'latest' => $latest]);
    }
}
