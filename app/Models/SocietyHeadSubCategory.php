<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyHeadSubCategory extends Model
{
    protected $table = 'society_head_sub_categories';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'account_category_id',
        'account_head_id',
        'title',
        'account_status',
        'status',
    ];

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id');
    }

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }
}
