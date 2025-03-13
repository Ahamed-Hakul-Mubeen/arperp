<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'invoice',
        'customer',
        'amount',
        'date',
        'created_user'
    ];

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'customer_id', 'customer');
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }

}
