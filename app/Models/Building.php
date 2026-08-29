<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = 'buildings';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'building_name',
        'num_flats',
        'status',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function wings()
    {
        return $this->hasMany(Wing::class, 'building_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'building_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
