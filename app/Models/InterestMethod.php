<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestMethod extends Model
{
    protected $table = 'interest_methods';

    public $timestamps = false;

    protected $fillable = ['method_title', 'status'];
}
