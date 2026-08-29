<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
    protected $table = 'user_logins';

    public $timestamps = false;

    protected $fillable = ['user_id', 'ipaddress', 'time'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
