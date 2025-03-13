{{ Form::open(['route' => ['project.attachment.store', $project->id], 'files' => true]) }}
<div class="modal-body px-3">
    <div class="form-group">
        {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
        {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required']) }}
        @error('name')
            <span class="invalid-name" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col-sm-12">
                {{ Form::label('file', __('Upload Files'), ['class' => 'form-label']) }}
            </div>
            <div class="col-sm-10">
                <div class="form-group">
                    <input type="file" class="form-control" name="file[]" required>
                </div>
                @error('file')
                    <span class="invalid-file" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-sm-2">
                <button type="button" id="add_file_btn" class="btn btn-info btn-sm mt-1"> <i class="ti ti-plus"></i></button>
            </div>
        </div>
        <div id="file_list_div"></div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
</div>
{{ Form::close() }}

<script type="text/javascript">
    var global_file_count = 0;
    $(document).ready(function() {
        $('#add_file_btn').click(function() {
            global_file_count++;
            var add = `<div class="row" id="file_div${global_file_count}">
                            <div class="col-sm-10">
                                <div class="form-group">
                                    <input type="file" class="form-control" name="file[]">
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <button type="button" onclick=remove_file(${global_file_count}) class="btn btn-danger btn-sm mt-1"> <i class="text-white ti ti-trash"></i> </button>
                            </div>
                        </div>`;
            $("#file_list_div").append(add);
        });
    });

    function remove_file(file_index)
    {
        $(`#file_div${file_index}`).remove();
    }
</script>
