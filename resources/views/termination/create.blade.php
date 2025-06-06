{{Form::open(array('url'=>'termination','method'=>'post'))}}
<div class="modal-body">
    {{-- start for ai module--}}
    @php
        $plan= \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if($plan->chatgpt == 1)
    <div class="text-end">
        <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['termination']) }}"
           data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
            <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
        </a>
    </div>
    @endif
    {{-- end for ai module--}}
    <div class="row">
        <div class="form-group col-lg-6 col-md-6">
            {{ Form::label('employee_id', __('Employee'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{ Form::select('employee_id', $employees,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{ Form::label('termination_type', __('Termination Type'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('termination_type', $terminationtypes,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('notice_date',__('Notice Date'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{Form::date('notice_date',null,array('class'=>'form-control ','required'=>'required'))}}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('termination_date',__('Termination Date'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{Form::date('termination_date',null,array('class'=>'form-control ','required'=>'required'))}}
        </div>
        <div class="form-group  col-lg-12">
            {{Form::label('description',__('Description'),['class'=>'form-label'])}}
            {{Form::textarea('description',null,array('class'=>'form-control','placeholder'=>__('Enter Description')))}}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{Form::close()}}
