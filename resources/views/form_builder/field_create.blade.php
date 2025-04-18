{{ Form::open(array('route' => ['form.field.store',$formbuilder->id])) }}
<div class="modal-body">
    <div class="row" id="frm_field_data">
        <div class="col-12 form-group">
            {{ Form::label('name', __('Question Name'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('name[]', '', array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="col-12 form-group">
            {{ Form::label('type', __('Type'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('type[]', $types,null, array('class' => 'form-control select2','id'=>'choices-multiple1','required'=>'required')) }}
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{Form::close()}}
