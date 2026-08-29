<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYearMaster extends Model
{
    protected $table = 'financial_year_master';

    protected $fillable = [
        'year',
        'year_start_date',
        'year_end_date',
        'current_financial_year',
        'is_active',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeCurrent($query)
    {
        return $query->where('current_financial_year', 1);
    }
}
