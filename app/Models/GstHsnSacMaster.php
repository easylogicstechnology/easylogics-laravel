<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstHsnSacMaster (gst_hsn_sac_master). cdate/udate are set explicitly by the callers, as in Cake. */
class GstHsnSacMaster extends Model
{
    protected $table = 'gst_hsn_sac_master';

    public $timestamps = false;

    protected $guarded = [];

    public function outwardSupplyMetas()
    {
        return $this->hasMany(GstOutwardSupplyMeta::class, 'hsn_sac_id');
    }
}
