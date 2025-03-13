    {{Form::open(array('url'=>'paysliptype','method'=>'post','enctype' => 'multipart/form-data'))}}
    <div class="modal-body">


    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('name',__('Name'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::text('name',null,array('class'=>'form-control','placeholder'=>__('Enter Payslip Type Name'),'required'=>'required'))}}
                @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-group">
                {{Form::label('digital_signature',__('Digital Signature'),['class'=>'col-form-label'])}}
                <input type="file" class="form-control" name="digital_signature" id="digital_signature" data-filename="signature_create" >
                <img id="blah" src="" class="mt-3" width="25%"/>
                <p class="signature_create"></p>
            </div>
        </div>

    </div>
    </div>
    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
    </div>
    {{Form::close()}}

