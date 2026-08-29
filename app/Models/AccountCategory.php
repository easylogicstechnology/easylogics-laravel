<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountCategory extends Model
{
    protected $table = 'account_categories';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = ['title', 'status'];

    public function accountHeads()
    {
        return $this->hasMany(AccountHead::class, 'account_category_id');
    }
}
