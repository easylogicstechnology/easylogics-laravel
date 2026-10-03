<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsAuditLog (tds_audit_logs). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsAuditLog extends Model
{
    protected $table = 'tds_audit_logs';

    public $timestamps = false;

    protected $guarded = [];
}
