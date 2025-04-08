<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    use HasFactory;

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'work_shift_employees', 'work_shift_id', 'employee_id');
    }
}
