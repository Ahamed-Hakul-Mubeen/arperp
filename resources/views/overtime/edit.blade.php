{{Form::model($overtime,array('route' => array('overtime.update', $overtime->id), 'method' => 'PUT')) }}
<div class="modal-body">

    <div class="p-0 card-body">
        <div class="row">
            <div class="col-md-6 d-none">
                <div class="form-group">
                    {{ Form::label('title', __('Title'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{ Form::text('title',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
            <div class="col-md-6 d-none">
                <div class="form-group">
                    {{ Form::label('number_of_days', __('Number Of Days'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{ Form::text('number_of_days',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
            <div class="col-md-6 d-none">
                <div class="form-group">
                    {{ Form::label('hours', __('Hours'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{ Form::text('hours',null, array('class' => 'form-control ','required'=>'required')) }}
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {{ Form::label('rate', __('Rate'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                    {{ Form::number('rate',null, array('class' => 'form-control ','required'=>'required')) }}
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


