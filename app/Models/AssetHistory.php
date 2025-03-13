<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetHistory extends Model
{
    use HasFactory;
    public function employee()
{
    return $this->belongsTo(Employee::class, 'employee_id');
}
public function fromEmployee()
{
    return $this->belongsTo(Employee::class, 'from_employee_id');
}

public function toEmployee()
{
    return $this->belongsTo(Employee::class, 'to_employee_id');
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}
}
