<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wing extends Model
{
    protected $table = 'wings';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'building_id',
        'wing_name',
        'status',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'wing_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
