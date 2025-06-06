<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Exports\EmployeeExport;
use App\Imports\EmployeesImport;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Document;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\EmployeeDocument;
use App\Models\ExperienceCertificate;
use App\Models\JoiningLetter;
use App\Models\NOC;
use App\Models\Plan;
use App\Models\Termination;
use App\Models\User;
use App\Models\Utility;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

//use Faker\Provider\File;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if(\Auth::user()->can('manage employee'))
        {
            if(Auth::user()->type == 'Employee')
            {
                $employees = Employee::where('user_id', '=', Auth::user()->id);
            }
            else
            {
                $employees = Employee::where('created_by', \Auth::user()->creatorId());
            }
            if (!empty($request->department)) {
                $employees->where('department_id', '=', $request->department);
            }
            if (!empty($request->designation)) {
                $employees->where('designation_id', '=', $request->designation);
            }
            if (!empty($request->branch)) {
                $employees->where('branch_id', '=', $request->branch);
            }
          $employees = $employees->with(['designation','branch','department','user'])->get();


            $branches         = Branch::where('created_by', \Auth::user()->creatorId())->get()->select('name', 'id');
            $departments      = Department::where('created_by', \Auth::user()->creatorId())->get()->select('name', 'id');
            $designations     = Designation::where('created_by', \Auth::user()->creatorId())->get()->select('name', 'id');
            return view('employee.index', compact('employees','branches','departments','designations'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create employee'))
        {
            $company_settings = Utility::settings();
            $documents        = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches         = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments      = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations     = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employees        = User::where('created_by', \Auth::user()->creatorId())->get();
            $employeesId      = \Auth::user()->employeeIdFormat($this->employeeNumber());

            return view('employee.create', compact('employees', 'employeesId', 'departments', 'designations', 'documents', 'branches', 'company_settings'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create employee'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required',
                                   'dob' => 'required',
                                   'phone' => 'required',
                                   'address' => 'required',
                                   'email' => 'required|unique:users',
                                   'password' => 'required',
                                   'branch_id' => 'required',
                                   'department_id' => 'required',
                                   'designation_id' => 'required',
                                   'document.*' => 'mimes:jpeg,png,jpg,gif,svg,pdf,docx,doc|max:20480',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->withInput()->with('error', $messages->first());
            }

            $objUser        = User::find(\Auth::user()->creatorId());
            $total_employee = $objUser->countEmployees();
            $plan           = Plan::find($objUser->plan);

            if($total_employee < $plan->max_users || $plan->max_users == -1)
            {
                $is_wfh = 0;
                if (!empty($request->is_wfh) && $request->is_wfh == 'on') {
                    $is_wfh = 1;
                }
                $request['is_wfh'] = $is_wfh;
                $user = User::create(
                    [
                        'name' => $request['name'],
                        'email' => $request['email'],
                        // 'gender'=>$request['gender'],
                        'password' => Hash::make($request['password']),
                        'type' => 'Employee',
                        'lang' => 'en',
                        'is_wfh' => $is_wfh,
                        'created_by' => \Auth::user()->creatorId(),
                    ]
                );
                $user->save();
                $employee_role = Role::where("name", "Employee")->where("created_by", \Auth::user()->creatorId())->first();
                if($employee_role){
                    $user->assignRole($employee_role->id);
                }else{
                    $user->assignRole('Employee');
                }
            }
            else
            {
                return redirect()->back()->with('error', __('Your employee limit is over, Please upgrade plan.'));
            }


            if(!empty($request->document) && !is_null($request->document))
            {
                $document_implode = implode(',', array_keys($request->document));
            }
            else
            {
                $document_implode = null;
            }


            $employee = Employee::create(
                [
                    'user_id' => $user->id,
                    'name' => $request['name'],
                    'dob' => $request['dob'],
                    'gender' => $request['gender'],
                    'phone' => $request['phone'],
                    'address' => $request['address'],
                    'email' => $request['email'],
                    'password' => Hash::make($request['password']),
                    'employee_id' => $this->employeeNumber(),
                    'branch_id' => $request['branch_id'],
                    'department_id' => $request['department_id'],
                    'designation_id' => $request['designation_id'],
                    'company_doj' => $request['company_doj'],
                    'documents' => $document_implode,
                    'account_holder_name' => $request['account_holder_name'],
                    'account_number' => $request['account_number'],
                    'bank_name' => $request['bank_name'],
                    'bank_identifier_code' => $request['bank_identifier_code'],
                    'branch_location' => $request['branch_location'],
                    'tax_payer_id' => $request['tax_payer_id'],
                    'created_by' => \Auth::user()->creatorId(),
                ]
            );

            if($request->hasFile('document'))
            {
                foreach($request->document as $key => $document)
                {

                    $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('document')[$key]->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $dir             = storage_path('uploads/document/');
                    $image_path      = $dir . $filenameWithExt;

                    if(File::exists($image_path))
                    {
                        File::delete($image_path);
                    }

                    if(!file_exists($dir))
                    {
                        mkdir($dir, 0777, true);
                    }
                    $path              = $request->file('document')[$key]->storeAs('uploads/document/', $fileNameToStore);
                    $employee_document = EmployeeDocument::create(
                        [
                            'employee_id' => $employee['employee_id'],
                            'document_id' => $key,
                            'document_value' => $fileNameToStore,
                            'created_by' => \Auth::user()->creatorId(),
                        ]
                    );
                    $employee_document->save();

                }

            }

            $setings = Utility::settings();

            if($setings['new_user'] == 1)
            {
                $userArr = [
                    'email' => $user->email,
                    'password' => $request->password,
                ];

                $resp = Utility::sendEmailTemplate('new_user', [$user->id => $user->email], $userArr);
                return redirect()->route('employee.index')->with('success', __('Employee successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }

            return redirect()->route('employee.index')->with('success', __('Employee  successfully created.'));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        if(\Auth::user()->can('edit employee'))
        {
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $branches->prepend('Select Company','');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($id);
//            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);
            $employeesId  = \Auth::user()->employeeIdFormat(!empty($employee) ? $employee->employee_id : '');

            $departmentData  = Department::where('created_by', \Auth::user()->creatorId())->where('branch_id',$employee->branch_id)->get()->pluck('name', 'id');


            return view('employee.edit', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents','departmentData'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $id)
    {

        if(\Auth::user()->can('edit employee'))
        {
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required',
                                   'dob' => 'required',
                                   'gender' => 'required',
                                   'phone' => 'required|numeric',
                                   'address' => 'required',
                                   'document.*' => 'mimes:jpeg,png,jpg,gif,svg,pdf,docx,doc|max:20480',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $employee = Employee::findOrFail($id);

            if($request->document)
            {
                foreach($request->document as $key => $document)
                {
                    if(!empty($document))
                    {
                        $filenameWithExt = $request->file('document')[$key]->getClientOriginalName();
                        $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension       = $request->file('document')[$key]->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
//                        $dir        = storage_path('uploads/document/');
                        $dir             = 'uploads/document/';

                        $image_path = $dir . $filenameWithExt;

                        if(File::exists($image_path))
                        {
                            File::delete($image_path);
                        }
//                        if(!file_exists($dir))
//                        {
//                            mkdir($dir, 0777, true);
//                        }
//                        $path = $request->file('document')[$key]->storeAs('uploads/document/', $fileNameToStore);

                        $path = \Utility::upload_coustom_file($request,'document',$fileNameToStore,$dir,$key,[]);


                        if($path['flag'] == 1){
                            $url = $path['url'];
                        }else{
                            return redirect()->back()->with('error', __($path['msg']));
                        }


                        $employee_document = EmployeeDocument::where('employee_id', $employee->employee_id)->where('document_id', $key)->first();

                        if(!empty($employee_document))
                        {
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        }
                        else
                        {
                            $employee_document                 = new EmployeeDocument();
                            $employee_document->employee_id    = $employee->employee_id;
                            $employee_document->document_id    = $key;
                            $employee_document->document_value = $fileNameToStore;
                            $employee_document->save();
                        }

                    }
                }
            }
            $employee = Employee::findOrFail($id);
            $input    = $request->all();
            $employee->fill($input)->save();
            $employee = Employee::find($id);
            $user = User::where('id',$employee->user_id)->first();
            if(!empty($user)){
                $is_wfh = 0;
                if (!empty($request->is_wfh) && $request->is_wfh == 'on') {
                    $is_wfh = 1;
                }
                $user->name = $employee->name;
                $user->email = $employee->email;
                $user->is_wfh = $is_wfh;
                
                $user->save();
            }
            if($request->salary)
            {
                return redirect()->route('setsalary.index')->with('success', 'Employee successfully updated.');
            }

            if(\Auth::user()->type != 'employee')
            {
                return redirect()->route('employee.index')->with('success', 'Employee successfully updated.');
            }
            else
            {
                return redirect()->route('employee.show', \Illuminate\Support\Facades\Crypt::encrypt($employee->id))->with('success', 'Employee successfully updated.');
            }

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($id)
    {

        if(Auth::user()->can('delete employee'))
        {
            $employee      = Employee::findOrFail($id);
            $user          = User::where('id', '=', $employee->user_id)->first();
            $emp_documents = EmployeeDocument::where('employee_id', $employee->employee_id)->get();
            $employee->delete();
            $user->delete();
            $dir = storage_path('uploads/document/');
            foreach($emp_documents as $emp_document)
            {
                $emp_document->delete();
                if(!empty($emp_document->document_value))
                {
                    unlink($dir . $emp_document->document_value);
                }

            }

            return redirect()->route('employee.index')->with('success', 'Employee successfully deleted.');
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function show(Request $request, $id)
    {

        if(\Auth::user()->can('view employee'))
        {
            try {
                $empId       = Crypt::decrypt($id);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Employee Not Found.'));
            }

            $empId        = Crypt::decrypt($id);
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $employee     = Employee::where('id',$empId)->first();

            $employeesId  = \Auth::user()->employeeIdFormat(!empty($employee) ? $employee->employee_id : '');
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            if(auth()->user()->type == "Employee")
            {
                // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Employee Details", $ip);
            }

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function json(Request $request)
    {
        $designations = Designation::where('department_id', $request->department_id)->get()->pluck('name', 'id')->toArray();

        return response()->json($designations);
    }

    function employeeNumber()
    {
        $latest = Employee::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if(!$latest)
        {
            return 1;
        }

        return $latest->employee_id + 1;
    }

    public function profile(Request $request)
    {
        if(\Auth::user()->can('manage employee profile'))
        {
            $employees = Employee::where('created_by', \Auth::user()->creatorId());
            if(!empty($request->branch))
            {
                $employees->where('branch_id', $request->branch);
            }
            if(!empty($request->department))
            {
                $employees->where('department_id', $request->department);
            }
            if(!empty($request->designation))
            {
                $employees->where('designation_id', $request->designation);
            }
            $employees = $employees->get();

            $brances = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $brances->prepend('All', '');

            $departments = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments->prepend('All', '');

            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations->prepend('All', '');

            return view('employee.profile', compact('employees', 'departments', 'designations', 'brances'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function profileShow($id)
    {
        if(\Auth::user()->can('show employee profile'))
        {
            $empId        = Crypt::decrypt($id);
            $documents    = Document::where('created_by', \Auth::user()->creatorId())->get();
            $branches     = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $departments  = Department::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $designations = Designation::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $employee     = Employee::find($empId);
            $employeesId  = \Auth::user()->employeeIdFormat($employee->employee_id);

            return view('employee.show', compact('employee', 'employeesId', 'branches', 'departments', 'designations', 'documents'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function lastLogin()
    {
        $users = User::where('created_by', \Auth::user()->creatorId())->get();

        return view('employee.lastLogin', compact('users'));
    }

    public function employeeJson(Request $request)
    {
        $employees = Employee::where('branch_id', $request->branch)->get()->pluck('name', 'id')->toArray();

        return response()->json($employees);
    }

    public function getdepartment(Request $request)
    {

        if($request->branch_id == 0)
        {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id')->toArray();
        }
        else
        {
            $departments = Department::where('created_by', '=', \Auth::user()->creatorId())->where('branch_id', $request->branch_id)->get()->pluck('name', 'id')->toArray();
        }

        return response()->json($departments);
    }

    public function joiningletterPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter=JoiningLetter::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);
        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' =>!empty($employees->address)?$employees->address:'' ,
            'designation' => !empty($employees->designation->name)?$employees->designation->name:'',
            'start_date' => !empty($employees->company_doj)?$employees->company_doj:'',
            'branch' => !empty($employees->Branch->name)?$employees->Branch->name:'',
            'start_time' => !empty($settings['company_start_time'])?$settings['company_start_time']:'',
            'end_time' => !empty($settings['company_end_time'])?$settings['company_end_time']:'',
            'total_hours' => $result,
        ];
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded Joining Letter as PDF", $ip);
        }

        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterpdf', compact('joiningletter','employees'));

    }
    public function joiningletterDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $joiningletter=JoiningLetter::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);



        $obj = [
            'date' =>  \Auth::user()->dateFormat($date) ,

            'app_name' => env('APP_NAME'),
            'employee_name' => $employees->name,
            'address' =>!empty($employees->address)?$employees->address:'' ,
            'designation' => !empty($employees->designation->name)?$employees->designation->name:'',
            'start_date' => !empty($employees->company_doj)?$employees->company_doj:'',
            'branch' => !empty($employees->Branch->name)?$employees->Branch->name:'',
            'start_time' => !empty($settings['company_start_time'])?$settings['company_start_time']:'',
            'end_time' => !empty($settings['company_end_time'])?$settings['company_end_time']:'',
            'total_hours' => $result,
            //

        ];
        // dd($obj);
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded Joining Letter as DOC", $ip);
        }

        $joiningletter->content = JoiningLetter::replaceVariable($joiningletter->content, $obj);
        return view('employee.template.joiningletterdocx', compact('joiningletter','employees'));

    }
    public function ExpCertificatePdf($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id',$id)->first();
        $experience_certificate=ExperienceCertificate::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        // dd($employees->salaryType->name);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  =date_diff($date1,$date2);
        $duration = $diff->format("%a days");

        if(!empty($termination->termination_date)){

            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll'=>!empty($employees->salaryType->name)?$employees->salaryType->name:'',
                'duration'=> $duration,
                'designation' => !empty($employees->designation->name)?$employees->designation->name:'',

            ];
        }else{
            return redirect()->back()->with('error', __('Termination date is required.'));
        }
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded Experience Certificate as PDF", $ip);
        }

        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatepdf', compact('experience_certificate','employees'));

    }
    public function ExpCertificateDoc($id)
    {
        $currantLang = \Cookie::get('LANGUAGE');
        if (!isset($currantLang)) {
            $currantLang = 'en';
        }
        $termination = Termination::where('employee_id',$id)->first();
        $experience_certificate=ExperienceCertificate::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);
        $date1 = date_create($employees->company_doj);
        $date2 = date_create($employees->termination_date);
        $diff  =date_diff($date1,$date2);
        $duration = $diff->format("%a days");
        if(!empty($termination->termination_date)){
            $obj = [
                'date' =>  \Auth::user()->dateFormat($date),
                'app_name' => env('APP_NAME'),
                'employee_name' => $employees->name,
                'payroll'=>!empty($employees->salaryType->name)?$employees->salaryType->name:'',
                'duration'=> $duration,
                'designation' => !empty($employees->designation->name)?$employees->designation->name:'',

            ];
        }else{
            return redirect()->back()->with('error', __('Termination date is required.'));
        }
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded Experience Certificate as DOC", $ip);
        }

        $experience_certificate->content = ExperienceCertificate::replaceVariable($experience_certificate->content, $obj);
        return view('employee.template.ExpCertificatedocx', compact('experience_certificate','employees'));

    }
    public function NocPdf($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate=NOC::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => !empty($employees)?$employees->name:'',
            'designation' => !empty($employees->designation->name)?$employees->designation->name:'',
            'app_name' => env('APP_NAME'),
        ];

        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded NOC Certificate as PDF", $ip);
        }

        $noc_certificate->content=NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocpdf', compact('noc_certificate','employees'));

    }
    public function NocDoc($id)
    {
        $users = \Auth::user();

        $currantLang = $users->currentLanguage();
        $noc_certificate=NOC::where(['lang' =>   $currantLang,'created_by' => \Auth::user()->creatorId()])->first();
        $date=date('Y-m-d');
        $employees = Employee::find($id);
        $settings = Utility::settings();
        $secs = strtotime($settings['company_start_time'])-strtotime("00:00");
        $result = date("H:i",strtotime($settings['company_end_time'])-$secs);


        $obj = [
            'date' =>  \Auth::user()->dateFormat($date),
            'employee_name' => $employees->name,
            'designation' => !empty($employees->designation->name)?$employees->designation->name:'',
            'app_name' => env('APP_NAME'),
        ];
        if(auth()->user()->type == "Employee")
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "Download", "Downloaded NOC Certificate as DOC", $ip);
        }

        $noc_certificate->content=NOC::replaceVariable($noc_certificate->content, $obj);
        return view('employee.template.Nocdocx', compact('noc_certificate','employees'));

    }

    //Export
    public function export()
    {
        $name = 'employee_' . date('Y-m-d i:h:s');
        $data = Excel::download(new EmployeeExport(), $name . '.xlsx'); ob_end_clean();

        return $data;
    }

    //import
    public function importFile()
    {
        return view('employee.import');
    }

    public function import(Request $request)
    {

        $rules = [
            'file' => 'required|mimes:csv,txt',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $employees = (new EmployeesImport())->toArray(request()->file('file'))[0];
        $totalCustomer = count($employees) - 1;
        $errorArray = [];

        for ($i = 1; $i <= count($employees) - 1; $i++) {

            $employee = $employees[$i];
            if (count($employee) >= 18) {

                if ($employee[5] == null) {
                    return redirect()->back()->with('error', __('Email Filed is Required'));
                }
                $employeeByEmail = Employee::where('email', $employee[5])->first();
                $userByEmail = User::where('email', $employee[5])->first();
                // dd($userByEmail);

                if (!empty($employeeByEmail) && !empty($userByEmail)) {

                    $employeeData = $employeeByEmail;
                } else {

                    $user = new User();
                    $user->name = $employee[0];
                    $user->email = $employee[5];
                    $user->password = Hash::make($employee[6]);
                    $user->type = 'employee';
                    $user->lang = 'en';
                    $user->created_by = \Auth::user()->creatorId();
                    $user->save();
                    $user->assignRole('Employee');
                    $employeeData = new Employee();
                    $employeeData->employee_id = $this->employeeNumber();
                    $employeeData->user_id = $user->id;
                }

                $employeeData->name = $employee[0];
                $employeeData->dob = $employee[1];
                $employeeData->gender = $employee[2];
                $employeeData->phone = $employee[3];
                $employeeData->address = $employee[4];
                $employeeData->email = $employee[5];
                $employeeData->password = Hash::make($employee[6]);
                $employeeData->employee_id = $this->employeeNumber();
                $employeeData->branch_id = $employee[8];
                $employeeData->department_id = $employee[9];
                $employeeData->designation_id = $employee[10];
                $employeeData->company_doj = $employee[11];
                $employeeData->account_holder_name = $employee[12];
                $employeeData->account_number = $employee[13];
                $employeeData->bank_name = $employee[14];
                $employeeData->bank_identifier_code = $employee[15];
                $employeeData->branch_location = $employee[16];
                $employeeData->tax_payer_id = $employee[17];
                $employeeData->created_by = \Auth::user()->creatorId();

                if (empty($employeeData)) {

                    $errorArray[] = $employeeData;
                } else {

                    $employeeData->save();
                }


            $errorRecord = [];

            if (empty($errorArray)) {
                $data['status'] = 'success';
                $data['msg'] = __('Record successfully imported');
            } else {
                $data['status'] = 'error';
                $data['msg'] = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');

                foreach ($errorArray as $errorData) {

                    $errorRecord[] = implode(',', $errorData);
                }

                \Session::put('errorArray', $errorRecord);
            }

            return redirect()->back()->with($data['status'], $data['msg']);
        }
        else
        {
            return redirect()->back()->with('error',__('Something went wrong'));
        }
        }

    }




}
