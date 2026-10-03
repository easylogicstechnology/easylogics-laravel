<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    public $incrementing = true;

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'username',
        'password',
        'role',
        'access_level',
        'added_by',
        'status',
        'name',
        'full_name',
        'email',
        'mobile',
    ];

    protected $hidden = [
        'password',
    ];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function societies()
    {
        return $this->hasMany(Society::class, 'user_id');
    }

    public function userLogins()
    {
        return $this->hasMany(UserLogin::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    public function isSociety(): bool
    {
        return $this->role === 'Society';
    }

    public function isReseller(): bool
    {
        return $this->role === 'Reseller';
    }

    public function isMember(): bool
    {
        return $this->role === 'Member';
    }

    public function isSubReseller(): bool
    {
        return $this->role === 'SubReseller';
    }

    /**
     * A reseller's team login is limited to the {module}_{action} flags of its reseller_sub_users row,
     * which is put in the session at login (Cake's Auth.sub_reseller - a snapshot, so a changed grid
     * applies from the next login). Every other role is never restricted here.
     */
    public function hasPermission(string $module, string $action = 'view'): bool
    {
        if ($this->role !== 'SubReseller') {
            return true;
        }

        $subReseller = session('sub_reseller');

        return !empty($subReseller) && !empty($subReseller[$module . '_' . $action]);
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'added_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function resellerSocieties()
    {
        return $this->hasMany(ResellerSociety::class, 'reseller_id');
    }

    public function validateCredentials($password): bool
    {
        $storedHash = $this->getAuthPassword();
        if (strlen($storedHash) === 40) {
            return sha1($password) === $storedHash;
        }
        return \Illuminate\Support\Facades\Hash::check($password, $storedHash);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
