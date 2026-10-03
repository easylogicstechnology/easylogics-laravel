<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Who a Reseller-panel request acts for.
 *
 * CakePHP rewrites Auth.User.id to the parent reseller's id when a reseller's team login (role SubReseller)
 * signs in, and keeps that login's reseller_sub_users row in Auth.sub_reseller. Laravel keeps the real login as
 * Auth::user(), so the panel asks here instead of using Auth::id(). For a Reseller this is just Auth::id().
 */
class ResellerContext
{
    public static function id(): ?int
    {
        $user = Auth::user();

        if ($user && $user->role === 'SubReseller') {
            $row = session('sub_reseller');

            if (!empty($row['reseller_id'])) {
                return (int) $row['reseller_id'];
            }
        }

        return Auth::id();
    }

    /**
     * Permission check for a reseller team login (Cake: AppController::_subResellerHasPermission). It looks at the
     * login's reseller_sub_users row kept in the session, so it also holds while the team login is inside a society
     * (Auth is then the Society user and only the session still says who really is in). Anyone else - the reseller
     * itself, a real Society login - is never restricted. $action may be a list: any one granted action is enough.
     *
     * @param string|string[] $action add|edit|delete|generate|update|view
     */
    public static function can(string $module, string|array $action = 'view'): bool
    {
        $row = session('sub_reseller');

        if (empty($row)) {
            return true;
        }

        foreach ((array) $action as $one) {
            if (!empty($row[$module . '_' . $one])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Society ids the reseller ticked for this team login on the Permissions page - deny by default, as in Cake.
     * null = not a team login, so every society the reseller owns is allowed.
     *
     * @return int[]|null
     */
    public static function allowedSocietyIds(): ?array
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'SubReseller') {
            return null;
        }

        $row = session('sub_reseller');

        return empty($row['allowed_society_ids'])
            ? []
            : array_map('intval', explode(',', $row['allowed_society_ids']));
    }
}
