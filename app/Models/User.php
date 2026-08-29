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
        'member_credit',
        'name',
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

    public function isResellerUser(): bool
    {
        return $this->role === 'ResellerUser';
    }

    public function hasPermission(string $module, string $action = 'can_view'): bool
    {
        if ($this->role !== 'ResellerUser') {
            return true;
        }

        if (!isset($this->cachedPermissions)) {
            $this->cachedPermissions = UserPermission::where('user_id', $this->id)
                ->get()
                ->keyBy('module');
        }

        $perm = $this->cachedPermissions[$module] ?? null;

        return $perm && $perm->$action == 1;
    }

    public function permissions()
    {
        return $this->hasMany(UserPermission::class, 'user_id');
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

    public function getTotalMembersUsed(): int
    {
        $societyIds = ResellerSociety::where('reseller_id', $this->id)
            ->pluck('societie_id')
            ->toArray();

        if (empty($societyIds)) {
            return 0;
        }

        return Member::whereIn('society_id', $societyIds)->count();
    }

    public function getRemainingCredit(): int
    {
        return max(0, $this->member_credit - $this->getTotalMembersUsed());
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
