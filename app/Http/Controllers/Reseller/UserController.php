<?php

namespace App\Http\Controllers\Reseller;

use App\Hashing\LegacyCakeHasher;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->orderBy('cdate', 'desc')
            ->get();

        return view('reseller.users.index', compact('users'));
    }

    public function create()
    {
        return view('reseller.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'mobile' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $request->input('email'),
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'password' => (new LegacyCakeHasher())->make($request->input('password')),
                'role' => 'ResellerUser',
                'access_level' => 3,
                'added_by' => Auth::id(),
                'status' => $request->input('status', 1),
            ]);

            DB::commit();

            return redirect()->route('reseller.users.index')
                ->with('success', 'User created successfully. You can now assign permissions from the Permissions page.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create user. ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $user = User::where('id', $id)
            ->where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->firstOrFail();

        return view('reseller.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::where('id', $id)
            ->where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $id,
            'mobile' => 'required|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        try {
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->username = $request->input('email');
            $user->mobile = $request->input('mobile');
            $user->status = $request->input('status', 1);

            if ($request->filled('password')) {
                $user->password = (new LegacyCakeHasher())->make($request->input('password'));
            }

            $user->save();

            DB::commit();

            return redirect()->route('reseller.users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update user. ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $user = User::where('id', $id)
            ->where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->firstOrFail();

        DB::beginTransaction();
        try {
            UserPermission::where('user_id', $id)->delete();
            $user->delete();
            DB::commit();

            return redirect()->route('reseller.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete user.');
        }
    }

    public function permissions(Request $request)
    {
        $users = User::where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->orderBy('name')
            ->get();

        $modules = UserPermission::MODULES;
        $permissionTypes = UserPermission::PERMISSION_TYPES;

        $selectedUserId = $request->query('user_id');
        $userPermissions = collect();

        if ($selectedUserId) {
            $selectedUser = User::where('id', $selectedUserId)
                ->where('added_by', Auth::id())
                ->where('role', 'ResellerUser')
                ->first();

            if ($selectedUser) {
                $userPermissions = UserPermission::where('user_id', $selectedUserId)
                    ->get()
                    ->keyBy('module');
            } else {
                $selectedUserId = null;
            }
        }

        return view('reseller.users.permissions', compact(
            'users', 'modules', 'permissionTypes', 'selectedUserId', 'userPermissions'
        ));
    }

    public function savePermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $user = User::where('id', $request->input('user_id'))
            ->where('added_by', Auth::id())
            ->where('role', 'ResellerUser')
            ->firstOrFail();

        $this->syncPermissions($user->id, $request->input('permissions', []));

        return redirect()->route('reseller.users.permissions', ['user_id' => $user->id])
            ->with('success', 'Permissions updated successfully for ' . $user->name . '.');
    }

    private function syncPermissions(int $userId, array $permissions): void
    {
        UserPermission::where('user_id', $userId)->delete();

        foreach (UserPermission::MODULES as $module) {
            $modulePerms = $permissions[$module] ?? [];
            $hasAny = !empty(array_filter($modulePerms));
            if ($hasAny) {
                UserPermission::create([
                    'user_id' => $userId,
                    'module' => $module,
                    'can_add' => !empty($modulePerms['can_add']) ? 1 : 0,
                    'can_edit' => !empty($modulePerms['can_edit']) ? 1 : 0,
                    'can_delete' => !empty($modulePerms['can_delete']) ? 1 : 0,
                    'can_generate' => !empty($modulePerms['can_generate']) ? 1 : 0,
                    'can_view' => !empty($modulePerms['can_view']) ? 1 : 0,
                ]);
            }
        }
    }
}
