<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Founder extends Model
{
    protected $table = 'site_founder';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'name',
        'designation',
        'description',
        'image_path',
        'display_status',
    ];
}
