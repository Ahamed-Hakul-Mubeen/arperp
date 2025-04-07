<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\ProjectUser;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class LeaveController extends Controller
{
    public function index(Request $request)
    {

        if(\Auth::user()->can('manage leave'))
        {
            if(\Auth::user()->type == 'Employee')
            {
                $user     = \Auth::user();
                $employee = Employee::where('user_id', '=', $user->id)->first();
                $project_id = ProjectUser::where('user_id', $user->id)->pluck('project_id')->toArray();

                $user_id_arr = ProjectUser::whereIn('project_id', $project_id)->pluck('user_id')->toArray();
                $user_id_arr[] = $user->id;
                $user_id_arr = array_unique($user_id_arr);
                $employee_id_arr = [];
                foreach($user_id_arr as $ua)
                {
                    $project_employee = Employee::where('user_id', '=', $ua)->first();
                    if($project_employee)
                    {
                        $employee_id_arr[] = $project_employee->id;
                    }
                }

                $leaves   = Leave::whereIn('employee_id', $employee_id_arr);
            }
            else
            {
                $leaves = Leave::where('created_by', '=', \Auth::user()->creatorId());
            }
            if (!empty($request->leavetype)) {
                $leaves->where('leave_type_id', '=', $request->leavetype);
            }
            if (!empty($request->startDate)) {
                $leaves->whereDate('start_date', '>=', $request->startDate);
            }
            if (!empty($request->endDate)) {
                $leaves->where('end_date', '<=', $request->endDate);
            }
            $leaves = $leaves->with(['leaveType','employees'])->get();
            $leavetypes      = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();

            if(auth()->user()->type == "Employee")
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Manage Leave", $ip);
            }

            return view('leave.index', compact('leaves','leavetypes'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create leave'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $employees = Employee::where('user_id', '=', \Auth::user()->id)->get()->pluck('name', 'id');
            }
            else
            {
                $employees = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            }
            $leavetypes      = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();
//            $leavetypes_days = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('leave.create', compact('employees', 'leavetypes'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('create leave'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'leave_type_id' => 'required',
                                   'start_date' => 'required',
                                   'end_date' => 'required',
                                   'leave_reason' => 'required',
                                   'remark' => \Auth::user()->type =='Employee'? 'nullable' : 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }


            $employee = Employee::where('user_id', '=', Auth::user()->id)->first();
            $leave_type = LeaveType::find($request->leave_type_id);
            $startDate = new \DateTime($request->start_date);
            $endDate = new \DateTime($request->end_date);
            $endDate->add(new \DateInterval('P1D'));
            $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
            if ($leave_type->days >= $total_leave_days)
            {
            $leave    = new Leave();
            if(\Auth::user()->type == "Employee")
            {
                $leave->employee_id = $employee->id;
            }
            else
            {
                $leave->employee_id = $request->employee_id;
            }

            $leave->leave_type_id    = $request->leave_type_id;
            $leave->applied_on       = date('Y-m-d');
            $leave->start_date       = $request->start_date;
            $leave->end_date         = $request->end_date;
            $leave->total_leave_days = $total_leave_days;
            $leave->leave_reason     = $request->leave_reason;
            $leave->remark           = $request->remark;
            $leave->status           = 'Pending';
            $leave->created_by       = \Auth::user()->creatorId();

            $leave->save();
            // dd($total_leave_days);
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            if ($total_leave_days > 1) {
                EmployeeHistory::storeHistory(
                    $leave->employee_id,
                    "Leave Requested",
                    "Leave requested from " . date('d/m/Y', strtotime($request->start_date)) . " to " . date('d/m/Y', strtotime($request->end_date)),
                    $ip
                );
            } else {
                EmployeeHistory::storeHistory(
                    $leave->employee_id,
                    "Leave Requested",
                    "Leave requested for " . date('d/m/Y', strtotime($request->start_date)),
                    $ip
                );
            }


                return redirect()->route('leave.index')->with('success', __('Leave successfully created.'));
            } else {
                return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show(Leave $leave)
    {
        return redirect()->route('leave.index');
    }

    public function edit(Leave $leave)
    {
        if(\Auth::user()->can('edit leave'))
        {
            if($leave->created_by == \Auth::user()->creatorId())
            {
                $employees  = Employee::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
                $leavetypes = LeaveType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('title', 'id');

                return view('leave.edit', compact('leave', 'employees', 'leavetypes'));
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

    public function update(Request $request, $leave)
    {

        $leave = Leave::find($leave);
        if(\Auth::user()->can('edit leave'))
        {
            if($leave->created_by == Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'leave_type_id' => 'required',
                                       'start_date' => 'required',
                                       'end_date' => 'required',
                                       'leave_reason' => 'required',
                                       'remark' => \Auth::user()->type =='Employee'? 'nullable' : 'required',
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $leave_type = LeaveType::find($request->leave_type_id);

                $startDate = new \DateTime($request->start_date);
                $endDate = new \DateTime($request->end_date);
                $endDate->add(new \DateInterval('P1D'));
                $total_leave_days = !empty($startDate->diff($endDate)) ? $startDate->diff($endDate)->days : 0;
                if ($leave_type->days >= $total_leave_days)
                {

                    $leave->employee_id      = $request->employee_id;
                    $leave->leave_type_id    = $request->leave_type_id;
                    $leave->start_date       = $request->start_date;
                    $leave->end_date         = $request->end_date;
                    $leave->total_leave_days = $total_leave_days;
                    $leave->leave_reason     = $request->leave_reason;
                    $leave->remark           = $request->remark;

                    $leave->save();

                    return redirect()->route('leave.index')->with('success', __('Leave successfully updated.'));
                }
                else
                {
                    return redirect()->back()->with('error', __('Leave type ' . $leave_type->name . ' is provide maximum ' . $leave_type->days . "  days please make sure your selected days is under " . $leave_type->days . ' days.'));
                }
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

    public function destroy(Leave $leave)
    {
        if(\Auth::user()->can('delete leave'))
        {
            if($leave->created_by == \Auth::user()->creatorId())
            {
                $leave->delete();

                return redirect()->route('leave.index')->with('success', __('Leave successfully deleted.'));
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

    public function action($id)
    {
        $leave     = Leave::find($id);
        $employee  = Employee::find($leave->employee_id);
        $leavetype = LeaveType::find($leave->leave_type_id);
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Leave Details", $ip);
        }
        return view('leave.action', compact('employee', 'leavetype', 'leave'));
    }

    public function changeaction(Request $request)
    {
        $leave = Leave::find($request->leave_id);

        if ($request->approval_type == 'PM') {
            $leave->pm_approval = $request->status;
        } elseif ($request->approval_type == 'HR') {
            $leave->hr_approval = $request->status;
        }


        if ($leave->pm_approval == 'Approved' && $leave->hr_approval == 'Approved') {
            $leave->status = 'Approved';
        }


        if ($leave->pm_approval == 'Rejected' || $leave->hr_approval == 'Rejected') {
            $leave->status = 'Rejected';
        }


        if ($request->approval_type == 'Final') {
            if ($request->status == 'Approved') {
                $leave->pm_approval = 'Approved';
                $leave->hr_approval = 'Approved';
                $leave->status = 'Approved';
            } elseif ($request->status == 'Rejected') {
                $leave->pm_approval = 'Rejected';
                $leave->hr_approval = 'Rejected';
                $leave->status = 'Rejected';
            }
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            if ($leave->total_leave_days > 1) {
                EmployeeHistory::storeHistory(
                    $leave->employee_id,
                    'Leave request '.$request->status,
                    "Leave request " . ucfirst($request->status) . " for " . date('d/m/Y', strtotime($leave->start_date)) . " to " . date('d/m/Y', strtotime($leave->end_date)),
                    $ip
                );
            } else {
                EmployeeHistory::storeHistory(
                    $leave->employee_id,
                    'Leave request '.$request->status,
                    "Leave request " . ucfirst($request->status) . " for " . date('d/m/Y', strtotime($leave->start_date)),
                    $ip
                );
            }
        }

        $leave->save();


       //Send Email
        $setings = Utility::settings();
        if(!empty($employee->id))
        {
            if($setings['leave_status'] == 1)
            {

                $employee     = Employee::where('id', $leave->employee_id)->where('created_by', '=', \Auth::user()->creatorId())->first();
                $leave->name  = !empty($employee->name) ? $employee->name : '';
                $leave->email = !empty($employee->email) ? $employee->email : '';
//            dd($leave);

                $actionArr = [

                    'leave_name'=> !empty($employee->name) ? $employee->name : '',
                    'leave_status' => $leave->status,
                    'leave_reason' =>  $leave->leave_reason,
                    'leave_start_date' => $leave->start_date,
                    'leave_end_date' => $leave->end_date,
                    'total_leave_days' => $leave->total_leave_days,

                ];
//            dd($actionArr);
                $resp = Utility::sendEmailTemplate('leave_action_sent', [$employee->id => $employee->email], $actionArr);


                return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.') .(($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }

        }

        return redirect()->route('leave.index')->with('success', __('Leave status successfully updated.'));
    }


    public function jsoncount(Request $request)
    {

        // $leave_counts = LeaveType::select(\DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave, leave_types.title, leave_types.days,leave_types.id'))
        //                          ->leftjoin('leaves', function ($join) use ($request){
        //     $join->on('leaves.leave_type_id', '=', 'leave_types.id');
        //     $join->where('leaves.employee_id', '=', $request->employee_id);
        // }
        // )->groupBy('leaves.leave_type_id')->get();

        $leave_counts=[];
        $leave_types = LeaveType::where('created_by',\Auth::user()->creatorId())->get();
        foreach ($leave_types as  $type) {
            $counts=Leave::select(\DB::raw('COALESCE(SUM(leaves.total_leave_days),0) AS total_leave'))->where('leave_type_id',$type->id)->groupBy('leaves.leave_type_id')->where('employee_id',$request->employee_id)->first();

            $leave_count['total_leave']=!empty($counts)?$counts['total_leave']:0;
            $leave_count['title']=$type->title;
            $leave_count['days']=$type->days;
            $leave_count['id']=$type->id;
            $leave_counts[]=$leave_count;
        }

        return $leave_counts;

    }
}
