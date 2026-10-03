<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/** The few `$this->Session->read('Auth....')` values the CakePHP report views ask for. */
class LegacySession
{
    public function read($key)
    {
        switch ($key) {
            case 'Auth.User.id':
                return Auth::id();
            case 'Auth.User.role':
                return Auth::user()->role ?? null;
            case 'Auth.year_id':
                return session('fy.year_id');
            case 'Auth.year_start_date':
                return session('fy.year_start_date');
            case 'Auth.year_end_date':
                return session('fy.year_end_date');
            case 'Auth.formated_year_start_date':
                $d = session('fy.year_start_date');

                return $d ? date('d/m/Y', strtotime($d)) : null;
            case 'Auth.start_year':
                $d = session('fy.year_start_date');

                return $d ? date('Y', strtotime($d)) : null;
            case 'Auth.end_year':
                $d = session('fy.year_end_date');

                return $d ? date('Y', strtotime($d)) : null;
            case 'Auth.dbConfig':
                return 'default';
        }

        return null;
    }
}
