<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\AllowanceOption;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Http\Request;

class AllowanceController extends Controller
{

    public function allowanceCreate($id)
    {

        $allowance_options = AllowanceOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $employee          = Employee::find($id);
        $Allowancetypes = Allowance::$Allowancetype;

        return view('allowance.create', compact('employee', 'allowance_options','Allowancetypes'));
    }

    public function store(Request $request)
    {


        if(\Auth::user()->can('create allowance'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'employee_id' => 'required',
                                   'allowance_option' => 'required',
                                   'title' => 'required',
                                   'amount' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $allowance                   = new Allowance();
            $allowance->employee_id      = $request->employee_id;
            $allowance->allowance_option = $request->allowance_option;
            $allowance->title            = $request->title;
            $allowance->type             = $request->type;
            $allowance->amount           = $request->amount;
            $allowance->created_by       = \Auth::user()->creatorId();
            $allowance->save();

            $employee          = Employee::find($request->employee_id);
            $empsal  = $request->amount * $employee->salary / 100;

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            EmployeeHistory::storeHistory(
                $request->employee_id,
                "Allowance Added",
                $request->title . " allowance of " . \Auth::user()->priceFormat($request->type == "fixed" ? $request->amount : $empsal) . " has been added.",
                $ip
            );

            return redirect()->back()->with('success', __('Allowance  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Allowance $allowance)
    {
        return redirect()->route('allowance.index');
    }

    public function edit($allowance)
    {
        $allowance = Allowance::find($allowance);
        if(\Auth::user()->can('edit allowance'))
        {
            if($allowance->created_by == \Auth::user()->creatorId())
            {
                $allowance_options = AllowanceOption::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $Allowancetypes =Allowance::$Allowancetype;
                return view('allowance.edit', compact('allowance', 'allowance_options','Allowancetypes'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Allowance $allowance)
    {
        if(\Auth::user()->can('edit allowance'))
        {
            if($allowance->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [

                                       'allowance_option' => 'required',
                                       'title' => 'required',
                                       'amount' => 'required',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $allowance->allowance_option = $request->allowance_option;
                $allowance->title            = $request->title;
                $allowance->type             = $request->type;
                $allowance->amount           = $request->amount;
                $allowance->save();

                $employee          = Employee::find($allowance->employee_id);
                $empsal  = $request->amount * $employee->salary / 100;

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(
                    $allowance->employee_id,
                    "Allowance Updated",
                    $request->title . " allowance updated to " . \Auth::user()->priceFormat($request->type == "fixed" ? $request->amount : $empsal) . ".",
                    $ip
                );

                return redirect()->back()->with('success', __('Allowance successfully updated.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Allowance $allowance)
    {

        if(\Auth::user()->can('delete allowance'))
        {
            if($allowance->created_by == \Auth::user()->creatorId())
            {
                $allowance->delete();

                $employee          = Employee::find($allowance->employee_id);
                $empsal  = $allowance->amount * $employee->salary / 100;

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(
                    $allowance->employee_id,
                    "Allowance Deleted",
                    $allowance->title . " allowance of " . \Auth::user()->priceFormat($allowance->type == "fixed" ? $allowance->amount : $empsal) . " has been deleted.",
                    $ip
                );

                return redirect()->back()->with('success', __('Allowance successfully deleted.'));
            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
