<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Auth\Events\Login;
use App\Models\EmployeeHistory;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Request;

class StoreLoginEvent
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event)
    {
        if($event->user->type == 'Employee')
        {
            // EmployeeHistory::create([
            //     'employee_id'    => $event->user->id,
            //     'type' => "Login",
            //     'description' => "Login",
            //     'ip_address' => Request::ip(),
            // ]);
        }
    }
}
