{{ Form::open(array('url' => 'project-task-new-stage')) }}
<div class="modal-body">

    <div class="row">
        <div class="form-group col-12">
            {{ Form::label('name', __('Project Task Stage Name'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('name', '', array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="form-group col-12">
            {{ Form::label('color', __('Color'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            <input class="jscolor form-control" value="FFFFFF" name="color" id="color" required>
            <small class="small">{{ __('For chart representation') }}</small>
        </div>

    </div>
</div>
<div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{ Form::close() }}
