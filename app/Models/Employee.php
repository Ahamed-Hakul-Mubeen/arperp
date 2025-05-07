<?php

namespace App\Models;

use App\Http\Controllers\AttendanceEmployeeController;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use DateTime;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'dob',
        'gender',
        'phone',
        'address',
        'email',
        'password',
        'employee_id',
        'branch_id',
        'department_id',
        'designation_id',
        'company_doj',
        'documents',
        'account_holder_name',
        'account_number',
        'bank_name',
        'bank_identifier_code',
        'branch_location',
        'tax_payer_id',
        'salary_type',
        'account',
        'salary',
        'created_by',
    ];

    public function documents()
    {
        return $this->hasMany('App\Models\EmployeeDocument', 'employee_id', 'employee_id')->get();
    }

    public function salary_type()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type')->pluck('name')->first();
    }

    public function allowances()
    {
        return $this->hasMany(Allowance::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function saturationDeductions()
    {
        return $this->hasMany(SaturationDeduction::class);
    }

    public function otherPayments()
    {
        return $this->hasMany(OtherPayment::class);
    }

    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function get_net_salary($year = null, $month = null)
    {
        if($year == null)
        {
            $year = date("Y");
        }
        if($month == null)
        {
            $month = date("Y");
        }
        $basic_salary = $this->salary;

        // Calculate total allowances
        $total_allowance = $this->allowances->sum(function ($allowance) use ($basic_salary) {
            return ($allowance->type === 'fixed') ? $allowance->amount : ($allowance->amount * $basic_salary / 100);
        });

        // Calculate total commissions
        $total_commission = $this->commissions->sum(function ($commission) use ($basic_salary) {
            return ($commission->type === 'fixed') ? $commission->amount : ($commission->amount * $basic_salary / 100);
        });

        // Calculate total loans
        // dd($this->loan->sum());
        $total_loan = $this->loans->sum(function ($loan) use ($basic_salary) {
            if($loan->pending_months > 0)
            {
                $loan->pending_months = $loan->pending_months - 1;
                $loan->last_emi = date('m-Y');
                $loan->save();
                
                return ($loan->type === 'fixed') ? $loan->amount : ($loan->amount * $basic_salary / 100);
            }
            return 0;
        });

        // Calculate total saturation deductions
        $total_saturation_deduction = $this->saturationDeductions->sum(function ($deduction) use ($basic_salary) {
            return ($deduction->type === 'fixed') ? $deduction->amount : ($deduction->amount * $basic_salary / 100);
        });

        // Leave deductions
        $leave_deductions = $this->leave_deductions($year, $month);

        // Calculate total other payments
        $total_other_payment = $this->otherPayments->sum(function ($otherPayment) use ($basic_salary) {
            return ($otherPayment->type === 'fixed') ? $otherPayment->amount : ($otherPayment->amount * $basic_salary / 100);
        });

        // Calculate total overtime
        $start_date = $year."-".$month."-01";
        $no_of_days = date('t', strtotime($start_date));
        $end_date = $year."-".$month."-".$no_of_days;

        $total_over_time = 0;
        $over_times      = Overtime::where('employee_id', '=', $this->id)->first();
        if($over_times)
        {
            $totalDuration = \DB::table('attendance_employees')
                            ->select(\DB::raw('SEC_TO_TIME(SUM(TIME_TO_SEC(overtime))) as total_duration'))
                            ->where("employee_id", $this->id)->whereBetween('date', [$start_date, $end_date])
                            ->value('total_duration');
            if($totalDuration)
            {
                $overtimes = explode(":", $totalDuration);
                $total_over_time = ($overtimes[0] + ($overtimes[1] * (1/60))) * $over_times->rate ;
            } else {
                $total_over_time = 0;
            }

        }

        // Calculate net salary
        $net_salary = $basic_salary + $total_allowance + $total_commission - $total_loan - $total_saturation_deduction - $leave_deductions + $total_other_payment + $total_over_time;

        return number_format($net_salary, 2, ".", "");
    }
    public function get_net_salary2()
    {
        $basic_salary = $this->salary;

        // Calculate total allowances
        $total_allowance = $this->allowances->sum(function ($allowance) use ($basic_salary) {
            return ($allowance->type === 'fixed') ? $allowance->amount : ($allowance->amount * $basic_salary / 100);
        });

        // Calculate total commissions
        $total_commission = $this->commissions->sum(function ($commission) use ($basic_salary) {
            return ($commission->type === 'fixed') ? $commission->amount : ($commission->amount * $basic_salary / 100);
        });

        // Calculate total loans
        $total_loan = $this->loans->sum(function ($loan) use ($basic_salary) {
            return ($loan->type === 'fixed') ? $loan->amount : ($loan->amount * $basic_salary / 100);
        });

        // Calculate total saturation deductions
        $total_saturation_deduction = $this->saturationDeductions->sum(function ($deduction) use ($basic_salary) {
            return ($deduction->type === 'fixed') ? $deduction->amount : ($deduction->amount * $basic_salary / 100);
        });

        // Calculate total other payments
        $total_other_payment = $this->otherPayments->sum(function ($otherPayment) use ($basic_salary) {
            return ($otherPayment->type === 'fixed') ? $otherPayment->amount : ($otherPayment->amount * $basic_salary / 100);
        });

        // Calculate net salary
        $net_salary = $basic_salary + $total_allowance + $total_commission - $total_loan - $total_saturation_deduction + $total_other_payment;

        return number_format($net_salary, 2, ".", "");
    }
    public function leave_deductions($year, $month)
    {
        $basic_salary = $this->salary;
        $start_date = $year."-".$month."-01";
        $no_of_days = date('t', strtotime($start_date));
        $end_date = $year."-".$month."-".$no_of_days;
        $weekends = [];

        for ($date = strtotime($start_date); $date <= strtotime($end_date); $date = strtotime("+1 day", $date)) {
            $day = date("N", $date); // 1 (Monday) to 7 (Sunday)
            if ($day == 6 || $day == 7) {
                $weekends[] = date("Y-m-d", $date);
            }
        }
        
        $attendance_days = AttendanceEmployee::where("employee_id", $this->user_id)->whereBetween('date', [$start_date, $end_date])->groupBy('date')->get()->count();       
        $start_date = ($start_date < $this->company_doj) ? date('Y-m-d', strtotime($this->company_doj . ' +1 day')) : $start_date;
        $end_date = ($end_date > date('Y-m-d')) ? date('Y-m-d') : $end_date;
        $holidays = Holiday::whereBetween('date', [$start_date, $end_date])->where('created_by', \Auth::user()->creatorId())->get()->count();
        $approved_leave = Leave::where('employee_id', $this->user_id)->where(function($query) use ($start_date, $end_date) {
                    $query->whereBetween('start_date', [$start_date, $end_date])->orWhereBetween('end_date', [$start_date, $end_date]);
                })->where('status', 'Approved')->get();
        $approved_leave_count = 0;
        foreach($approved_leave as $leave)
        {
            if($leave->start_date >= $start_date && $leave->end_date <= $end_date)
            {
                $approved_leave_count += $leave->total_leave_days;
            }
            else if($leave->start_date < $start_date && $leave->end_date <= $end_date)
            {
                $date1 = new DateTime($start_date);
                $date2 = new DateTime($leave->end_date);
                $interval = $date1->diff($date2);
                $approved_leave_count += ($interval->days + 1);
            }
            else if($leave->start_date >= $start_date && $leave->end_date > $end_date)
            {
                $date1 = new DateTime($leave->start_date);
                $date2 = new DateTime($end_date);
                $interval = $date1->diff($date2);
                $approved_leave_count += ($interval->days + 1);
            }
        }

        $acceptable_days = $attendance_days + $holidays + $approved_leave_count + count($weekends);
        $deduction_days = $no_of_days - $acceptable_days;
        if($deduction_days == 0) {
            $leave_deductions = 0;
        } else {
            $leave_deductions = ($basic_salary / 30 ) * $deduction_days;
        }
        return number_format($leave_deductions, 2, ".", "");
    }
    public static function allowance($id)
    {
        //allowance
        $allowances      = Allowance::where('employee_id', '=', $id)->get();
        $total_allowance = 0;
        foreach ($allowances as $allowance) {
            $total_allowance = $allowance->amount + $total_allowance;
        }

        $allowance_json = json_encode($allowances);

        return $allowance_json;
    }

    public static function commission($id)
    {
        //commission
        $commissions      = Commission::where('employee_id', '=', $id)->get();
        $total_commission = 0;
        foreach ($commissions as $commission) {
            $total_commission = $commission->amount + $total_commission;
        }
        $commission_json = json_encode($commissions);

        return $commission_json;
    }

    public static function loan($id)
    {
        //Loan
        $loans      = Loan::where('employee_id', '=', $id)->get();
        $total_loan = 0;
        foreach ($loans as $loan) {
            $total_loan = $loan->amount + $total_loan;
        }
        $loan_json = json_encode($loans);

        return $loan_json;
    }

    public static function saturation_deduction($id)
    {
        //Saturation Deduction
        $saturation_deductions      = SaturationDeduction::where('employee_id', '=', $id)->get();
        $total_saturation_deduction = 0;
        foreach ($saturation_deductions as $saturation_deduction) {
            $total_saturation_deduction = $saturation_deduction->amount + $total_saturation_deduction;
        }
        $saturation_deduction_json = json_encode($saturation_deductions);

        return $saturation_deduction_json;
    }

    public static function other_payment($id)
    {
        //OtherPayment
        $other_payments      = OtherPayment::where('employee_id', '=', $id)->get();
        $total_other_payment = 0;
        foreach ($other_payments as $other_payment) {
            $total_other_payment = $other_payment->amount + $total_other_payment;
        }
        $other_payment_json = json_encode($other_payments);

        return $other_payment_json;
    }

    public static function overtime($year, $month, $id)
    {
        $start_date = $year."-".$month."-01";
        $no_of_days = date('t', strtotime($start_date));
        $end_date = $year."-".$month."-".$no_of_days;

        $over_time_arr = [];
        $over_times      = Overtime::where('employee_id', '=', $id)->first();
        if($over_times)
        {
            $totalDuration = \DB::table('attendance_employees')
                            ->select(\DB::raw('SEC_TO_TIME(SUM(TIME_TO_SEC(overtime))) as total_duration'))
                            ->where("employee_id", $id)->whereBetween('date', [$start_date, $end_date])
                            ->value('total_duration');
            if($totalDuration)
            {
                $overtimes = explode(":", $totalDuration);
                $over_time_amount = ($overtimes[0] + ($overtimes[1] * (1/60))) * $over_times->rate ;
                $over_time_arr = array("hours" => $overtimes[0], "minutes" => $overtimes[0], "amount" => $over_time_amount);
            } else {
                $over_time_arr = [];
            }

        }

        return json_encode($over_time_arr);
    }

    public static function employee_id()
    {
        $employee = Employee::latest()->first();

        return !empty($employee) ? $employee->id + 1 : 1;
    }

    public function branch()
    {
        return $this->hasOne('App\Models\Branch', 'id', 'branch_id');
    }

    public function department()
    {
        return $this->hasOne('App\Models\Department', 'id', 'department_id');
    }

    public function designation()
    {
        return $this->hasOne('App\Models\Designation', 'id', 'designation_id');
    }

    public function salaryType()
    {
        return $this->hasOne('App\Models\PayslipType', 'id', 'salary_type');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function paySlip()
    {
        return $this->hasOne('App\Models\PaySlip', 'id', 'employee_id');
    }

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account');
    }


    public function present_status($employee_id, $data)
    {
        return AttendanceEmployee::where('employee_id', $employee_id)->where('date', $data)->first();
    }
    
    public function termination()
    {
    return $this->hasOne(Termination::class, 'employee_id', 'id');
    }

    public function resignation()
    {
    return $this->hasOne(Resignation::class, 'employee_id', 'id');
    }

    public static function employee_salary($salary)
    {
        $employee = Employee::where("salary", $salary)->first();
        if ($employee->salary == '0' || $employee->salary == '0.0') {
            return "-";
        } else {
            return $employee->salary;
        }
    }
}
