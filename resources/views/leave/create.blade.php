{{Form::open(array('url'=>'leave','method'=>'post'))}}
    <div class="modal-body">
        {{-- start for ai module--}}
        @php
            $plan= \App\Models\Utility::getChatGPTSettings();
        @endphp
        @if($plan->chatgpt == 1)
            <div class="text-end">
                <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['leave']) }}"
                  data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                    <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
                </a>
            </div>
        @endif
        {{-- end for ai module--}}
        @if(\Auth::user()->type =='company' || \Auth::user()->type =='HR'|| \Auth::user()->type !='Employee')
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {{Form::label('employee_id',__('Employee') ,['class'=>'form-label'])}}<span class="text-danger">*</span>
                        {{Form::select('employee_id',$employees,null,array('class'=>'form-control select','id'=>'employee_id','placeholder'=>__('Select Employee'),'required' => 'required'))}}
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{Form::label('leave_type_id',__('Leave Type') ,['class'=>'form-label'])}}<span class="text-danger">*</span>
                    <select name="leave_type_id" id="leave_type_id" class="form-control select " required>
                        <option value="">{{ __('Select Leave Type') }}</option>
                        @foreach($leavetypes as $leave)
                            <option value="{{ $leave->id }}">{{ $leave->title }} (<p class="float-right pr-5">{{ $leave->days }}</p>)</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('start_date', __('Start Date'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{Form::date('start_date',null,array('class'=>'form-control' ,'required' => 'required' ))}}


                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('end_date', __('End Date'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{Form::date('end_date',null,array('class'=>'form-control','required' => 'required'  ))}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{Form::label('leave_reason',__('Leave Reason') ,['class'=>'form-label'])}}<span class="text-danger">*</span>
                    {{Form::textarea('leave_reason',null,array('class'=>'form-control','required' => 'required','placeholder'=>__('Leave Reason')))}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-end">
                <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm text-right" data-ajax-popup-over="true" id="grammarCheck" data-url="{{ route('grammar',['grammar']) }}"
                   data-bs-placement="top" data-title="{{ __('Grammar check with AI') }}">
                    <i class="ti ti-rotate"></i> <span>{{__('Grammar check with AI')}}</span>
                </a>
            </div>
            @if(\Auth::user()->type !='Employee')
            <div class="col-md-12">
                <div class="form-group">
                    {{Form::label('remark',__('Remark'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                    {{Form::textarea('remark',null,array('class'=>'form-control grammer_textarea','placeholder'=>__('Leave Remark'),'required' => 'required'))}}
                </div>
            </div>
            @endif

        </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
{{Form::close()}}

<script>
    $(document).ready(function(){
        $('#start_date').on('change', function() {
            var startDate = $(this).val();
            $('#end_date').attr('min', startDate);
        });
    });
</script>
