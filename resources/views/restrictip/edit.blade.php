{{ Form::model($ip, ['route' => ['restrict-ip.update', $ip->id], 'method' => 'PUT']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            {{ Form::label('ip', __('IP'), ['class' => 'col-form-label']) }}
            {{ Form::text('ip', null, ['class' => 'form-control']) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
</div>
{{ Form::close() }}
