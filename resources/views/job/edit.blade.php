@extends('layouts.admin')
@section('page-title')
    {{__('Edit Job')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('job.index')}}">{{__('Job')}}</a></li>
    <li class="breadcrumb-item">{{__('Job Edit')}}</li>
@endsection
@push('css-page')
    <link rel="stylesheet" href="{{asset('css/summernote/summernote-bs4.css')}}">
    <link href="{{asset('css/bootstrap-tagsinput.css')}}" rel="stylesheet"/>

@endpush
@push('script-page')

    <script src="{{asset('js/bootstrap-tagsinput.min.js')}}"></script>

    <script>
        var e = $('[data-toggle="tags"]');
        e.length && e.each(function () {
            $(this).tagsinput({tagClass: "badge badge-primary"})
        });
        $(document).ready(function() {
    // Clear previous errors on input focus
    $('#start_date, #end_date').on('focus', function() {
        $('#start_date_error').text(''); // Clear start date error
        $('#end_date_error').text('');   // Clear end date error
    });

    $('#end_date').on('change', function() {
        var startDate = new Date($('#start_date').val());
        var endDate = new Date($(this).val());

        // Check if start date and end date are set
        if (!startDate || !endDate) {
            return; // If either date is not selected, don't show an error
        }

        // Validate: End Date cannot be earlier than Start Date
        if (endDate < startDate) {
            $('#end_date_error').text("End Date cannot be earlier than Start Date.");
        } else {
            $('#end_date_error').text(''); // Clear the error if the end date is valid
        }
    });

    $('#start_date').on('change', function() {
        // When the start date changes, revalidate the end date in case it's now valid
        var startDate = new Date($(this).val());
        var endDate = new Date($('#end_date').val());

        if (endDate >= startDate) {
            $('#end_date_error').text(''); // Clear error if end date is valid
        }
    });
});
    </script>
    <script src="{{asset('css/summernote/summernote-bs4.js')}}"></script>
@endpush
@section('action-btn')
    <div class="float-end">
        {{-- start for ai module--}}
        @php
            $plan= \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if($plan->chatgpt == 1)
            <a href="#" data-size="lg" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['job']) }}"
               data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"> </i> <span>{{__('Generate with AI')}}</span>
            </a>
        @endif
        {{-- end for ai module--}}
    </div>
@endsection
@section('content')

    {{Form::model($job,array('route' => array('job.update', $job->id), 'method' => 'PUT')) }}
    <div class="mt-3 row">
        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-body job-create ">
                    <div class="row">
                        <div class="form-group col-md-12">
                            {!! Form::label('title', __('Job Title'),['class'=>'form-label']) !!}
                            {!! Form::text('title', null, ['class' => 'form-control','required' => 'required']) !!}
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('branch', __('Company'),['class'=>'form-label']) !!}
                            {{ Form::select('branch', $branches,null, array('class' => 'form-control select','required'=>'required')) }}
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('category', __('Job Category'),['class'=>'form-label']) !!}
                            {{ Form::select('category', $categories,null, array('class' => 'form-control select','required'=>'required')) }}
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('position', __('Positions'),['class'=>'form-label']) !!}
                            {!! Form::text('position', null, ['class' => 'form-control','required' => 'required']) !!}
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('status', __('Status'),['class'=>'form-label']) !!}
                            {{ Form::select('status', $status,null, array('class' => 'form-control select','required'=>'required')) }}
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('start_date', __('Start Date'),['class'=>'form-label']) !!}
                            {!! Form::date('start_date', old('start_date'), ['class' => 'form-control', 'id' => 'start_date', 'required' => 'required']) !!}
                            <span class="text-danger" id="start_date_error"></span> <!-- Error message -->
                        </div>
                        <div class="form-group col-md-6">
                            {!! Form::label('end_date', __('End Date'),['class'=>'form-label']) !!}
                            {!! Form::date('end_date', old('end_date'), ['class' => 'form-control', 'id' => 'end_date', 'required' => 'required']) !!}
                            <span class="text-danger" id="end_date_error"></span> <!-- Error message -->
                        </div>
                        <div class="form-group col-md-12">
                            <input type="text" class="form-control" value="{{$job->skill}}" data-toggle="tags" name="skill" placeholder="Skill"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 ">
            <div class="card card-fluid">
                <div class="card-body job-create">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <h6>{{__('Need to ask ?')}}</h6>
                                <div class="my-4">
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="applicant[]" value="gender" id="check-gender" {{(in_array('gender',$job->applicant)?'checked':'')}}>
                                        <label class="form-check-label" for="check-gender">{{__('Gender')}} </label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="applicant[]" value="dob" id="check-dob" {{(in_array('dob',$job->applicant)?'checked':'')}}>
                                        <label class="form-check-label" for="check-dob">{{__('Date Of Birth')}}</label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="applicant[]" value="country" id="check-country" {{(in_array('country',$job->applicant)?'checked':'')}}>
                                        <label class="form-check-label" for="check-country">{{__('Country')}}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <h6>{{__('Need to show option ?')}}</h6>
                                <div class="my-4">
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="visibility[]" value="profile" id="check-profile" {{(in_array('profile',$job->visibility)?'checked':'')}}>
                                        <label class="form-check-label" for="check-profile">{{__('Profile Image')}} </label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="visibility[]" value="resume" id="check-resume" {{(in_array('resume',$job->visibility)?'checked':'')}}>
                                        <label class="form-check-label" for="check-resume">{{__('Resume')}}</label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="visibility[]" value="letter" id="check-letter" {{(in_array('letter',$job->visibility)?'checked':'')}}>
                                        <label class="form-check-label" for="check-letter">{{__('Cover Letter')}}</label>
                                    </div>
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="visibility[]" value="terms" id="check-terms" {{(in_array('terms',$job->visibility)?'checked':'')}}>
                                        <label class="form-check-label" for="check-terms">{{__('Terms And Conditions')}}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <h6>{{__('Custom Question')}}</h6>
                            <div class="my-4">
                                @foreach($customQuestion as $question)
                                    <div class="form-check custom-checkbox">
                                        <input type="checkbox" class="form-check-input" name="custom_question[]" value="{{$question->id}}" id="custom_question_{{$question->id}}" {{(in_array($question->id,$job->custom_question)?'checked':'')}}>
                                        <label class="form-check-label" for="custom_question_{{$question->id}}">{{$question->question}} </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-fluid">
                <div class="card-body ">
                    <div class="row">
                        <div class="form-group col-md-12">
                            {!! Form::label('description', __('Job Description'),['class'=>'form-label']) !!}
                            <textarea class="form-control summernote-simple-2" name="description" id="exampleFormControlTextarea1" rows="15">{{$job->description}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-fluid">
                <div class="card-body">
                    <div class="row">
                        <div class="mb-2 form-group col-6">
                            {!! Form::label('requirement', __('Job Requirement'),['class'=>'form-label']) !!}
                        </div>
                        <div class="col-6 text-end">
                            @if($plan->chatgpt == 1)
                                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true" id="grammarCheck" data-url="{{ route('grammar',['grammar']) }}"
                                   data-bs-placement="top" data-title="{{ __('Grammar check with AI') }}">
                                    <i class="ti ti-rotate"></i> <span>{{__('Grammar check with AI')}}</span>
                                </a>
                            @endif
                        </div>
                        <div class="form-group col-md-12">
                            <textarea class="form-control summernote-simple" name="requirement" id="exampleFormControlTextarea2" rows="8">{{$job->requirement}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 text-end">
            <div class="form-group">
                <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
            </div>
        </div>
        {{Form::close()}}
    </div>
@endsection

