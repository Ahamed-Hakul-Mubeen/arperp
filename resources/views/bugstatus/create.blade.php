{{ Form::open(array('url' => 'bugstatus')) }}
<div class="modal-body">

    <div class="row">
        <div class="form-group col-12">
            {{ Form::label('title', __('Bug Status Title'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('title', '', array('class' => 'form-control','required'=>'required')) }}
        </div>

    </div>
</div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
    {{ Form::close() }}

