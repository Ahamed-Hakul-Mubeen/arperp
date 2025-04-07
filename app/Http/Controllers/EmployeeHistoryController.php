<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class EmployeeHistoryController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage employee'))
        {
            $employees = Employee::where('is_active',1)->get();
            return view('employee-history.index', compact('employees'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function view($id)
    {
        $id = Crypt::decrypt($id);
        $employee = Employee::find($id);
        $employee_history = EmployeeHistory::with('employee')->where('employee_id', $id)->get();
        // dd($employee_history);
        return view('employee-history.timeline', compact('employee','employee_history'));
    }
}
