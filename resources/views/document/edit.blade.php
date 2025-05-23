    {{Form::model($document,array('route' => array('document.update', $document->id), 'method' => 'PUT')) }}
    <div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{Form::label('name',__('Name',['class'=>'form-label']))}}<span class="text-danger">*</span>
                {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Department Name'),'required'=>'required'))}}
                @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('is_required', __('Required Field'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                <select class="form-control select2" required name="is_required">
                    <option value="0" @if($document->is_required == 0) selected @endif>{{__('Not Required')}}</option>
                    <option value="1" @if($document->is_required == 1) selected @endif>{{__('Is Required')}}</option>
                </select>
            </div>
        </div>

    </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
    </div>
    {{Form::close()}}
