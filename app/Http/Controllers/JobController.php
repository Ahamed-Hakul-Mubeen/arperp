<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CustomQuestion;
use App\Models\EmployeeHistory;
use App\Models\Job;
use App\Models\JobStage;
use App\Models\Utility;
use App\Models\JobApplication;
use App\Models\JobApplicationNote;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class JobController extends Controller
{

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage job')) {
            $jobs = Job::where('created_by', '=', \Auth::user()->creatorId());

            $data['total']     = Job::where('created_by', '=', \Auth::user()->creatorId())->count();
            $data['active']    = Job::where('status', 'active')->where('created_by', '=', \Auth::user()->creatorId())->count();
            $data['in_active'] = Job::where('status', 'in_active')->where('created_by', '=', \Auth::user()->creatorId())->count();

            $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->select('name', 'id');;
            if (!empty($request->company)) {
                $jobs->where('branch', '=', $request->company);
            }
            $jobs= $jobs->with('branches', 'createdBy')->get();
            if(auth()->user()->type == "Employee")
            {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
                EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed Job List", $ip);
            }
            return view('job.index', compact('jobs', 'data' ,'branches'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {

        $categories = JobCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('title', 'id');
        $categories->prepend('--', '');

        $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $branches->prepend('All', 0);

        $status = Job::$status;

        $customQuestion = CustomQuestion::where('created_by', \Auth::user()->creatorId())->get();

        return view('job.create', compact('categories', 'status', 'branches', 'customQuestion'));
    }

    public function store(Request $request)
    {

        if (\Auth::user()->can('create job')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'title' => 'required',
                    'branch' => 'required',
                    'category' => 'required',
                    'skill' => 'required',
                    'position' => 'required|integer',
                    'start_date' => 'required|date|before_or_equal:end_date',  // Added date validation
                    'end_date' => 'required|date|after_or_equal:start_date',    // Added date validation
                    'description' => 'required',
                    'requirement' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $job                  = new Job();
            $job->title           = $request->title;
            $job->branch          = $request->branch;
            $job->category        = $request->category;
            $job->skill           = $request->skill;
            $job->position        = $request->position;
            $job->status          = $request->status;
            $job->start_date      = $request->start_date;
            $job->end_date        = $request->end_date;
            $job->description     = $request->description;
            $job->requirement     = $request->requirement;
            $job->code            = uniqid();
            $job->applicant       = !empty($request->applicant) ? implode(',', $request->applicant) : '';
            $job->visibility      = !empty($request->visibility) ? implode(',', $request->visibility) : '';
            $job->custom_question = !empty($request->custom_question) ? implode(',', $request->custom_question) : '';
            $job->created_by      = \Auth::user()->creatorId();
            $job->save();

            return redirect()->route('job.index')->with('success', __('Job  successfully created.'));
        } else {
            return redirect()->route('job.index')->with('error', __('Permission denied.'));
        }
    }

    public function show(Job $job)
    {
        $status          = Job::$status;
        $job->applicant  = !empty($job->applicant) ? explode(',', $job->applicant) : '';
        $job->visibility = !empty($job->visibility) ? explode(',', $job->visibility) : '';
        $job->skill      = !empty($job->skill) ? explode(',', $job->skill) : '';
        if(auth()->user()->type == "Employee")
        {
            // dd($job);
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
            // EmployeeHistory::storeHistory(auth()->user()->id, "View", "Viewed ". $job->title ." Job in Detail", $ip);
        }
        return view('job.show', compact('status', 'job'));
    }

    public function edit(Job $job)
    {

        $categories = JobCategory::where('created_by', \Auth::user()->creatorId())->get()->pluck('title', 'id');
        $categories->prepend('--', '');

        $branches = Branch::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $branches->prepend('All', 0);

        $status = Job::$status;

        $job->applicant       = explode(',', $job->applicant);
        $job->visibility      = explode(',', $job->visibility);
        $job->custom_question = explode(',', $job->custom_question);

        $customQuestion = CustomQuestion::where('created_by', \Auth::user()->creatorId())->get();

        return view('job.edit', compact('categories', 'status', 'branches', 'job', 'customQuestion'));
    }

    public function update(Request $request, Job $job)
    {
        if (\Auth::user()->can('edit job')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'title' => 'required',
                    'branch' => 'required',
                    'category' => 'required',
                    'skill' => 'required',
                    'position' => 'required|integer',
                    'start_date' => 'required|date|before_or_equal:end_date',  // Added date validation
                    'end_date' => 'required|date|after_or_equal:start_date',    // Added date validation
                    'description' => 'required',
                    'requirement' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $job->title           = $request->title;
            $job->branch          = $request->branch;
            $job->category        = $request->category;
            $job->skill           = $request->skill;
            $job->position        = $request->position;
            $job->status          = $request->status;
            $job->start_date      = $request->start_date;
            $job->end_date        = $request->end_date;
            $job->description     = $request->description;
            $job->requirement     = $request->requirement;
            $job->applicant       = !empty($request->applicant) ? implode(',', $request->applicant) : '';
            $job->visibility      = !empty($request->visibility) ? implode(',', $request->visibility) : '';
            $job->custom_question = !empty($request->custom_question) ? implode(',', $request->custom_question) : '';
            $job->save();

            return redirect()->route('job.index')->with('success', __('Job  successfully updated.'));
        } else {
            return redirect()->route('job.index')->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Job $job)
    {
        $application = JobApplication::where('job', $job->id)->get()->pluck('id');
        JobApplicationNote::whereIn('application_id', $application)->delete();
        JobApplication::where('job', $job->id)->delete();
        $job->delete();

        return redirect()->route('job.index')->with('success', __('Job  successfully deleted.'));
    }

    public function career($id, $lang)
    {
        //        dd($lang);
        $jobs = Job::where('created_by', $id)->where('status', 'active')->with(['branches', 'createdBy'])->get();

        \Session::put('lang', $lang);

        App::setLocale($lang);

        $companySettings['title_text']      = \DB::table('settings')->where('created_by', $id)->where('name', 'title_text')->first();
        $companySettings['footer_text']     = \DB::table('settings')->where('created_by', $id)->where('name', 'footer_text')->first();
        $companySettings['company_favicon'] = \DB::table('settings')->where('created_by', $id)->where('name', 'company_favicon')->first();
        $companySettings['company_logo']    = \DB::table('settings')->where('created_by', $id)->where('name', 'company_logo')->first();
        $languages                          = Utility::languages();

        $currantLang = \Session::get('lang');
        if (empty($currantLang)) {
            $user        = User::find($id);
            $currantLang = !empty($user) && !empty($user->lang) ? $user->lang : 'en';
        }


        return view('job.career', compact('companySettings', 'jobs', 'languages', 'currantLang', 'id'));
    }

    public function jobRequirement($code, $lang)
    {
        $job = Job::where('code', $code)->first();
        if($job)
        {
            if ($job->status == 'in_active') {
                return redirect()->back()->with('error', __('Job Expired.'));
            }

            \Session::put('lang', $lang);

            \App::setLocale($lang);

            $companySettings['title_text']      = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'title_text')->first();
            $companySettings['footer_text']     = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'footer_text')->first();
            $companySettings['company_favicon'] = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'company_favicon')->first();
            $companySettings['company_logo']    = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'company_logo')->first();
            $languages                          = Utility::languages();

            $currantLang = \Session::get('lang');
            if (empty($currantLang)) {
                $currantLang = !empty($job->createdBy) ? $job->createdBy->lang : 'en';
            }


            return view('job.requirement', compact('companySettings', 'job', 'languages', 'currantLang'));
        }
        else
        {
            return redirect()->back()->with('error', __('Job Not Found.'));
        }
    }

    public function jobApply($code, $lang)
    {
        \Session::put('lang', $lang);

        \App::setLocale($lang);

        $job                                = Job::where('code', $code)->first();
        if($job)
        {
            if ($job->status == 'in_active') {
                return redirect()->route('career', array("id" => $job->created_by,'lang' => 'en'));
            }
            $companySettings['title_text']      = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'title_text')->first();
            $companySettings['footer_text']     = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'footer_text')->first();
            $companySettings['company_favicon'] = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'company_favicon')->first();
            $companySettings['company_logo']    = \DB::table('settings')->where('created_by', $job->created_by)->where('name', 'company_logo')->first();
            $customQuestionIds = explode(',', $job->custom_question);
            $questions = CustomQuestion::whereIn('id', $customQuestionIds)->where('created_by', $job->created_by)->get();
            $languages = Utility::languages();
            $currantLang = \Session::get('lang');
            if (empty($currantLang)) {
                $currantLang = !empty($job->createdBy) ? $job->createdBy->lang : 'en';
            }
            return view('job.apply', compact('companySettings', 'job', 'questions', 'languages', 'currantLang'));
        }
        else
        {
            return redirect()->back()->with('error', __('Job Expired.'));
        }
    }

    public function jobApplyData(Request $request, $code)
    {

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'email' => 'required|email',
                'phone' => 'required',
                //                               'profile' => 'mimes:jpeg,png,jpg,gif,svg|max:20480',
                //                               'resume' => 'mimes:jpeg,png,jpg,gif,svg,pdf,doc,zip|max:20480',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $job = Job::where('code', $code)->first();
        $creatorId = $job->created_by;
        $existingJobApplication = JobApplication::where('email', $request->email)->where('job', $job->id)->first();
        if ($existingJobApplication) {
            return redirect()->back()->with('error', __('Email already exists'));
        }

        if (!empty($request->profile)) {

            //storage limit
            $image_size = $request->file('profile')->getSize();
            $result = Utility::updateStorageLimit($creatorId, $image_size);
            if ($result == 1) {
                $filenameWithExt = $request->file('profile')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('profile')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;

                $dir        = 'uploads/job/profile';

                $image_path = $dir . $filenameWithExt;
                if (\File::exists($image_path)) {
                    \File::delete($image_path);
                }
                $url = '';
                $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
            } else {
                $fileNameToStore = '';
            }
        }


        if (!empty($request->resume)) {

            //storage limit
            $image_size = $request->file('resume')->getSize();
            $result = Utility::updateStorageLimit($creatorId, $image_size);


            if ($result == 1) {

                $filenameWithExt1 = $request->file('resume')->getClientOriginalName();
                $filename1        = pathinfo($filenameWithExt1, PATHINFO_FILENAME);
                $extension1       = $request->file('resume')->getClientOriginalExtension();
                $fileNameToStore1 = $filename1 . '_' . time() . '.' . $extension1;

                $dir        = 'uploads/job/resume';

                $image_path = $dir . $filenameWithExt1;
                if (\File::exists($image_path)) {
                    \File::delete($image_path);
                }
                $url = '';
                $path = Utility::upload_file($request, 'resume', $fileNameToStore1, $dir, []);
            } else {
                $fileNameToStore1 = '';
            }
        }


        $stage = JobStage::where('created_by', $job->created_by)->first();
        $jobApplication                  = new JobApplication();
        $jobApplication->job             = $job->id;
        $jobApplication->name            = $request->name;
        $jobApplication->email           = $request->email;
        $jobApplication->phone           = $request->phone;
        $jobApplication->profile         = !empty($request->profile) ? $fileNameToStore : '';
        $jobApplication->resume          = !empty($request->resume) ? $fileNameToStore1 : '';
        $jobApplication->cover_letter    = $request->cover_letter;
        $jobApplication->dob             = $request->dob;
        $jobApplication->gender          = $request->gender;
        $jobApplication->country         = $request->country;
        $jobApplication->state           = $request->state;
        $jobApplication->city            = $request->city;
        $jobApplication->custom_question = json_encode($request->question);
        $jobApplication->created_by      = $job->created_by;
        $jobApplication->stage           = $stage->id;
        $jobApplication->save();


        return redirect()->back()->with('success', __('Job application successfully send') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
    }
}
