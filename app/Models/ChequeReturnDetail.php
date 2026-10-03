<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeReturnDetail extends Model
{
    protected $table = 'cheque_return_details';
    protected $primaryKey = 'cr_id';

    protected $fillable = [
        'member_id',
        'payment_id',
        'society_id',
        'cheque_no',
        'cheque_amount',
        'cheque_return_date',
        'cheque_return_reason',
        'member_transfer',
        'financial_year_id',
    ];

    public function payment()
    {
        return $this->belongsTo(MemberPayment::class, 'payment_id');
    }
}
