<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCategory extends Model
{
    protected $table = 'employee_categories';
    public $timestamps = false;

    protected $fillable = [
        'society_id', 'emp_category_name', 'status', 'cdate', 'udate',
    ];

    public function subCategories()
    {
        return $this->hasMany(EmployeeSubCategory::class, 'emp_category_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'emp_category_id');
    }
}
