<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstOutwardSupplyMeta (gst_outward_supply_meta). cdate/udate are set explicitly by the callers, as in Cake. */
class GstOutwardSupplyMeta extends Model
{
    protected $table = 'gst_outward_supply_meta';

    public $timestamps = false;

    protected $guarded = [];

    public function hsnSac()
    {
        return $this->belongsTo(GstHsnSacMaster::class, 'hsn_sac_id');
    }
}
