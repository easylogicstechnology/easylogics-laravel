<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TariffType extends Model
{
    protected $table = 'tariff_types';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['tariff_type', 'status'];
}
