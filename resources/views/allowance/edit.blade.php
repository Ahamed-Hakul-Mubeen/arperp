{{Form::model($allowance,array('route' => array('allowance.update', $allowance->id), 'method' => 'PUT')) }}
<div class="modal-body">

    <div class="p-0 card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('allowance_option', __('Allowance Options')) }}<span class="text-danger">*</span>
                    {{ Form::select('allowance_option',$allowance_options,null, array('class' => 'form-control select','required'=>'required')) }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('title', __('Title')) }}<span class="text-danger">*</span>
                    {{ Form::text('title',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('type', __('Type'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
                    {{ Form::select('type', $Allowancetypes, null, ['class' => 'form-control select amount_type', 'required' => 'required']) }}
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('amount', __('Amount') , ['class' => 'form-label'])  }}<span class="text-danger">*</span>
                    {{ Form::number('amount',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
</div>
    {{Form::close()}}
