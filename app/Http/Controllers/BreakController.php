<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use App\Models\TakeBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class BreakController extends Controller
{
    //
    public function storeBreak(Request $request)
{
   

   
    $employeeId = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
    $date = date("Y-m-d");
    $breakStartTime = date("H:i:s");
    $attendance = AttendanceEmployee::where('employee_id', '=', $employeeId)
                                    ->where('date', '=', $date)
                                    ->orderBy("id", "DESC")
                                    ->first();
    $break = new TakeBreak();
    $break->employee_id = $employeeId;
    $break->attendance_id = $attendance->id;
    $break->break_start_time = now()->format('H:i:s'); 
    $break->break_type = $request->input('break_type');
    $break->save();
}

public function endBreak(Request $request)

{   
    $employeeId = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
    $date = date("Y-m-d");
    $breakEndTime = date("H:i:s");
    $attendance = AttendanceEmployee::where('employee_id', '=', $employeeId)
                                    ->where('date', '=', $date)
                                    ->orderBy("id", "DESC")
                                    ->first();
    $break = TakeBreak::where('employee_id', $employeeId)
                  ->where('attendance_id', $attendance->id)
                  ->latest('created_at')
                  ->first();

    if ($break) {
        $break->break_end_time = now()->format('H:i:s');
        $break->save();

         $startTime = new \DateTime($break->break_start_time);
         $endTime = new \DateTime($break->break_end_time);
         $interval = $startTime->diff($endTime);
         $breakDurationInSeconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
 
         // Aggregate all break durations for the attendance record
         $totalBreakDurationInSeconds = TakeBreak::where('attendance_id', $attendance->id)
        ->get()
        ->reduce(function ($carry, $item) {
            $start = new \DateTime($item->break_start_time);
            $end = new \DateTime($item->break_end_time);
            $diff = $start->diff($end);
            return $carry + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
        }, 0);
 
         // Convert total seconds to H:i:s format
         $hours = floor($totalBreakDurationInSeconds / 3600);
         $minutes = floor(($totalBreakDurationInSeconds % 3600) / 60);
         $seconds = $totalBreakDurationInSeconds % 60;
         $totalBreakDurationFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
 
         $attendance->total_break_duration = $totalBreakDurationFormatted;
         $attendance->save();
    }
}
}
