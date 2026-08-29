<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $table = 'tenants';
    public $timestamps = false;

    protected $fillable = [
        'society_id', 'building_id', 'wing_id', 'flat_no',
        'tenant_name', 'lease_type', 'agreement_on', 'rent_per_month',
        'nationality', 'address', 'city', 'state_id', 'country_id',
        'phone', 'email', 'status', 'cdate', 'udate',
    ];
}
