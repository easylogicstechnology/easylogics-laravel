<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyYearMapping extends Model
{
    protected $table = 'society_year_mapping';

    protected $fillable = ['society_id', 'year_id', 'is_active'];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYearMaster::class, 'year_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
