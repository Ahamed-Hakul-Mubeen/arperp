<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\TakeBreak;
use App\Models\WorkShift;
use Illuminate\Support\Facades\DB; // Import DB facade

class WorkShiftController extends Controller
{
    //
    public function index()
    {
        // if (\Auth::user()->can('manage assets management')) {
            $workshifts = WorkShift::all();
            //$workshifts = WorkShift::where('created_by', \Auth::user()->creatorId());
            return view('workshift.index',compact('workshifts'));
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function create()
    {   
        // if(\Auth::user()->can('create assets management'))
        // {
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->whereHas('user', function ($query) {
                $query->where('type',['Employee', 'HR', 'Project Manager']);
            })
            ->pluck('name', 'id'); 
            $break = TakeBreak::where('employee_id','break_type')->get();
            return view('workshift.create',compact('employees','break'));
        // }else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'employee' => 'required|array', // Ensure the 'employee' field is an array
            'employee.*' => 'exists:employees,id', // Validate that each employee exists in the employees table
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'shift_type' => 'required|in:regular,scheduled',
            'is_sunday_off' => 'boolean',
            'is_monday_off' => 'boolean',
            'is_tuesday_off' => 'boolean',
            'is_wednesday_off' => 'boolean',
            'is_thursday_off' => 'boolean',
            'is_friday_off' => 'boolean',
            'is_saturday_off' => 'boolean',
            'sunday_start_time' => 'nullable|date_format:H:i',
            'sunday_end_time' => 'nullable|date_format:H:i|after:sunday_start_time',
            'monday_start_time' => 'nullable|date_format:H:i',
            'monday_end_time' => 'nullable|date_format:H:i|after:monday_start_time',
            'tuesday_start_time' => 'nullable|date_format:H:i',
            'tuesday_end_time' => 'nullable|date_format:H:i|after:tuesday_start_time',
            'wednesday_start_time' => 'nullable|date_format:H:i',
            'wednesday_end_time' => 'nullable|date_format:H:i|after:wednesday_start_time',
            'thursday_start_time' => 'nullable|date_format:H:i',
            'thursday_end_time' => 'nullable|date_format:H:i|after:thursday_start_time',
            'friday_start_time' => 'nullable|date_format:H:i',
            'friday_end_time' => 'nullable|date_format:H:i|after:friday_start_time',
            'saturday_start_time' => 'nullable|date_format:H:i',
            'saturday_end_time' => 'nullable|date_format:H:i|after:saturday_start_time',
            'break_time' => 'nullable|string',
            //'description' => 'nullable|string',
            'department' => 'required|string',
          
        ]);

        // Return validation errors
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        // Create a new WorkShift instance
        $workShift = new WorkShift();
        $workShift->name = $request->name;
        $workShift->start_date = $request->start_date;
        $workShift->end_date = $request->end_date;
        $workShift->start_time = $request->start_time; // Regular shift start time
        $workShift->end_time = $request->end_time; // Regular shift end time
        $workShift->shift_type = $request->shift_type;
        $workShift->break_time = $request->break_time;
        $workShift->description = $request->description;
        $workShift->department = $request->department;
        $workShift->created_by = \Auth::user()->creatorId();

        // Set weekend days (off days) flags
        $workShift->is_sunday_off = $request->has('is_sunday_off');
        $workShift->is_monday_off = $request->has('is_monday_off');
        $workShift->is_tuesday_off = $request->has('is_tuesday_off');
        $workShift->is_wednesday_off = $request->has('is_wednesday_off');
        $workShift->is_thursday_off = $request->has('is_thursday_off');
        $workShift->is_friday_off = $request->has('is_friday_off');
        $workShift->is_saturday_off = $request->has('is_saturday_off');


        // Set start and end times for scheduled days (if scheduled shift is selected)
        if ($request->shift_type == 'scheduled') {
            $workShift->sunday_start_time = $request->sunday_start_time;
            $workShift->sunday_end_time = $request->sunday_end_time;
            $workShift->monday_start_time = $request->monday_start_time;
            $workShift->monday_end_time = $request->monday_end_time;
            $workShift->tuesday_start_time = $request->tuesday_start_time;
            $workShift->tuesday_end_time = $request->tuesday_end_time;
            $workShift->wednesday_start_time = $request->wednesday_start_time;
            $workShift->wednesday_end_time = $request->wednesday_end_time;
            $workShift->thursday_start_time = $request->thursday_start_time;
            $workShift->thursday_end_time = $request->thursday_end_time;
            $workShift->friday_start_time = $request->friday_start_time;
            $workShift->friday_end_time = $request->friday_end_time;
            $workShift->saturday_start_time = $request->saturday_start_time;
            $workShift->saturday_end_time = $request->saturday_end_time;
        }

        // Save the new work shift to the database
        //dd($workShift);
        $workShift->save();

        foreach ($request->employee as $employeeId) {
            DB::table('work_shift_employees')->insert([
                'work_shift_id' => $workShift->id,
                'employee_id' => $employeeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    

        return redirect()->route('work_shift.index')->with('success', __('Work shift added successfully.'));
    }

    public function edit($id)
    {
        // Find the existing work shift by ID
        $workShift = WorkShift::find($id);
    
        // Fetch all employees (id => name)
        $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->whereHas('user', function ($query) {
                $query->where('type',['Employee', 'HR', 'Project Manager']);
            })
            ->pluck('name', 'employees.id'); // Make sure 'employees.id' is correct
    
        $selectedEmployees = $workShift->employees()->pluck('work_shift_employees.employee_id')->toArray();
    
        return view('workshift.edit', compact('workShift', 'employees', 'selectedEmployees'));
    }
    
    


    public function update(Request $request, $id)
{   
    // Validate the incoming request data
    $validator = \Validator::make($request->all(), [
        'name' => 'required',
        'employee' => 'required|array', // Ensure the 'employee' field is an array
        'employee.*' => 'exists:employees,id', // Validate that each employee exists in the employees table
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    // Return validation errors
    if ($validator->fails()) {
        $messages = $validator->getMessageBag();
        return redirect()->back()->with('error', $messages->first());
    }

    // Find the existing work shift by ID
    $workShift = WorkShift::find($id);
    
    // Update basic information
    $workShift->name = $request->name;
    $workShift->start_date = $request->start_date;
    $workShift->end_date = $request->end_date;
    $workShift->start_time = $request->start_time; // Regular shift start time
    $workShift->end_time = $request->end_time; // Regular shift end time
    $workShift->shift_type = $request->shift_type;
    $workShift->break_time = $request->break_time;
    $workShift->description = $request->description;
    $workShift->department = $request->department;

    // Set weekend days (off days) flags
    $workShift->is_sunday_off = $request->has('is_sunday_off');
    $workShift->is_monday_off = $request->has('is_monday_off');
    $workShift->is_tuesday_off = $request->has('is_tuesday_off');
    $workShift->is_wednesday_off = $request->has('is_wednesday_off');
    $workShift->is_thursday_off = $request->has('is_thursday_off');
    $workShift->is_friday_off = $request->has('is_friday_off');
    $workShift->is_saturday_off = $request->has('is_saturday_off');

    // Set start and end times for scheduled days (if scheduled shift is selected)
    if ($request->shift_type == 'scheduled') {
        $workShift->sunday_start_time = $request->sunday_start_time;
        $workShift->sunday_end_time = $request->sunday_end_time;
        $workShift->monday_start_time = $request->monday_start_time;
        $workShift->monday_end_time = $request->monday_end_time;
        $workShift->tuesday_start_time = $request->tuesday_start_time;
        $workShift->tuesday_end_time = $request->tuesday_end_time;
        $workShift->wednesday_start_time = $request->wednesday_start_time;
        $workShift->wednesday_end_time = $request->wednesday_end_time;
        $workShift->thursday_start_time = $request->thursday_start_time;
        $workShift->thursday_end_time = $request->thursday_end_time;
        $workShift->friday_start_time = $request->friday_start_time;
        $workShift->friday_end_time = $request->friday_end_time;
        $workShift->saturday_start_time = $request->saturday_start_time;
        $workShift->saturday_end_time = $request->saturday_end_time;
    } 

    // Update the work shift in the database
    $workShift->save();

    // Sync employees: Delete old records and insert new ones
    DB::table('work_shift_employees')->where('work_shift_id', $id)->delete();

    foreach ($request->employee as $employeeId) {
        DB::table('work_shift_employees')->insert([
            'work_shift_id' => $workShift->id,
            'employee_id' => $employeeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return redirect()->route('work_shift.index')->with('success', __('Work shift updated successfully.'));
}


public function destroy($id)
{
    // if(\Auth::user()->can('delete assets management'))
    // {
        $workShift = WorkShift::find($id);
        if($workShift->created_by == \Auth::user()->creatorId())
        {  
            $workShift->employees()->detach();
            $workShift->delete();

            return redirect()->route('work_shift.index')->with('success', __('workshifts successfully deleted.'));
        }
        // else
        // {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }
    

    

}