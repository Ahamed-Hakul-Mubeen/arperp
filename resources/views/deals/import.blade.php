{{ Form::open(array('route' => array('deals.import'),'method'=>'post', 'enctype' => "multipart/form-data")) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-12 mb-6">
            {{Form::label('file',__('Download sample Deal CSV file'),['class'=>'form-label'])}}
            <a href="{{asset(Storage::url('uploads/sample')).'/sample-deal.csv'}}" class="btn btn-sm btn-primary">
                <i class="ti ti-download"></i> {{__('Download')}}
            </a>
        </div>
        <div class="col-md-12">
            {{Form::label('file',__('Select CSV File'),['class'=>'form-label'])}}
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" class="form-control" name="file" id="file" data-filename="upload_file" required>
                </label>
                <p class="upload_file"></p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
</div>
{{ Form::close() }}
