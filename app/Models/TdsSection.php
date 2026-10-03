<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsSection (tds_sections). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsSection extends Model
{
    protected $table = 'tds_sections';

    public $timestamps = false;

    protected $guarded = [];

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'tds_payable_ledger_head_id');
    }

    public function transactions()
    {
        return $this->hasMany(TdsTransaction::class, 'tds_section_id');
    }
}
