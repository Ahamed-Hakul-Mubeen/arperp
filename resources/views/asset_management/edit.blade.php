{{ Form::model($asset, array('route' => array('asset_management.update', $asset->id), 'method' => 'PUT')) }}

<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('product_name', __('Assets Type'), ['class' => 'form-label']) }}
                {{ Form::select('product_name', $productTypes, $asset->product_type_id, ['id' => 'product_name', 'class' => 'form-control', 'placeholder' => __('Select Product Type')]) }}
                @error('product_name')
                <span class="invalid-product_name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div id="asset_properties_section">
                <!-- Asset properties will be dynamically inserted here -->
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('product_description', __('Product Name'), ['class' => 'form-label']) }}
                {{ Form::text('product_description', null, ['class' => 'form-control', 'placeholder' => __('Enter Product Name')]) }}
                @error('product_description')
                <span class="invalid-product_description" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('product_configuration', __('Product Configuration'), ['class' => 'form-label']) }}
                {{ Form::textarea('product_configuration', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Enter Product Configuration')]) }}
                @error('product_configuration')
                <span class="invalid-product_configuration" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
</div>

{{ Form::close() }}

<script type="text/javascript">
  $(document).ready(function() {
    // Function to fetch and display asset properties
    function loadAssetProperties(productTypeId, assetId = null) {
        $.ajax({
            url: assetId ? '{{ route('get.asset.properties.edit', $asset->id) }}' : '{{ route('get.asset.properties') }}',
            type: 'GET',
            data: {
                product_type_id: productTypeId,
                asset_id: assetId // This will only be passed for editing
            },
            success: function(response) {
                var propertiesHtml = '';
                var properties = response.properties;
                var existingValues = response.existingValues || {}; // Default to an empty object if null

                $.each(properties, function(index, property) {
                    var propertyValue = existingValues[property] || ''; // Get existing value or leave blank
                    propertiesHtml += `<div class="form-group">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" value="${property}" readonly />
                                                </div>
                                                <div class="col-sm-6">
                                                    <input type="text" class="form-control" name="asset_property_values[${property}]" value="${propertyValue}" placeholder="Enter The value" />
                                                </div>
                                            </div>
                                        </div>`;
                });
                $('#asset_properties_section').html(propertiesHtml);
            },
            error: function(error) {
                console.error(error);
            }
        });
    }

    // Load properties when the form is loaded (initial load for editing)
    var initialProductTypeId = $('#product_name').val(); // Get the initially selected product type
    var assetId = '{{ $asset->id ?? null }}'; // The asset ID (if editing)
    if (initialProductTypeId) {
        loadAssetProperties(initialProductTypeId, assetId);
    }

    // Event listener for asset type change
    $('#product_name').change(function() {
        var selectedProductTypeId = $(this).val();
        if (selectedProductTypeId) {
            loadAssetProperties(selectedProductTypeId, assetId); // Pass assetId to get existing values when editing
        } else {
            $('#asset_properties_section').html(''); // Clear the properties if no type is selected
        }
    });
});
</script>