<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $fillable = [
        'from_account',
        'to_account',
        'amount',
        'date',
        'payment_method',
        'reference',
        'description',
        'created_by',
        'created_user'
    ];

    public function fromBankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'from_account');
    }

    public function toBankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'to_account');
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }

}
