    {{Form::open(array('url'=>'document','method'=>'post'))}}
    <div class="modal-body">

    <div class="row">
        <div class="form-group col-12">
            {{Form::label('name',__('Name'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Document Name'),'required'=>'required'))}}
            @error('name')
            <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group col-12">
            {{ Form::label('is_required', __('Required Field'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            <select class="form-control select2" required name="is_required">
                <option value="0">{{__('Not Required')}}</option>
                <option value="1">{{__('Is Required')}}</option>
            </select>
        </div>

    </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
    {{Form::close()}}

