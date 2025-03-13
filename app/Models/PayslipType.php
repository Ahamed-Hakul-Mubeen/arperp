<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipType extends Model
{
    protected $fillable = [
        'name',
        'digital_signature',
        'created_by',
    ];
}
