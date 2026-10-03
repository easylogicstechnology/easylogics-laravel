<?php

namespace App\Http\Controllers\Reseller;

use App\Hashing\LegacyCakeHasher;
use App\Http\Controllers\Controller;
use App\Models\ResellerSociety;
use App\Models\ResellerSubUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Manage Users" - a reseller's team logins (CakePHP ResellersController: manage_users, add_user, edit_user,
 * delete_user, permissions). A team login is a users row (role SubReseller) plus a reseller_sub_users row
 * holding its permission grid and allowed societies. Routes are Reseller-only (see routes/web.php).
 */
class UserController extends Controller
{
    /** manage_users() */
    public function index()
    {
        $resellerId = Auth::id();

        $subUsers = ResellerSubUser::where('reseller_id', $resellerId)->orderByDesc('created')->get();

        $userMap = $this->userMap($subUsers, ['id', 'username', 'full_name', 'email', 'mobile', 'status']);

        return view('reseller.users.index', compact('subUsers', 'userMap'));
    }

    /** add_user() - GET */
    public function create()
    {
        return view('reseller.users.create', [
            'modules' => ResellerSubUser::MODULES,
            'permActions' => ResellerSubUser::PERM_ACTIONS,
        ]);
    }

    /** add_user() - POST */
    public function store(Request $request)
    {
        $resellerId = Auth::id();

        $fullName = trim((string) $request->input('full_name'));
        $email = trim((string) $request->input('email'));
        $mobile = trim((string) $request->input('mobile'));
        $password = (string) $request->input('password');
        $confirmPassword = (string) $request->input('password_confirmation');
        $status = $request->has('status') ? $request->input('status') : 1;

        if (empty($fullName) || empty($mobile) || empty($password)) {
            return redirect()->route('reseller.users.create')
                ->with('error', 'Full Name, Mobile Number and Password are required.');
        }

        if ($password !== $confirmPassword) {
            return redirect()->route('reseller.users.create')
                ->with('error', 'Password and Confirm Password do not match.');
        }

        $username = $mobile;
        if (User::where('username', $username)->exists()) {
            return redirect()->route('reseller.users.create')
                ->with('error', 'A user with this mobile number already exists.');
        }

        try {
            DB::transaction(function () use ($request, $resellerId, $username, $fullName, $email, $mobile, $password, $status) {
                $now = date('Y-m-d H:i:s');

                $user = User::create([
                    'username' => $username,
                    'full_name' => $fullName,
                    'email' => $email,
                    'mobile' => $mobile,
                    'password' => (new LegacyCakeHasher())->make($password),
                    'role' => 'SubReseller',
                    'access_level' => 3,
                    'added_by' => $resellerId,
                    'cdate' => $now,
                    'udate' => $now,
                    'status' => $status,
                ]);

                ResellerSubUser::create([
                    'reseller_id' => $resellerId,
                    'user_id' => $user->id,
                    'status' => 1,
                    'created' => $now,
                    'modified' => $now,
                ] + $this->postedPermissions($request));
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Failed to create user. Please try again.');
        }

        return redirect()->route('reseller.users.index')->with('success', 'User created successfully.');
    }

    /** edit_user($id) - GET; $id is reseller_sub_users.id */
    public function edit($id)
    {
        $subUser = $this->ownSubUser($id);
        if (!$subUser) {
            return redirect()->route('reseller.users.index')->with('error', 'User not found.');
        }

        $userData = User::where('id', $subUser->user_id)
            ->first(['id', 'username', 'full_name', 'email', 'mobile', 'status']);

        return view('reseller.users.edit', [
            'subUser' => $subUser,
            'userData' => $userData,
            'modules' => ResellerSubUser::EDIT_SCREEN_MODULES,
            'permActions' => ResellerSubUser::PERM_ACTIONS,
        ]);
    }

    /** edit_user($id) - POST/PUT */
    public function update(Request $request, $id)
    {
        $subUser = $this->ownSubUser($id);
        if (!$subUser) {
            return redirect()->route('reseller.users.index')->with('error', 'User not found.');
        }

        $updateData = [];
        if (!empty($request->input('password'))) {
            $updateData['password'] = (new LegacyCakeHasher())->make((string) $request->input('password'));
        }
        if ($request->has('full_name')) {
            $updateData['full_name'] = trim((string) $request->input('full_name'));
        }
        if ($request->has('email')) {
            $updateData['email'] = trim((string) $request->input('email'));
        }
        if ($request->has('mobile')) {
            $updateData['mobile'] = trim((string) $request->input('mobile'));
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->input('status');
        }
        $updateData['udate'] = date('Y-m-d H:i:s');

        DB::transaction(function () use ($request, $subUser, $updateData) {
            DB::table('users')->where('id', $subUser->user_id)->update($updateData);

            // Cake resets every module of ResellerSubUser::MODULES here, though this screen only posts the first nine
            ResellerSubUser::where('id', $subUser->id)->update(
                ['modified' => date('Y-m-d H:i:s')] + $this->postedPermissions($request)
            );
        });

        return redirect()->route('reseller.users.index')->with('success', 'User updated successfully.');
    }

    /** delete_user($id) - deactivates, never deletes */
    public function deactivate($id)
    {
        $subUser = $this->ownSubUser($id);
        if (!$subUser) {
            return redirect()->route('reseller.users.index')->with('error', 'User not found.');
        }

        DB::transaction(function () use ($subUser) {
            DB::table('users')->where('id', $subUser->user_id)->update(['status' => 0]);
            ResellerSubUser::where('id', $subUser->id)->update(['status' => 0, 'modified' => date('Y-m-d H:i:s')]);
        });

        return redirect()->route('reseller.users.index')->with('success', 'User deactivated successfully.');
    }

    /** permissions() - GET */
    public function permissions(Request $request)
    {
        $resellerId = Auth::id();

        $subUsers = ResellerSubUser::where('reseller_id', $resellerId)
            ->where('status', 1)
            ->orderByDesc('created')
            ->get();

        $userMap = $this->userMap($subUsers, ['id', 'username', 'full_name', 'email']);

        $resellerSocietiesList = $this->resellerSocieties($resellerId);

        $selectedUserId = $request->query('user_id') !== null ? (int) $request->query('user_id') : null;
        $selectedSubUser = null;
        $selectedSocietyIds = [];
        if ($selectedUserId) {
            $found = ResellerSubUser::where('user_id', $selectedUserId)->where('reseller_id', $resellerId)->first();
            if ($found) {
                $selectedSubUser = $found;
                // Deny by default: nothing checked until the reseller explicitly grants a society.
                if (!empty($found->allowed_society_ids)) {
                    $selectedSocietyIds = array_map('intval', explode(',', $found->allowed_society_ids));
                }
            } else {
                $selectedUserId = null;
            }
        }

        return view('reseller.users.permissions', [
            'subUsers' => $subUsers,
            'userMap' => $userMap,
            'selectedUserId' => $selectedUserId,
            'selectedSubUser' => $selectedSubUser,
            'resellerSocietiesList' => $resellerSocietiesList,
            'selectedSocietyIds' => $selectedSocietyIds,
            'modules' => ResellerSubUser::MODULES,
            'moduleLabels' => ResellerSubUser::MODULE_LABELS,
            'permActions' => ResellerSubUser::PERM_ACTIONS,
        ]);
    }

    /** permissions() - POST */
    public function savePermissions(Request $request)
    {
        $resellerId = Auth::id();

        $postedUserId = (int) $request->input('user_id');
        $subUser = ResellerSubUser::where('user_id', $postedUserId)->where('reseller_id', $resellerId)->first();

        $flash = [];
        if ($subUser) {
            $resellerSocietyIds = ResellerSociety::where('reseller_id', $resellerId)->pluck('societie_id')->all();

            // Only societies the reseller themself has access to can be granted - an id outside
            // $resellerSocietyIds (tampered form) is silently dropped.
            $postedSocietyIds = array_map('intval', (array) $request->input('society_ids', []));
            $postedSocietyIds = array_intersect($postedSocietyIds, $resellerSocietyIds);

            ResellerSubUser::where('id', $subUser->id)->update(
                ['modified' => date('Y-m-d H:i:s')]
                + $this->postedPermissions($request)
                + ['allowed_society_ids' => implode(',', $postedSocietyIds)]
            );

            $flash = ['success' => 'Permissions updated successfully.'];
        }

        return redirect()->route('reseller.users.permissions', ['user_id' => $postedUserId])->with($flash);
    }

    /** The reseller's own team login row, looked up by reseller_sub_users.id (never another reseller's). */
    private function ownSubUser($id): ?ResellerSubUser
    {
        return ResellerSubUser::where('id', $id)->where('reseller_id', Auth::id())->first();
    }

    /** @return \Illuminate\Support\Collection users of the given reseller_sub_users rows, keyed by id */
    private function userMap($subUsers, array $fields)
    {
        if ($subUsers->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $subUsers->pluck('user_id')->all())->get($fields)->keyBy('id');
    }

    /** @return array<int, object> the reseller's own societies (id, society_name, society_code) by name */
    private function resellerSocieties($resellerId)
    {
        $ids = ResellerSociety::where('reseller_id', $resellerId)->pluck('societie_id')->all();
        if (empty($ids)) {
            return [];
        }

        return DB::table('societies')
            ->whereIn('id', array_map('intval', $ids))
            ->orderBy('society_name')
            ->get(['id', 'society_name', 'society_code'])
            ->all();
    }

    /** @return array<string, int> every {module}_{action} column: 1 if its Permission[...] box was posted, else 0 */
    private function postedPermissions(Request $request): array
    {
        $posted = (array) $request->input('Permission', []);

        $perms = [];
        foreach (ResellerSubUser::permissionColumns() as $key) {
            $perms[$key] = isset($posted[$key]) ? 1 : 0;
        }

        return $perms;
    }
}
