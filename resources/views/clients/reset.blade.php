
{{Form::model($user,array('route' => array('client.password.update', $user->id), 'method' => 'post')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group">
            {{ Form::label('password', __('Password')) }}
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder= "{{__('Enter Password')}}">
            @error('password')
            <span class="invalid-feedback" role="alert">
               <strong>{{ $message }}</strong>
           </span>
            @enderror
        </div>
        <div class="form-group">
            {{ Form::label('password_confirmation', __('Confirm Password')) }}
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder = "{{__('Enter Confirm Password')}}">
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Reset') }}</button>
</div>

{{Form::close()}}

