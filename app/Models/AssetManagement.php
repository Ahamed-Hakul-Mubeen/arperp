<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetManagement extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_type_id',
        'product_description',
        'product_configuration',
        'created_by',
    ];

    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    public function employee()
    {
        return $this->hasMany(Employee::class, 'employee_id');
    }

    public function assetAssignments()
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

}
