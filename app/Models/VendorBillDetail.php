<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model VendorBillDetail (vendor_bill_details): one billing line of a vendor bill. */
class VendorBillDetail extends Model
{
    protected $table = 'vendor_bill_details';

    public $timestamps = false;

    protected $guarded = [];
}
