    {{Form::open(array('url'=>'leavetype','method'=>'post'))}}
    <div class="modal-body">

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('title',__('Leave Type'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::text('title',null,array('class'=>'form-control','placeholder'=>__('Enter Leave Type Name'),'required'=>'required'))}}
                @error('title')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('days',__('Days Per Year'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::number('days',null,array('class'=>'form-control','placeholder'=>__('Enter Days / Year'),'required'=>'required'))}}
            </div>
        </div>

    </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
    {{Form::close()}}

