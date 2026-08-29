<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'user_permissions';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'user_id',
        'module',
        'can_add',
        'can_edit',
        'can_delete',
        'can_generate',
        'can_view',
    ];

    public const MODULES = [
        'dashboard',
        'software',
        'members',
        'bills',
        'payments',
        'settlement',
        'reports',
        'settings',
        'bill_generated',
        'member_payment',
        'general_receipt',
    ];

    public const PERMISSION_TYPES = [
        'can_add',
        'can_edit',
        'can_delete',
        'can_generate',
        'can_view',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
