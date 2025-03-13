<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Employee;
use App\Models\Resignation;
use App\Models\TransactionLines;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ResignationController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage resignation'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $emp          = Employee::where('user_id', '=', \Auth::user()->id)->first();
                $resignations = Resignation::where('created_by', '=', \Auth::user()->creatorId())->where('employee_id', '=', $emp->id)->with('employee')->get();
            }
            else
            {
                $resignations = Resignation::where('created_by', '=', \Auth::user()->creatorId())->with('employee')->get();
            }

            return view('resignation.index', compact('resignations'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create resignation'))
        {
            if(Auth::user()->type == 'company')
            {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }
            else
            {
                $employees = Employee::where('user_id', \Auth::user()->id)->get()->pluck('name', 'id');
            }
            $employees->prepend('Select Employees', '');

            return view('resignation.create', compact('employees'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create resignation'))
        {

            $validator = \Validator::make(
                $request->all(), [
                        'notice_date' => 'required',
                        'resignation_date' => 'required',
                    ]
            );

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $resignation = new Resignation();
            $user        = \Auth::user();
            if($user->type == 'Employee')
            {
                $employee                 = Employee::where('user_id', $user->id)->first();
                $resignation->employee_id = $employee->id;
            }
            else
            {
                $resignation->employee_id = $request->employee_id;
            }
            $resignation->notice_date      = $request->notice_date;
            $resignation->resignation_date = $request->resignation_date;
            $resignation->no_of_years      = $request->no_of_years;
            $resignation->base_salary      = $request->base_salary;
            $resignation->settlement       = $request->settlement;
            $resignation->description      = $request->description;
            $resignation->created_by       = \Auth::user()->creatorId();

            $resignation->save();

            if($request->settlement != 0)
            {
                $account = Employee::find($request->employee_id);
                $user_employee = User::find($account->user_id);
                if($user_employee)
                {
                    $user_employee->is_enable_login = 0;
                    $user_employee->save();
                }
                Utility::bankAccountBalance($account->account, $request->settlement, 'debit');

                $bank_acc = BankAccount::find($account->account);
                $data = [
                    'account_id' => $bank_acc->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $request->settlement,
                    'reference' => 'Settlement',
                    'reference_id' => $resignation->id,
                    'reference_sub_id' => $request->employee_id,
                    'date' => date("Y-m-d"),
                ];
                Utility::addTransactionLines($data, "new");

                $Salaries_co_acc = ChartOfAccount::where('code', 5450)->where('created_by', \Auth::user()->creatorId())->first();
                $data = [
                    'account_id' => $Salaries_co_acc->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->settlement,
                    'reference' => 'Settlement',
                    'reference_id' => $resignation->id,
                    'reference_sub_id' => $request->employee_id,
                    'date' => date("Y-m-d"),
                ];
                Utility::addTransactionLines($data, "new");
            }
            else
            {
                $account = Employee::find($request->employee_id);
                $user_employee = User::find($account->user_id);
                if($user_employee)
                {
                    $user_employee->is_enable_login = 0;
                    $user_employee->save();
                }
            }

            $setings = Utility::settings();
            if($setings['resignation_sent'] == 1)
            {
                $employee           = Employee::find($resignation->employee_id);
                $resignation->name  = $employee->name;
                $resignation->email = $employee->email;

                $resignationArr = [
                    'resignation_email'=>$employee->email,
                    'assign_user'=>$employee->name,
                    'resignation_date'  =>$resignation->resignation_date,
                    'notice_date'  =>$resignation->notice_date,

                ];
//                dd($resignationArr);
                $resp = Utility::sendEmailTemplate('resignation_sent', [$employee->email], $resignationArr);



                return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'). ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }

            return redirect()->route('resignation.index')->with('success', __('Resignation  successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Resignation $resignation)
    {
        return redirect()->route('resignation.index');
    }

    public function edit(Resignation $resignation)
    {
        if(\Auth::user()->can('edit resignation'))
        {
            if(Auth::user()->type == 'company')
            {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }
            else
            {
                $employees = Employee::where('user_id', \Auth::user()->id)->get()->pluck('name', 'id');
            }
            $employees->prepend('Select Employees', '');
            if($resignation->created_by == \Auth::user()->creatorId())
            {

                return view('resignation.edit', compact('resignation', 'employees'));
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

    public function update(Request $request, Resignation $resignation)
    {
        if(\Auth::user()->can('edit resignation'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                            'notice_date' => 'required',
                            'resignation_date' => 'required',
                        ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                if(\Auth::user()->type != 'employee')
                {
                    $resignation->employee_id = $request->employee_id;
                }

                $account = Employee::find($request->employee_id);
                Utility::bankAccountBalance($account->account, $resignation->settlement, 'credit');
                TransactionLines::where('reference_id', $resignation->id)->where('reference', 'Settlement')->where('created_by', \Auth::user()->creatorId())->delete();

                $resignation->notice_date      = $request->notice_date;
                $resignation->resignation_date = $request->resignation_date;
                $resignation->no_of_years      = $request->no_of_years;
                $resignation->base_salary      = $request->base_salary;
                $resignation->settlement       = $request->settlement;
                $resignation->description      = $request->description;

                Utility::bankAccountBalance($account->account, $request->settlement, 'debit');

                $bank_acc = BankAccount::find($account->account);
                $data = [
                    'account_id' => $bank_acc->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $request->settlement,
                    'reference' => 'Settlement',
                    'reference_id' => $resignation->id,
                    'reference_sub_id' => $request->employee_id,
                    'date' => date("Y-m-d"),
                ];
                Utility::addTransactionLines($data, "new");

                $Salaries_co_acc = ChartOfAccount::where('code', 5450)->where('created_by', \Auth::user()->creatorId())->first();
                $data = [
                    'account_id' => $Salaries_co_acc->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->settlement,
                    'reference' => 'Settlement',
                    'reference_id' => $resignation->id,
                    'reference_sub_id' => $request->employee_id,
                    'date' => date("Y-m-d"),
                ];
                Utility::addTransactionLines($data, "new");

                $resignation->save();

                return redirect()->route('resignation.index')->with('success', __('Resignation successfully updated.'));
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

    public function destroy(Resignation $resignation)
    {
        if(\Auth::user()->can('delete resignation'))
        {
            if($resignation->created_by == \Auth::user()->creatorId())
            {
                $account = Employee::find($resignation->employee_id);
                Utility::bankAccountBalance($account->account_id, $resignation->settlement, 'credit');
                TransactionLines::where('reference_id', $resignation->id)->where('reference', 'Settlement')->where('created_by', \Auth::user()->creatorId())->delete();

                $resignation->delete();

                return redirect()->route('resignation.index')->with('success', __('Resignation successfully deleted.'));
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

    public function fetch_employee(Request $request)
    {
        $employee_id = $request->employee_id;
        $data = Employee::find($employee_id);
        if($data && $data->company_doj)
        {
            $date1=date_create(date("Y-m-d", strtotime($data->company_doj)));
            $date2=date_create();
            $diff=date_diff($date1,$date2);
            return array("status" => 1, "year" => $diff->y, "salary" => $data->salary);
        }
        return array("status" => 0);
    }
}
