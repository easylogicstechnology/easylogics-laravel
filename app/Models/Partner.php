<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'site_partners';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'name',
        'location',
        'company',
        'description',
        'image_path',
        'sort_order',
        'display_status',
    ];
}
