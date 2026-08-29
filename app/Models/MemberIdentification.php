<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberIdentification extends Model
{
    protected $table = 'member_identifications';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'member_id',
        'identification_type',
        'identification_no',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
