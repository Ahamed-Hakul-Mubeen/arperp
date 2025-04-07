<?php

namespace App\Http\Controllers;

use App\Imports\AttendanceImport;
use App\Models\AttendanceEmployee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\IpRestrict;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Carbon\Carbon;

class AttendanceEmployeeController extends Controller
{
    public function index(Request $request)
    {

        if (\Auth::user()->can('manage attendance')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Company', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');

            if (\Auth::user()->type != 'client' && \Auth::user()->type != 'company' && \Auth::user()->type != 'HR') {

                $emp = !empty(\Auth::user()->employee)?\Auth::user()->employee->id : 0;

                $attendanceEmployee = AttendanceEmployee::where('employee_id', $emp);

                if ($request->type == 'monthly' && !empty($request->month)) {
                    $month = date('m', strtotime($request->month));
                    $year = date('Y', strtotime($request->month));

                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date($year . '-' . $month . '-t');

                    $attendanceEmployee->whereBetween(
                        'date', [
                            $start_date,
                            $end_date,
                        ]
                    );
                } elseif ($request->type == 'daily' && !empty($request->date)) {
                    $attendanceEmployee->where('date', $request->date);
                } else {
                    $month = date('m');
                    $year = date('Y');
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date($year . '-' . $month . '-t');

                    $attendanceEmployee->whereBetween(
                        'date', [
                            $start_date,
                            $end_date,
                        ]
                    );
                }
                $attendanceEmployee = $attendanceEmployee->get();

            } else {

                $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId());

                if (!empty($request->branch)) {
                    $employee->where('branch_id', $request->branch);
                }

                if (!empty($request->department)) {
                    $employee->where('department_id', $request->department);
                }
                $employee = $employee->get()->pluck('id');

                $attendanceEmployee = AttendanceEmployee::whereIn('employee_id', $employee);

                if ($request->type == 'monthly' && !empty($request->month)) {
                    $month = date('m', strtotime($request->month));
                    $year = date('Y', strtotime($request->month));

                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date($year . '-' . $month . '-t');

                    $attendanceEmployee->whereBetween(
                        'date', [
                            $start_date,
                            $end_date,
                        ]
                    );
                } elseif ($request->type == 'daily' && !empty($request->date)) {
                    $attendanceEmployee->where('date', $request->date);
                } else {
                    $month = date('m');
                    $year = date('Y');
                    $start_date = date($year . '-' . $month . '-01');
                    $end_date = date($year . '-' . $month . '-t');

                    $attendanceEmployee->whereBetween(
                        'date', [
                            $start_date,
                            $end_date,
                        ]
                    );
                }

//                dd($attendanceEmployee->toSql(), $attendanceEmployee->getBindings());
                $attendanceEmployee = $attendanceEmployee->get();

            }
            if(auth()->user()->type == "Employee")
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Attendance List", $ip);
            }
            return view('attendance.index', compact('attendanceEmployee', 'branch', 'department'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create attendance')) {
            $employees = User::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', "employee")->get()->pluck('name', 'id');

            return view('attendance.create', compact('employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create attendance')) {
            $validator = \Validator::make(
                $request->all(), [
                    'employee_id' => 'required',
                    'date' => 'required',
                    'clock_in' => 'required',
                    'clock_out' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $startTime = Utility::getValByName('company_start_time');
            $endTime = Utility::getValByName('company_end_time');
            $attendance = AttendanceEmployee::where('employee_id', '=', $request->employee_id)->where('date', '=', $request->date)->where('clock_out', '=', '00:00:00')->get()->toArray();
            if ($attendance) {
                return redirect()->route('attendanceemployee.index')->with('error', __('Employee Attendance Already Created.'));
            } else {
                $date = date("Y-m-d");

                $totalLateSeconds = strtotime($request->clock_in) - strtotime($date . $startTime);

                $hours = floor($totalLateSeconds / 3600);
                $mins = floor($totalLateSeconds / 60 % 60);
                $secs = floor($totalLateSeconds % 60);

                $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                //early Leaving
                $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($request->clock_out);
                $hours = floor($totalEarlyLeavingSeconds / 3600);
                $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                $secs = floor($totalEarlyLeavingSeconds % 60);
                $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                if (strtotime($request->clock_out) > strtotime($date . $endTime)) {
                    //Overtime
                    $totalOvertimeSeconds = strtotime($request->clock_out) - strtotime($date . $endTime);
                    $hours = floor($totalOvertimeSeconds / 3600);
                    $mins = floor($totalOvertimeSeconds / 60 % 60);
                    $secs = floor($totalOvertimeSeconds % 60);
                    $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                } else {
                    $overtime = '00:00:00';
                }

                $employeeAttendance = new AttendanceEmployee();
                $employeeAttendance->employee_id = $request->employee_id;
                $employeeAttendance->date = $request->date;
                $employeeAttendance->status = 'Present';
                $employeeAttendance->clock_in = $request->clock_in . ':00';
                $employeeAttendance->clock_out = $request->clock_out . ':00';
                $employeeAttendance->late = $late;
                $employeeAttendance->early_leaving = $earlyLeaving;
                $employeeAttendance->overtime = $overtime;
                $employeeAttendance->total_rest = '00:00:00';
                $employeeAttendance->created_by = \Auth::user()->creatorId();
                $employeeAttendance->save();

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully created.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('attendanceemployee.index');
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit attendance')) {
            $attendanceEmployee = AttendanceEmployee::where('id', $id)->first();
            $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            if($attendanceEmployee->total_break_duration != null)
            {
                $timeString = $attendanceEmployee->total_break_duration;
                list($hours, $minutes, $seconds) = explode(':', $timeString);
                $totalMinutes = ($hours * 60) + $minutes + ($seconds / 60);
                $attendanceEmployee->total_break_duration = round($totalMinutes, 2);
            }
            else
                $attendanceEmployee->total_break_duration = 0;

            return view('attendance.edit', compact('attendanceEmployee', 'employees'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {
        if ((\Auth::user()->type == 'company' || \Auth::user()->type == 'HR') && isset($request->employee_id)) {
            
            $employeeId = AttendanceEmployee::where('employee_id', $request->employee_id)->first();
            $check = AttendanceEmployee::where('id',$id)->where('employee_id', '=', $request->employee_id)->where('date', $request->date)->first();
            // dd($check->date);

            $startTime = Utility::getValByName('company_start_time');
            $endTime = Utility::getValByName('company_end_time');

            $clockIn = $request->clock_in;
            $clockOut = $request->clock_out;
            $work_from_home = $request->work_from_home;

            $startTime1 = Carbon::parse(Utility::getValByName('company_start_time'));
            $endTime1 = Carbon::parse(Utility::getValByName('company_end_time'));
            $defaultBreakTimeInMinutes = Utility::getValByName('break_time');

            if(!$request->total_break_duration)
            $request->total_break_duration=0;

            $punch_in = Carbon::parse($request->clock_in);
            $punch_out = Carbon::parse($request->clock_out);

            $totalScheduledMinutes = $endTime1->diffInMinutes($startTime1) - $defaultBreakTimeInMinutes;

            $totalWorkedMinutes = $punch_out->diffInMinutes($punch_in) - $request->total_break_duration;

            $earlyLeavingMinutes = 0;
            if ($punch_out < $endTime1) {
                $earlyLeavingMinutes = $endTime1->diffInMinutes($punch_out);
            }

            $overtimeMinutes = 0;
            if ($totalWorkedMinutes > $totalScheduledMinutes) {
                $overtimeMinutes = $totalWorkedMinutes - $totalScheduledMinutes;
            }

            $hours = floor($request->total_break_duration / 60); // 1 hour
            $minutes = floor($request->total_break_duration - ($hours * 60)); // 20 minutes
            $seconds = round(($request->total_break_duration - floor($request->total_break_duration)) * 60);

            $total_break_time = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

            $earlyLeavingTime = gmdate('H:i:s', $earlyLeavingMinutes * 60);
            $overtimeTime = gmdate('H:i:s', $overtimeMinutes * 60);



            if ($clockIn) {
                $status = "present";
            } else {
                $status = "leave";
            }

            $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

            $hours = floor($totalLateSeconds / 3600);
            $mins = floor($totalLateSeconds / 60 % 60);
            $secs = floor($totalLateSeconds % 60);
            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
            $hours = floor($totalEarlyLeavingSeconds / 3600);
            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            if (strtotime($clockOut) > strtotime($endTime)) {
                //Overtime
                $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
                $hours = floor($totalOvertimeSeconds / 3600);
                $mins = floor($totalOvertimeSeconds / 60 % 60);
                $secs = floor($totalOvertimeSeconds % 60);
                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $overtime = '00:00:00';
            }
            // dd($check->date == date('Y-m-d'));
            if (\Auth::user()->type == 'company' || $check->date == date('Y-m-d')) {
                $check->update([
                    'late' => $late,
                    'early_leaving' => ($earlyLeavingTime > 0) ? $earlyLeavingTime : '00:00:00',
                    'overtime' => $overtimeTime,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'total_break_duration' => $total_break_time,
                    'work_from_home' => $work_from_home
                ]);

                return redirect()->route('attendanceemployee.index')->with('success', __('Employee attendance successfully updated.'));
            } else {
                return redirect()->route('attendanceemployee.index')->with('error', __('you can only update current day attendance.'));
            }
        }

        //    dd($request->all());
        $employeeId = !empty(\Auth::user()->employee)?\Auth::user()->employee->id : 0;
        $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->first();
        //        dd($todayAttendance);
        //        if(!empty($todayAttendance) && $todayAttendance->clock_out == '00:00:00')
        //        if($todayAttendance->clock_out == '00:00:00')
        //        {

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        if (Auth::user()->type == 'Employee' || !isset($request->employee_id)) {

            $startTime = Carbon::parse(Utility::getValByName('company_start_time'));
            $endTime = Carbon::parse(Utility::getValByName('company_end_time'));
            $defaultBreakTimeInMinutes = Utility::getValByName('break_time');

            $attendance = AttendanceEmployee::find($id);
            $punch_in = Carbon::parse($attendance->clock_in);
            $punch_out = Carbon::parse(date("H:i:s"));
            if($attendance->total_break_duration){

                $break_taken = Carbon::parse($attendance->total_break_duration);
                $break_taken_minutes = $break_taken->hour * 60 + $break_taken->minute;
            }else
                $break_taken_minutes = 0;

            $totalScheduledMinutes = $endTime->diffInMinutes($startTime) - $defaultBreakTimeInMinutes;

            $totalWorkedMinutes = $punch_out->diffInMinutes($punch_in) - $break_taken_minutes;

            $earlyLeavingMinutes = 0;
            if ($punch_out < $endTime) {
                $earlyLeavingMinutes = $endTime->diffInMinutes($punch_out);
            }

            $overtimeMinutes = 0;
            if ($totalWorkedMinutes > $totalScheduledMinutes) {
                $overtimeMinutes = $totalWorkedMinutes - $totalScheduledMinutes;
            }

            // $workedTime = gmdate('H:i:s', $totalWorkedMinutes * 60);
            $earlyLeavingTime = gmdate('H:i:s', $earlyLeavingMinutes * 60);
            $overtimeTime = gmdate('H:i:s', $overtimeMinutes * 60);


            // $date = date("Y-m-d");
            // $time = date("H:i:s");
            //                dd($time);
            // //early Leaving
            // $totalEarlyLeavingSeconds = strtotime($date . $endTime) - time();
            // $hours = floor($totalEarlyLeavingSeconds / 3600);
            // $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            // $secs = floor($totalEarlyLeavingSeconds % 60);
            // $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            // if (time() > strtotime($date . $endTime)) {
            //     //Overtime
            //     $totalOvertimeSeconds = time() - strtotime($date . $endTime);
            //     $hours = floor($totalOvertimeSeconds / 3600);
            //     $mins = floor($totalOvertimeSeconds / 60 % 60);
            //     $secs = floor($totalOvertimeSeconds % 60);
            //     $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            // } else {
            //     $overtime = '00:00:00';
            // }

            //                $attendanceEmployee                = AttendanceEmployee::find($id);
            $attendanceEmployee['clock_out'] = $punch_out;
            $attendanceEmployee['early_leaving'] = $earlyLeavingTime;
            $attendanceEmployee['overtime'] = $overtimeTime;

            if (!empty($request->date)) {
                $attendanceEmployee['date'] = $request->date;
            }
            //                dd($attendanceEmployee);
            AttendanceEmployee::where('id', $id)->update($attendanceEmployee);
            //                $attendanceEmployee->save();

            return redirect()->route('hrm.dashboard')->with('success', __('Employee successfully clock Out.'));
        } else {
            $date = date("Y-m-d");
            $clockout_time = date("H:i:s");
            //late
            $totalLateSeconds = strtotime($clockout_time) - strtotime($date . $startTime);

            $hours = abs(floor($totalLateSeconds / 3600));
            $mins = abs(floor($totalLateSeconds / 60 % 60));
            $secs = abs(floor($totalLateSeconds % 60));

            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            //early Leaving
            $totalEarlyLeavingSeconds = strtotime($date . $endTime) - strtotime($clockout_time);
            $hours = floor($totalEarlyLeavingSeconds / 3600);
            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
            $secs = floor($totalEarlyLeavingSeconds % 60);
            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            if (strtotime($clockout_time) > strtotime($date . $endTime)) {
                //Overtime
                $totalOvertimeSeconds = strtotime($clockout_time) - strtotime($date . $endTime);
                $hours = floor($totalOvertimeSeconds / 3600);
                $mins = floor($totalOvertimeSeconds / 60 % 60);
                $secs = floor($totalOvertimeSeconds % 60);
                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
            } else {
                $overtime = '00:00:00';
            }

            $attendanceEmployee = AttendanceEmployee::find($id);
            // $attendanceEmployee->employee_id   = $employeeId;
            // $attendanceEmployee->date          = $request->date;
            // $attendanceEmployee->clock_in      = $request->clock_in;
            $attendanceEmployee->clock_out = $clockout_time;
            $attendanceEmployee->late = $late;
            $attendanceEmployee->early_leaving = $earlyLeaving;
            $attendanceEmployee->overtime = $overtime;
            $attendanceEmployee->total_rest = '00:00:00';

            $attendanceEmployee->save();

            return redirect()->back()->with('success', __('Employee attendance successfully updated.'));
        }
        //        }
        //        else
        //        {
        //            return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //        }
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete attendance')) {
            $attendance = AttendanceEmployee::where('id', $id)->first();

            $attendance->delete();

            return redirect()->route('attendanceemployee.index')->with('success', __('Attendance successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function attendance(Request $request)
    {
        $settings = Utility::settings();

        if ($settings['ip_restrict'] == 'on') {
            $userIp = request()->ip();
            $ip = IpRestrict::where('created_by', \Auth::user()->creatorId())->whereIn('ip', [$userIp])->first();
            if (!empty($ip)) {
                return redirect()->back()->with('error', __('This ip is not allowed to clock in & clock out.'));
            }
        }


        $employeeId = !empty(\Auth::user()->employee)?\Auth::user()->employee->id : 0;

        $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)->where('date', date('Y-m-d'))->orderBy('id', 'desc')->first();
        //        if(empty($todayAttendance))
        //        {

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        $attendance = AttendanceEmployee::orderBy('id', 'desc')->where('employee_id', '=', $employeeId)->where('clock_out', '=', '00:00:00')->first();

        if ($attendance != null) {
            $attendance = AttendanceEmployee::find($attendance->id);
            $attendance->clock_out = $endTime;
            $attendance->save();
        }

        $date = date("Y-m-d");
        $time = date("H:i:s");

        if (!empty($todayAttendance)) {
            $startTime = $todayAttendance->clock_out;
        }
        //late

        $totalLateSeconds = time() - strtotime($date . $startTime);

        $hours = abs(floor($totalLateSeconds / 3600));
        $mins = abs(floor($totalLateSeconds / 60 % 60));
        $secs = abs(floor($totalLateSeconds % 60));

        if(date("Y-m-d H:i:s") < date("Y-m-d H:i:s", strtotime($date . $startTime)))
        {
            $hours -= 1;
        }

        $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        $checkDb = AttendanceEmployee::where('employee_id', '=', \Auth::user()->id)->get()->toArray();

        if (empty($checkDb)) {
            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->work_from_home = $request->work_from_home;
            $employeeAttendance->created_by = \Auth::user()->id;

            $employeeAttendance->save();

            return redirect()->back()->with('success', __('Employee Successfully Clock In.'));
        }
        foreach ($checkDb as $check) {

            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->created_by = \Auth::user()->id;

            $employeeAttendance->save();

            return redirect()->back()->with('success', __('Employee Successfully Clock In.'));

        }
        //        }
        //        else
        //        {
        //            return redirect()->back()->with('error', __('Employee are not allow multiple time clock in & clock for every day.'));
        //        }
    }

    public function bulkAttendance(Request $request)
    {
        if (\Auth::user()->can('create attendance')) {

            $branch = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branch->prepend('Select Company', '');

            $department = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $department->prepend('Select Department', '');

            $employees = [];
            if (!empty($request->branch) && !empty($request->department)) {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('branch_id', $request->branch)->where('department_id', $request->department)->get();

            } else {
                $employees = Employee::where('created_by', \Auth::user()->creatorId())->where('branch_id', 1)->where('department_id', 1)->get();
            }

            return view('attendance.bulk', compact('employees', 'branch', 'department'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function bulkAttendanceData(Request $request)
    {

        if (\Auth::user()->can('create attendance')) {
            if (!empty($request->branch) && !empty($request->department)) {
                $startTime = Utility::getValByName('company_start_time');
                $endTime = Utility::getValByName('company_end_time');
                $date = $request->date;

                $employees = $request->employee_id;
                $atte = [];

                if (!empty($employees)) {
                    foreach ($employees as $employee) {
                        $present = 'present-' . $employee;
                        $in = 'in-' . $employee;
                        $out = 'out-' . $employee;
                        $atte[] = $present;
                        if ($request->$present == 'on') {

                            $in = date("H:i:s", strtotime($request->$in));
                            $out = date("H:i:s", strtotime($request->$out));

                            $totalLateSeconds = strtotime($in) - strtotime($startTime);

                            $hours = floor($totalLateSeconds / 3600);
                            $mins = floor($totalLateSeconds / 60 % 60);
                            $secs = floor($totalLateSeconds % 60);
                            $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            //early Leaving
                            $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($out);
                            $hours = floor($totalEarlyLeavingSeconds / 3600);
                            $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                            $secs = floor($totalEarlyLeavingSeconds % 60);
                            $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                            if (strtotime($out) > strtotime($endTime)) {
                                //Overtime
                                $totalOvertimeSeconds = strtotime($out) - strtotime($endTime);
                                $hours = floor($totalOvertimeSeconds / 3600);
                                $mins = floor($totalOvertimeSeconds / 60 % 60);
                                $secs = floor($totalOvertimeSeconds % 60);
                                $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                            } else {
                                $overtime = '00:00:00';
                            }
                            $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                            if (!empty($attendance)) {
                                $employeeAttendance = $attendance;
                            } else {
                                $employeeAttendance = new AttendanceEmployee();
                                $employeeAttendance->employee_id = $employee;
                                $employeeAttendance->created_by = \Auth::user()->creatorId();
                            }
                            $employeeAttendance->date = $request->date;
                            $employeeAttendance->status = 'Present';
                            $employeeAttendance->clock_in = $in;
                            $employeeAttendance->clock_out = $out;
                            $employeeAttendance->late = $late;
                            $employeeAttendance->early_leaving = ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00';
                            $employeeAttendance->overtime = $overtime;
                            $employeeAttendance->total_rest = '00:00:00';
                            $employeeAttendance->save();

                        } else {
                            $attendance = AttendanceEmployee::where('employee_id', '=', $employee)->where('date', '=', $request->date)->first();

                            if (!empty($attendance)) {
                                $employeeAttendance = $attendance;
                            } else {
                                $employeeAttendance = new AttendanceEmployee();
                                $employeeAttendance->employee_id = $employee;
                                $employeeAttendance->created_by = \Auth::user()->creatorId();
                            }

                            $employeeAttendance->status = 'Leave';
                            $employeeAttendance->date = $request->date;
                            $employeeAttendance->clock_in = '00:00:00';
                            $employeeAttendance->clock_out = '00:00:00';
                            $employeeAttendance->late = '00:00:00';
                            $employeeAttendance->early_leaving = '00:00:00';
                            $employeeAttendance->overtime = '00:00:00';
                            $employeeAttendance->total_rest = '00:00:00';
                            $employeeAttendance->save();
                        }
                    }
                } else {
                    return redirect()->back()->with('error', __('Employee not found.'));
                }

                return redirect()->back()->with('success', __('Employee attendance successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Branch & department field required.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    //for attendance employee report
    public function importFile()
    {
        return view('attendance.import');
    }

    public function import(Request $request)
    {
        $rules = [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $attendance = (new AttendanceImport())->toArray(request()->file('file'))[0];

        $email_data = [];
        foreach ($attendance as $key => $employee) {
            if ($key != 0) {
                echo "<pre>";
                if ($employee != null && Employee::where('email', $employee[0])->where('created_by', \Auth::user()->creatorId())->exists()) {
                    $email = $employee[0];
                } else {
                    $email_data[] = $employee[0];
                }
            }
        }
        $totalattendance = count($attendance) - 1;
        $errorArray = [];

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        if (!empty($attendanceData)) {
            $errorArray[] = $attendanceData;
        } else {
            foreach ($attendance as $key => $value) {
                if ($key != 0) {
                    $employeeData = Employee::where('email', $value[0])->where('created_by', \Auth::user()->creatorId())->first();
                    // $employeeId = 0;
                    if (!empty($employeeData)) {
                        $employeeId = $employeeData->id;

                        $clockIn = $value[2];
                        $clockOut = $value[3];
                        $break_hour = $value[4];
                        $is_wfh_data = $value[5];

                        if ($clockIn) {
                            $status = "present";
                        } else {
                            $status = "leave";
                        }

                        $totalLateSeconds = strtotime($clockIn) - strtotime($startTime);

                        $hours = floor($totalLateSeconds / 3600);
                        $mins = floor($totalLateSeconds / 60 % 60);
                        $secs = floor($totalLateSeconds % 60);
                        $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                        $totalEarlyLeavingSeconds = strtotime($endTime) - strtotime($clockOut);
                        $hours = floor($totalEarlyLeavingSeconds / 3600);
                        $mins = floor($totalEarlyLeavingSeconds / 60 % 60);
                        $secs = floor($totalEarlyLeavingSeconds % 60);
                        $earlyLeaving = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

                        if (strtotime($clockOut) > strtotime($endTime)) {
                            //Overtime
                            $totalOvertimeSeconds = strtotime($clockOut) - strtotime($endTime);
                            $hours = floor($totalOvertimeSeconds / 3600);
                            $mins = floor($totalOvertimeSeconds / 60 % 60);
                            $secs = floor($totalOvertimeSeconds % 60);
                            $overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
                        } else {
                            $overtime = '00:00:00';
                        }

                        $total_break_duration = date('H:i', mktime(0,$break_hour));
                        if($is_wfh_data == "Yes")
                        {
                            $is_wfh = 1;
                        } else {
                            $is_wfh = 0;
                        }

                        $check = AttendanceEmployee::where('employee_id', $employeeId)->where('date', $value[1])->first();
                        if ($check) {
                            $check->update([
                                'late' => $late,
                                'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                                'overtime' => $overtime,
                                'total_break_duration' => $total_break_duration,
                                'work_from_home' => $is_wfh,
                                'clock_in' => $value[2],
                                'clock_out' => $value[3],
                            ]);
                        } else {
                            $time_sheet = AttendanceEmployee::create([
                                'employee_id' => $employeeId,
                                'date' => $value[1],
                                'status' => $status,
                                'late' => $late,
                                'early_leaving' => ($earlyLeaving > 0) ? $earlyLeaving : '00:00:00',
                                'overtime' => $overtime,
                                'total_break_duration' => $total_break_duration,
                                'work_from_home' => $is_wfh,
                                'clock_in' => $value[2],
                                'clock_out' => $value[3],
                                'created_by' => \Auth::user()->id,
                            ]);
                        }
                    }
                } else {
                    $email_data = implode(' And ', $email_data);
                }
            }

            if (!empty($email_data)) {
                return redirect()->back()->with('status', 'This record is not import. ' . '</br>' . $email_data);
            } else {
                if (empty($errorArray)) {
                    $data['status'] = 'success';
                    $data['msg'] = __('Record successfully imported');
                } else {

                    $data['status'] = 'error';
                    $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalattendance . ' ' . 'record');

                    foreach ($errorArray as $errorData) {
                        $errorRecord[] = implode(',', $errorData->toArray());
                    }

                    \Session::put('errorArray', $errorRecord);
                }

                return redirect()->back()->with($data['status'], $data['msg']);
            }
        }
    }

    public function export(Request $request)
{
    // Get filtered attendance data
    $attendanceEmployee = $this->filterAttendance($request);

    // Pass the filtered data to the export class
    return Excel::download(new AttendanceExport($attendanceEmployee), 'Attendance_' . date('Y-m-d_H-i-s') . '.xlsx');
}

    private function filterAttendance($request)
    {
        if (\Auth::user()->type != 'employee') {
            $employee = Employee::select('id')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->branch)) {
                $employee->where('branch_id', $request->branch);
            }

            if (!empty($request->department)) {
                $employee->where('department_id', $request->department);
            }

            $employee = $employee->get()->pluck('id');
            $attendanceEmployee = AttendanceEmployee::whereIn('employee_id', $employee);

        } else {
            $emp = !empty(\Auth::user()->employee) ? \Auth::user()->employee->id : 0;
            $attendanceEmployee = AttendanceEmployee::where('employee_id', $emp);
        }

        if ($request->type == 'monthly' && !empty($request->month)) {
            $month = date('m', strtotime($request->month));
            $year = date('Y', strtotime($request->month));
            $start_date = date($year . '-' . $month . '-01');
            $end_date = date($year . '-' . $month . '-t');

            $attendanceEmployee->whereBetween('date', [$start_date, $end_date]);

        } elseif ($request->type == 'daily' && !empty($request->date)) {
            $attendanceEmployee->where('date', $request->date);

        } else {
            $month = date('m');
            $year = date('Y');
            $start_date = date($year . '-' . $month . '-01');
            $end_date = date($year . '-' . $month . '-t');

            $attendanceEmployee->whereBetween('date', [$start_date, $end_date]);
        }

        return $attendanceEmployee->get();
    }

    public function print(Request $request)
{
    $attendanceEmployee = $this->filterAttendance($request);
    $settings = Utility::getSettingById(\Auth::user()->creatorId());
    if (!$settings) {
        $settings = Utility::getSetting();
    }
    $companyLogo = $settings['company_logo'] ?? 'default-logo.png';

    return view('attendance.print', compact('attendanceEmployee','companyLogo'));
}


}
