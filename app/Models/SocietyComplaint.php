<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyComplaint extends Model
{
    protected $table = 'society_complaints';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'reseller_id',
        'complaint_type',
        'description',
        'status',
        'resolution',
        'complaint_date',
        'resolved_date',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function reseller()
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
}
