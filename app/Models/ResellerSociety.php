<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSociety extends Model
{
    protected $table = 'reseller_societies';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'societie_id',
        'user_id',
        'reseller_id',
        'added_by',
        'status',
    ];

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function society()
    {
        return $this->belongsTo(Society::class, 'societie_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
