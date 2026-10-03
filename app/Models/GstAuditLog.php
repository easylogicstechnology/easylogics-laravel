<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstAuditLog (gst_audit_logs). cdate/udate are set explicitly by the callers, as in Cake. */
class GstAuditLog extends Model
{
    protected $table = 'gst_audit_logs';

    public $timestamps = false;

    protected $guarded = [];
}
