<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'chart_account_id',
        'vender_id',
        'description',
        'category_id',
        'payment_method',
        'reference',
        'created_user',
        'created_by',
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public function chartAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccount', 'id', 'chart_account_id');
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }
}
