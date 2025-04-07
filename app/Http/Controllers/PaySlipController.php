<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\PayslipExport;
use App\Models\Allowance;
use App\Models\AttendanceEmployee;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Commission;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Loan;
use App\Models\OtherPayment;
use App\Models\Overtime;
use App\Models\PaySlip;
use App\Models\PayslipType;
use App\Models\SaturationDeduction;
use App\Models\TransactionLines;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class PaySlipController extends Controller
{

    public function index()
    {
        if(\Auth::user()->can('manage pay slip') || \Auth::user()->type != 'client' || \Auth::user()->type != 'company')
        {
            $employees = Employee::where(
                [
                    'created_by' => \Auth::user()->creatorId(),
                ]
            )->first();

            $month = [
                '01' => 'JAN',
                '02' => 'FEB',
                '03' => 'MAR',
                '04' => 'APR',
                '05' => 'MAY',
                '06' => 'JUN',
                '07' => 'JUL',
                '08' => 'AUG',
                '09' => 'SEP',
                '10' => 'OCT',
                '11' => 'NOV',
                '12' => 'DEC',
            ];

            $year = [
                // date("Y", strtotime("+1 year")) => date("Y", strtotime("+1 year")),
                date("Y") => date("Y"),
                date("Y", strtotime("-1 year")) => date("Y", strtotime("-1 year")),
                date("Y", strtotime("-2 year")) => date("Y", strtotime("-2 year")),
                date("Y", strtotime("-3 year")) => date("Y", strtotime("-3 year")),
                date("Y", strtotime("-4 year")) => date("Y", strtotime("-4 year")),
                date("Y", strtotime("-5 year")) => date("Y", strtotime("-5 year")),
            ];
            $total_payable = Payslip::where('created_by', \Auth::user()->creatorId())->where('salary_month',date('Y-m'))->sum('net_payble');
            if(auth()->user()->type == "Employee")
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Payslip List", $ip);
            }
            return view('payslip.index', compact('employees', 'month', 'year','total_payable'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(), [
                'month' => 'required',
                'year' => 'required',
            ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $month = $request->month;
        $year  = $request->year;

        if($month > date("m") && $year >= date("Y"))
        {
            return redirect()->back()->with('error', "Cannot Create Payslip for Upcommeing  Months");
        }

        $formate_month_year = $year . '-' . $month;

        $existingPaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->pluck('employee_id')->toArray();
        $employee_list   = Employee::where('created_by', \Auth::user()->creatorId())->where('company_doj', '<=', date($year . '-' . $month . '-t'))->whereNotIn('id', $existingPaysilp)->where('salary', '>', 0)->get();
        // dd($employee_list,$existingPaysilp);
        foreach($employee_list as $employee)
        {
            $start_date = $year."-".$month."-01";
            $no_of_days = date('t', strtotime($start_date));
            $end_date = $year."-".$month."-".$no_of_days;

            $check_attendance = AttendanceEmployee::where('employee_id', $employee->id)->whereBetween('date', [$start_date, $end_date])->first();
            if($check_attendance)
            {
                $payslipEmployee                       = new PaySlip();
                $payslipEmployee->employee_id          = $employee->id;
                $payslipEmployee->net_payble           = $employee->get_net_salary($year, $month);
                $payslipEmployee->salary_month         = $formate_month_year;
                $payslipEmployee->status               = 0;
                $payslipEmployee->basic_salary         = $employee->salary;
                $payslipEmployee->allowance            = Employee::allowance($employee->id);
                $payslipEmployee->commission           = Employee::commission($employee->id);
                $payslipEmployee->loan                 = Employee::loan($employee->id);
                $payslipEmployee->saturation_deduction = Employee::saturation_deduction($employee->id);
                $payslipEmployee->leave_deductions     = $employee->leave_deductions($year, $month);
                $payslipEmployee->other_payment        = Employee::other_payment($employee->id);
                $payslipEmployee->overtime             = Employee::overtime($year, $month, $employee->id);
                $payslipEmployee->created_by           = \Auth::user()->creatorId();
                // dd($payslipEmployee);
                $payslipEmployee->save();

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(
                    $payslipEmployee->employee_id,
                    "Payslip Created",
                    "Payslip created for " . $formate_month_year . ".",
                    $ip
                );
            }
        }
        if(count($employee_list) == 0 && count($existingPaysilp) == 0)
        {
            return redirect()->route('payslip.index')->with('error', __('Please set employee salary.'));
        }
        else if(count($employee_list) == 0)
        {
            return redirect()->route('payslip.index')->with('error', __('Payslip Already created.'));
        }
        else
        {
            return redirect()->route('payslip.index')->with('success', __('Payslip successfully created.'));
        }

    }


    public function destroy($id)
    {
        $payslip = PaySlip::find($id);
        $payslip_month = date('m-Y',strtotime($payslip->created_at));
        TransactionLines::where("reference", "Payslip")->where("reference_id", $id)->where('created_by', \Auth::user()->creatorId())->delete();

        $employee = Employee::find($payslip->employee_id);
        Utility::bankAccountBalance($employee->account, $payslip->net_payble, 'credit');

        $payslip->delete();

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        EmployeeHistory::storeHistory(
            $payslip->employee_id,
            "Payslip Deleted",
            "Payslip deleted for " . date('F Y', strtotime($payslip->created_at)) . ".",
            $ip
        );

        $loan = Loan::where('employee_id', $payslip->employee_id)->where('last_emi', $payslip_month)->increment('pending_months', 1);

        return true;
    }

    public function showemployee($paySlip)
    {
        $payslip = PaySlip::find($paySlip);

        return view('payslip.show', compact('payslip'));
    }


    public function search_json(Request $request)
    {

        $formate_month_year = $request->datePicker;
        $validatePaysilp    = PaySlip::where('salary_month', '=', $formate_month_year)->where('created_by', \Auth::user()->creatorId())->get()->toarray();
        // dd($validatePaysilp);
        $data=[];
        if (empty($validatePaysilp))
        {
            $data=[];
            return [];
        } else {
            $paylip_employee = PaySlip::select(
                [
                    'employees.id',
                    'employees.employee_id',
                    'employees.name',
                    'payslip_types.name as payroll_type',
                    'pay_slips.basic_salary',
                    'pay_slips.net_payble',
                    'pay_slips.id as pay_slip_id',
                    'pay_slips.status',
                    'employees.user_id',
                ]
            )->leftjoin(
                'employees',
                function ($join) use ($formate_month_year) {
                    $join->on('employees.id', '=', 'pay_slips.employee_id');
                    $join->on('pay_slips.salary_month', '=', \DB::raw("'" . $formate_month_year . "'"));
                    $join->leftjoin('payslip_types', 'payslip_types.id', '=', 'employees.salary_type');
                }
            )->where('employees.created_by', \Auth::user()->creatorId())->get();

                // dd($paylip_employee);
            foreach ($paylip_employee as $employee) {

                if (Auth::user()->type == 'Employee') {
                    // dd($employee->user_id);
                    if (Auth::user()->id == $employee->user_id) {
                        $tmp   = [];
                        $tmp[] = $employee->id;
                        $tmp[] = $employee->name;
                        $tmp[] = $employee->payroll_type;
                        $tmp[] = $employee->pay_slip_id;
                        $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                        $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                        if ($employee->status == 1) {
                            $tmp[] = 'paid';
                        } else {
                            $tmp[] = 'unpaid';
                        }
                        $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                        $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                        $data[] = $tmp;
                    }
                } else {

                    $tmp   = [];
                    $tmp[] = $employee->id;
                    $tmp[] = \Auth::user()->employeeIdFormat($employee->employee_id);
                    $tmp[] = $employee->name;
                    $tmp[] = $employee->payroll_type;
                    $tmp[] = !empty($employee->basic_salary) ? \Auth::user()->priceFormat($employee->basic_salary) : '-';
                    $tmp[] = !empty($employee->net_payble) ? \Auth::user()->priceFormat($employee->net_payble) : '-';
                    if ($employee->status == 1) {
                        $tmp[] = 'Paid';
                    } else {
                        $tmp[] = 'UnPaid';
                    }
                    $tmp[]  = !empty($employee->pay_slip_id) ? $employee->pay_slip_id : 0;
                    $tmp['url']  = route('employee.show', Crypt::encrypt($employee->id));
                    $data[] = $tmp;
                }
            }

            return $data;
        }
    }

    public function paysalary($id, $date)
    {
        $employeePayslip = PaySlip::where('employee_id', '=', $id)->where('created_by', \Auth::user()->creatorId())->where('salary_month', '=', $date)->first();

        $account = Employee::find($id);
        Utility::bankAccountBalance($account->account, $employeePayslip->net_payble, 'debit');

        $bank_acc = BankAccount::find($account->account);
        $data = [
            'account_id' => $bank_acc->chart_account_id,
            'transaction_type' => 'Credit',
            'transaction_amount' => $employeePayslip->net_payble,
            'reference' => 'Payslip',
            'reference_id' => $employeePayslip->id,
            'reference_sub_id' => 0,
            'date' => date("Y-m-d"),
        ];
        Utility::addTransactionLines($data, "new");

        $Salaries_co_acc = ChartOfAccount::where('code', 5410)->where('created_by', \Auth::user()->creatorId())->first();
        $data = [
            'account_id' => $Salaries_co_acc->id,
            'transaction_type' => 'Debit',
            'transaction_amount' => $employeePayslip->net_payble,
            'reference' => 'Payslip',
            'reference_id' => $employeePayslip->id,
            'reference_sub_id' => 0,
            'date' => date("Y-m-d"),
        ];
        Utility::addTransactionLines($data, "new");

        if(!empty($employeePayslip))
        {
            $employeePayslip->status = 1;
            $employeePayslip->save();

            return redirect()->route('payslip.index')->with('success', __('Payslip Payment successfully.'));
        }
        else
        {
            return redirect()->route('payslip.index')->with('error', __('Payslip Payment failed.'));
        }

    }

    public function bulk_pay_create($date)
    {
        $Employees       = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->get();
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        return view('payslip.bulkcreate', compact('Employees', 'unpaidEmployees', 'date'));
    }

    public function bulkpayment(Request $request, $date)
    {
        $unpaidEmployees = PaySlip::where('salary_month', $date)->where('created_by', \Auth::user()->creatorId())->where('status', '=', 0)->get();

        foreach($unpaidEmployees as $employee)
        {
            $account = Employee::find($employee->employee_id);
            Utility::bankAccountBalance($account->account, $employee->net_payble, 'debit');

            $bank_acc = BankAccount::find($account->account);
            $data = [
                'account_id' => $bank_acc->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $employee->net_payble,
                'reference' => 'Payslip',
                'reference_id' => $employee->id,
                'reference_sub_id' => 0,
                'date' => date("Y-m-d"),
            ];
            Utility::addTransactionLines($data, "new");

            $Salaries_co_acc = ChartOfAccount::where('code', 5410)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $Salaries_co_acc->id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $employee->net_payble,
                'reference' => 'Payslip',
                'reference_id' => $employee->id,
                'reference_sub_id' => 0,
                'date' => date("Y-m-d"),
            ];
            Utility::addTransactionLines($data, "new");

            $employee->status = 1;
            $employee->save();
        }

        return redirect()->route('payslip.index')->with('success', __('Payslip Bulk Payment successfully.'));
    }

    public function employeepayslip()
    {
        $employees = Employee::where(
            [
                'user_id' => \Auth::user()->id,
            ]
        )->first();

        $payslip = PaySlip::where('employee_id', '=', $employees->id)->get();

        return view('payslip.employeepayslip', compact('payslip'));

    }

    public function pdf($id, $month)
{
    // Retrieve the payslip record based on employee ID, month, and creator ID
    $payslip = PaySlip::where('employee_id', $id)
        ->where('salary_month', $month)
        ->where('created_by', \Auth::user()->creatorId())
        ->first();

    // Retrieve the employee based on the payslip's employee ID
    $employee = Employee::find($payslip->employee_id);

    $payslipType = PayslipType::where('id', $employee->salary_type)->first();

    // Get the digital signature
    $digitalSignature = $payslipType ? $payslipType->digital_signature : null;

    $payslipDetail = Utility::employeePayslipDetail($id, $month);

    if(auth()->user()->type == "Employee")
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Payslip viewed for ". $month, $ip);
    }

    // Pass the digital signature to the view
    return view('payslip.pdf', compact('payslip', 'employee', 'payslipDetail', 'digitalSignature'));
}

    public function send($id, $month)
    {
        $setings = Utility::settings();
//        dd($setings);
        if($setings['payslip_sent'] == 1)
        {
            $payslip  = PaySlip::where('employee_id', $id)->where('salary_month', $month)->where('created_by', \Auth::user()->creatorId())->first();
            $employee = Employee::find($payslip->employee_id);

            $payslip->name  = $employee->name;
            $payslip->email = $employee->email;

            $payslipId    = Crypt::encrypt($payslip->id);
            $payslip->url = route('payslip.payslipPdf', $payslipId);
//            dd($payslip->url);

            $payslipArr = [

                'employee_name'=> $employee->name,
                'employee_email' => $employee->email,
                'payslip_name' =>   $payslip->name,
                'payslip_salary_month' => $payslip->salary_month,
                'payslip_url' =>$payslip->url,

            ];
            $resp = Utility::sendEmailTemplate('payslip_sent', [$employee->id => $employee->email], $payslipArr);



            return redirect()->back()->with('success', __('Payslip successfully sent.') .(($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }

        return redirect()->back()->with('success', __('Payslip successfully sent.'));

    }

    public function payslipPdf($id)
    {
        $payslipId = Crypt::decrypt($id);

        $payslip  = PaySlip::where('id', $payslipId)->where('created_by', \Auth::user()->creatorId())->first();
        $employee = Employee::find($payslip->employee_id);

        $payslipDetail = Utility::employeePayslipDetail($payslip->employee_id);

        return view('payslip.payslipPdf', compact('payslip', 'employee', 'payslipDetail'));
    }

    public function editEmployee($paySlip)
    {
        $payslip = PaySlip::find($paySlip);

        return view('payslip.salaryEdit', compact('payslip'));
    }

    public function updateEmployee(Request $request, $id)
    {


        if(isset($request->allowance) && !empty($request->allowance))
        {
            $allowances   = $request->allowance;
            $allowanceIds = $request->allowance_id;
            foreach($allowances as $k => $allownace)
            {
                $allowanceData         = Allowance::find($allowanceIds[$k]);
                $allowanceData->amount = $allownace;
                $allowanceData->save();
            }
        }


        if(isset($request->commission) && !empty($request->commission))
        {
            $commissions   = $request->commission;
            $commissionIds = $request->commission_id;
            foreach($commissions as $k => $commission)
            {
                $commissionData         = Commission::find($commissionIds[$k]);
                $commissionData->amount = $commission;
                $commissionData->save();
            }
        }

        if(isset($request->loan) && !empty($request->loan))
        {
            $loans   = $request->loan;
            $loanIds = $request->loan_id;
            foreach($loans as $k => $loan)
            {
                $loanData         = Loan::find($loanIds[$k]);
                $loanData->amount = $loan;
                $loanData->save();
            }
        }


        if(isset($request->saturation_deductions) && !empty($request->saturation_deductions))
        {
            $saturation_deductionss   = $request->saturation_deductions;
            $saturation_deductionsIds = $request->saturation_deductions_id;
            foreach($saturation_deductionss as $k => $saturation_deductions)
            {

                $saturation_deductionsData         = SaturationDeduction::find($saturation_deductionsIds[$k]);
                $saturation_deductionsData->amount = $saturation_deductions;
                $saturation_deductionsData->save();
            }
        }


        if(isset($request->other_payment) && !empty($request->other_payment))
        {
            $other_payments   = $request->other_payment;
            $other_paymentIds = $request->other_payment_id;
            foreach($other_payments as $k => $other_payment)
            {
                $other_paymentData         = OtherPayment::find($other_paymentIds[$k]);
                $other_paymentData->amount = $other_payment;
                $other_paymentData->save();
            }
        }

        $hours = $request->overtime_hours;
        $minutes = $request->overtime_minutes;
        $amount = $request->overtime_amount;

        $overtime_arr = array("hours" => $hours, "minutes" => $minutes, "amount" => $amount);

        $payslipEmployee                       = PaySlip::find($request->payslip_id);

        $pay_arr = explode("-",$payslipEmployee->salary_month);

        $year = $pay_arr[0];
        $month = $pay_arr[1];

        $employee = Employee::find($payslipEmployee->employee_id);

        $payslipEmployee->net_payble           = $employee->get_net_salary($year, $month);
        $payslipEmployee->allowance            = Employee::allowance($payslipEmployee->employee_id);
        $payslipEmployee->commission           = Employee::commission($payslipEmployee->employee_id);
        $payslipEmployee->loan                 = Employee::loan($payslipEmployee->employee_id);
        $payslipEmployee->saturation_deduction = Employee::saturation_deduction($payslipEmployee->employee_id);
        $payslipEmployee->leave_deductions     = $employee->leave_deductions($year, $month);
        $payslipEmployee->other_payment        = Employee::other_payment($payslipEmployee->employee_id);
        $payslipEmployee->overtime             = json_encode($overtime_arr);
        $payslipEmployee->net_payble           = Employee::find($payslipEmployee->employee_id)->get_net_salary($year, $month);
        $payslipEmployee->save();

        return redirect()->route('payslip.index')->with('success', __('Employee payroll successfully updated.'));
    }

    public function export(Request $request)
    {
        $name = 'payslip_' . date('Y-m-d i:h:s');
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Export", "Payslip Exported", $ip);
        }
        $data = Excel::download(new PayslipExport($request), $name . '.xlsx'); 
        return $data;
    }
}
