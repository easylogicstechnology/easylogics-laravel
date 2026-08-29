<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingFrequency extends Model
{
    protected $table = 'billing_frequencies';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['frequency_type', 'status'];
}
