{{Form::open(array('url'=>'designation','method'=>'post'))}}
    <div class="modal-body">

    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('department_id', __('Department'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                {{ Form::select('department_id', $departments,null, array('class' => 'form-control select','required'=>'required')) }}
            </div>
            <div class="form-group">
                {{Form::label('name',__('Name'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Designation Name'),'required'=>'required'))}}
                @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

    </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
    {{Form::close()}}

