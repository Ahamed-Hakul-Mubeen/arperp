<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeHistory extends Model
{
    use HasFactory;
    
    protected $fillable = ['employee_id','type','description','ip_address'];

    public static function storeHistory($user_id, $type, $description, $ip_address = null)
    {
        
        self::create([
            'employee_id' => $user_id,
            'type' => $type,
            'description' => $description,
            'ip_address' => $ip_address,
        ]);
    }
    /**
     * Get the user that owns the EmployeeHistory
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee()
    {
        
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }
}
