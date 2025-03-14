{{Form::open(array('url'=>'loan','method'=>'post'))}}
{{ Form::hidden('employee_id',$employee->id, array()) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-lg-4 col-md-6">
            {{ Form::label('title', __('Title'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('title',null, array('class' => 'form-control ','required'=>'required')) }}
        </div>
        <div class="form-group col-lg-4 col-md-6">
            {{ Form::label('loan_option', __('Loan Options'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('loan_option',$loan_options,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-lg-4 col-md-6">
            {{ Form::label('type', __('Type'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('type', $loan, null, ['class' => 'form-control select amount_type', 'required' => 'required']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Loan Amount'),['class'=>'form-label amount_label']) }}<span class="text-danger">*</span>
            {{ Form::number('amount',null, array('class' => 'form-control ','required'=>'required','step'=>'0.01')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('no_of_months', __('No Of Months'),['class'=>'form-label amount_label']) }}<span class="text-danger">*</span>
            {{ Form::number('no_of_months',null, array('class' => 'form-control ','required'=>'required','step'=>'0.01','min' => 1)) }}
        </div>
{{--        <div class="form-group col-md-6">--}}
{{--            {{ Form::label('start_date', __('Start Date'),['class'=>'form-label']) }}--}}
{{--            {{ Form::date('start_date',null, array('class' => 'form-control','required'=>'required')) }}--}}
{{--        </div>--}}
{{--        <div class="form-group col-md-6">--}}
{{--            {{ Form::label('end_date', __('End Date'),['class'=>'form-label']) }}--}}
{{--            {{ Form::date('end_date',null, array('class' => 'form-control','required'=>'required')) }}--}}
{{--        </div>--}}

        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('reason', __('Reason')) }}<span class="text-danger">*</span>
                {{ Form::textarea('reason',null, array('class' => 'form-control ','required'=>'required','rows' => 3)) }}
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{ Form::close() }}
