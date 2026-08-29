<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestType extends Model
{
    protected $table = 'interest_types';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['interest_type', 'status'];
}
