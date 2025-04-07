<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function commissionCreate($id)
    {
        $employee = Employee::find($id);
        $commissions =Commission::$commissiontype;
        return view('commission.create', compact('employee','commissions'));
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('create commission'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'employee_id' => 'required',
                                   'title' => 'required',
                                   'amount' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $commission              = new Commission();
            $commission->employee_id = $request->employee_id;
            $commission->title       = $request->title;
            $commission->type        = $request->type;
            $commission->amount      = $request->amount;
            $commission->created_by  = \Auth::user()->creatorId();
            $commission->save();

            $employee          = Employee::find($request->employee_id);
            $empsal  = $request->amount * $employee->salary / 100;

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            EmployeeHistory::storeHistory(
                $request->employee_id,
                "Commission Added",
                $request->title . " commission of " . \Auth::user()->priceFormat($request->type == "fixed" ? $request->amount : $empsal) . " has been added.",
                $ip
            );

            return redirect()->back()->with('success', __('Commission  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Commission $commission)
    {
        return redirect()->route('commision.index');
    }

    public function edit($commission)
    {
        $commission = Commission::find($commission);
        if(\Auth::user()->can('edit commission'))
        {
            $commissions =Commission::$commissiontype;

            if($commission->created_by == \Auth::user()->creatorId())
            {

                return view('commission.edit', compact('commission','commissions'));
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

    public function update(Request $request, Commission $commission)
    {
        if(\Auth::user()->can('edit commission'))
        {
            if($commission->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [

                                       'title' => 'required',
                                       'amount' => 'required',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $commission->title  = $request->title;
                $commission->type  = $request->type;
                $commission->amount = $request->amount;
                $commission->save();

                $employee          = Employee::find($commission->employee_id);
                $empsal  = $request->amount * $employee->salary / 100;

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(
                    $commission->employee_id,
                    "Commission Updated",
                    $request->title . " commission updated to " . \Auth::user()->priceFormat($request->type == "fixed" ? $request->amount : $empsal) . ".",
                    $ip
                );

                return redirect()->back()->with('success', __('Commission successfully updated.'));
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

    public function destroy(Commission $commission)
    {

        if(\Auth::user()->can('delete commission'))
        {
            if($commission->created_by == \Auth::user()->creatorId())
            {

                $commission->delete();

                $employee          = Employee::find($commission->employee_id);
                $empsal  = $commission->amount * $employee->salary / 100;

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(
                    $commission->employee_id,
                    "Commission Deleted",
                    $commission->title . " commission of " . \Auth::user()->priceFormat($commission->type == "fixed" ? $commission->amount : $empsal) . " has been deleted.",
                    $ip
                );

                return redirect()->back()->with('success', __('Commission successfully deleted.'));
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
