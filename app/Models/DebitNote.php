<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $fillable = [
        'bill',
        'vendor',
        'amount',
        'date',
        'created_user'
    ];

    public function vendor()
    {
        return $this->hasOne('App\Models\Vender', 'vender_id', 'vendor');
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }

}
