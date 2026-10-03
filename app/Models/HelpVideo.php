<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpVideo extends Model
{
    protected $table = 'help_videos';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'source_app',
        'title',
        'video_path',
        'display_status',
    ];
}
