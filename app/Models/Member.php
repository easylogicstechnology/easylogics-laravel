<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'members';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'member_prefix',
        'member_name',
        'member_photo',
        'member_email',
        'member_phone',
        'member_parking_no',
        'society_id',
        'building_id',
        'wing_id',
        'floor_no',
        'unit_type',
        'flat_no',
        'area',
        'carpet',
        'commercial',
        'residential',
        'terrace',
        'gstin_no',
        'op_bill_due_date',
        'op_bill_date',
        'op_principal',
        'op_interest',
        'op_tax',
        'penality',
        'supplementary_principal',
        'supplementary_penality',
        'supplementary_interest',
        'supplementary_tax',
        'user_id',
        'report_access',
        'member_transfer',
        'joint_member_name',
        'status',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function wing()
    {
        return $this->belongsTo(Wing::class, 'wing_id');
    }

    public function identifications()
    {
        return $this->hasMany(MemberIdentification::class, 'member_id');
    }

    public function tariffs()
    {
        return $this->hasMany(MemberTariff::class, 'member_id');
    }

    public function tariffDetails()
    {
        return $this->hasMany(MemberTariffDetail::class, 'member_id');
    }

    public function payments()
    {
        return $this->hasMany(MemberPayment::class, 'member_id');
    }

    public function billSummaries()
    {
        return $this->hasMany(MemberBillSummary::class, 'member_id');
    }

    public function billGenerates()
    {
        return $this->hasMany(MemberBillGenerate::class, 'member_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
