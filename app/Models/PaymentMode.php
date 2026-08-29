<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMode extends Model
{
    protected $table = 'payment_modes';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['payment_mode', 'status'];
}
