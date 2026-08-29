<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'banks';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['bank_name', 'IFSC_code', 'branch', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
