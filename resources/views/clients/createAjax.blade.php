<div class="card bg-none card-box">
    {{ Form::open(array('url' => 'clients')) }}
    <div class="row">
        <div class="col-6 form-group">
            {{ Form::label('name', __('Name'),['class'=>'form-label']) }}
            {{ Form::text('name', null, array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="col-6 form-group">
            {{ Form::label('email', __('E-Mail Address'),['class'=>'form-label']) }}
            {{ Form::email('email', null, array('class' => 'form-control','required'=>'required')) }}
        </div>
        <div class="col-6 form-group">
            {{ Form::label('password', __('Password'),['class'=>'form-label']) }}
            {{ Form::password('password', null, array('class' => 'form-control','required'=>'required')) }}
        </div>

        <div class="mt-4 mb-0 form-group">
            {{ Form::hidden('ajax',true) }}
            <button type="submit" class="btn-create badge-blue">{{ __('Create') }}</button>
        </div>
    </div>
    {{ Form::close() }}
</div>
