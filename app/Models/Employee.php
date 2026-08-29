<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    public $timestamps = false;

    protected $fillable = [
        'society_id', 'emp_name', 'emp_code', 'emp_category_id', 'emp_sub_category_id',
        'joining_date', 'gender', 'date_of_birth', 'marrital_status',
        'date_of_leaving', 'religion', 'qualification', 'pan_no', 'gstin_no',
        'emp_photo', 'status', 'cdate', 'udate',
    ];

    public function category()
    {
        return $this->belongsTo(EmployeeCategory::class, 'emp_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(EmployeeSubCategory::class, 'emp_sub_category_id');
    }
}
