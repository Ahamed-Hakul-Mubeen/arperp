{{ Form::open(['route' => ['product_type.store'], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="form-group">
        {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
        {{ Form::text('name', null,array('class'=>'form-control','placeholder'=>__('Enter Assets Product'))) }}
        @error('name')
            <span class="invalid-name" role="alert">
                <strong class="text-danger">{{ $message }}</strong>
            </span>
        @enderror
    </div>

    <div class="form-group">
        <div class="row">
            <div class="col-sm-12">
                {{ Form::label('asset_property', __('Add Asset Properties'), ['class' => 'form-label']) }}
            </div>
            <div class="col-sm-10">
                <div class="form-group">
                    <input type="text" class="form-control" name="asset_property[]"  placeholder="{{ __('Enter Asset Property') }}" required />
                </div>
                @error('asset_property')
                    <span class="invalid-asset_property" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-sm-2">
                <button type="button" id="add_asset_property_btn" class="btn btn-info btn-sm mt-1"> <i class="ti ti-plus"></i></button>
            </div>
        </div>
        <div id="asset_property_list_div"></div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
</div>
{{ Form::close() }}

<script type="text/javascript">
    var global_asset_property_count = 0;
    $(document).ready(function() {
        $('#add_asset_property_btn').click(function() {
            global_asset_property_count++;
            var add = `<div class="row" id="asset_property_div${global_asset_property_count}">
                            <div class="col-sm-10">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="asset_property[]" placeholder="{{ __('Enter Asset Property') }}" />
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <button type="button" onclick=remove_asset_property(${global_asset_property_count}) class="btn btn-danger btn-sm mt-1"> <i class="text-white ti ti-trash"></i> </button>
                            </div>
                        </div>`;
            $("#asset_property_list_div").append(add);
        });
    });

    function remove_asset_property(property_index) {
        $(`#asset_property_div${property_index}`).remove();
    }
</script>