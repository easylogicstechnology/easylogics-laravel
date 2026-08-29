<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    protected $table = 'account_heads';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['account_category_id', 'title', 'transaction_type', 'status'];

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function subCategories()
    {
        return $this->hasMany(SocietyHeadSubCategory::class, 'account_head_id');
    }
}
