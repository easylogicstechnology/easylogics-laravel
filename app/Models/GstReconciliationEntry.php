<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstReconciliationEntry (gst_reconciliation_entries). cdate/udate are set explicitly by the callers, as in Cake. */
class GstReconciliationEntry extends Model
{
    protected $table = 'gst_reconciliation_entries';

    public $timestamps = false;

    protected $guarded = [];
}
