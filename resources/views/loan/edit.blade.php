{{Form::model($loan,array('route' => array('loan.update', $loan->id), 'method' => 'PUT')) }}
<div class="modal-body">
    <div class="card-body p-0">
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    {{ Form::label('title', __('Title')) }}<span class="text-danger">*</span>
                    {{ Form::text('title',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    {{ Form::label('loan_option', __('Loan Options')) }}<span class="text-danger">*</span>
                    {{ Form::select('loan_option',$loan_options,null, array('class' => 'form-control select','required'=>'required')) }}
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    {{ Form::label('type', __('Type')) }}<span class="text-danger">*</span>
                    {{ Form::select('type', $loans, null, ['class' => 'form-control select amount_type', 'required' => 'required']) }}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('amount', __('Loan Amount'),['class'=>'form-label amount_label']) }}<span class="text-danger">*</span>
                    {{ Form::number('amount',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
            <div class="form-group col-md-6">
                {{ Form::label('no_of_months', __('No Of Months'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                {{ Form::number('no_of_months',null, array('class' => 'form-control ','required'=>'required','step'=>'0.01','min' => 1)) }}
            </div>
{{--            <div class="col-md-6">--}}
{{--                <div class="form-group">--}}
{{--                    {{ Form::label('start_date', __('Start Date')) }}--}}
{{--                    {{ Form::date('start_date',null, array('class' => 'form-control ','required'=>'required')) }}--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="col-md-6">--}}
{{--                <div class="form-group">--}}
{{--                    {{ Form::label('end_date', __('End Date')) }}--}}
{{--                    {{ Form::date('end_date',null, array('class' => 'form-control ','required'=>'required')) }}--}}
{{--                </div>--}}
{{--            </div>--}}
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('reason', __('Reason')) }}<span class="text-danger">*</span>
                    {{ Form::textarea('reason',null, array('class' => 'form-control ','required'=>'required','rows' => 3)) }}
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
</div>
{{Form::close()}}
