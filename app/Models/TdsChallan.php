<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsChallan (tds_challans). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsChallan extends Model
{
    protected $table = 'tds_challans';

    public $timestamps = false;

    protected $guarded = [];

    public function challanTransactions()
    {
        return $this->hasMany(TdsChallanTransaction::class, 'tds_challan_id');
    }
}
