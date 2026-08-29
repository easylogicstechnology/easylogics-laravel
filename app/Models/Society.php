<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Society extends Model
{
    protected $table = 'societies';

    public $incrementing = false;

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'user_id',
        'society_name',
        'society_code',
        'registration_no',
        'registration_date',
        'address',
        'telephone_no',
        'fax_no',
        'email_id',
        'url',
        'tan_no',
        'pan_no',
        'circle',
        'service_tax_no',
        'gstin_no',
        'cgst_no',
        'igst_no',
        'is_conveyance',
        'financial_year',
        'conveynace_date',
        'authorised_person',
        'enable_sms',
        'whatsapp_enabled',
        'mobile_app_enabled',
        'op_balance_saved',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'society_id');
    }

    public function wings()
    {
        return $this->hasMany(Wing::class, 'society_id');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'society_id');
    }

    public function societyParameter()
    {
        return $this->hasMany(SocietyParameter::class, 'society_id');
    }

    public function societyBanks()
    {
        return $this->hasMany(SocietyBank::class, 'society_id');
    }

    public function ledgerHeads()
    {
        return $this->hasMany(SocietyLedgerHead::class, 'society_id');
    }

    public function headSubCategories()
    {
        return $this->hasMany(SocietyHeadSubCategory::class, 'society_id');
    }

    public function journalVouchers()
    {
        return $this->hasMany(JournalVoucher::class, 'society_id');
    }

    public function cashWithdraws()
    {
        return $this->hasMany(CashWithdraw::class, 'society_id');
    }

    public function yearMappings()
    {
        return $this->hasMany(SocietyYearMapping::class, 'society_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
