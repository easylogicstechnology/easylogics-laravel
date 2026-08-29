<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSubCategory extends Model
{
    protected $table = 'employee_sub_categories';
    public $timestamps = false;

    protected $fillable = [
        'society_id', 'emp_category_id', 'emp_sub_category_name', 'status', 'cdate', 'udate',
    ];

    public function category()
    {
        return $this->belongsTo(EmployeeCategory::class, 'emp_category_id');
    }
}
