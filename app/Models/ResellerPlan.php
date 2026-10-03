<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Subscription plans a reseller can buy (CakePHP ResellerPlan / table reseller_plans). */
class ResellerPlan extends Model
{
    protected $table = 'reseller_plans';

    public $timestamps = false;

    protected $fillable = ['plan_key', 'name', 'days', 'amount', 'is_active', 'only_reseller_id', 'sort_order', 'cdate', 'udate'];
}
