<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsChallanTransaction (tds_challan_transactions). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsChallanTransaction extends Model
{
    protected $table = 'tds_challan_transactions';

    public $timestamps = false;

    protected $guarded = [];
}
