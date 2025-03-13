<div class="modal-body">
    <h5>{{ __('Asset Name :') }} {{ $asset->productType->name }}</h5>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ __('Property Name') }}</th>
                    <th>{{ __('Property Value') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Decode the properties and their values from the asset
                    $propertyNames = json_decode($asset->productType->asset_properties, true);
                    $propertyValues = json_decode($asset->asset_properties_values, true);
                @endphp

                @foreach($propertyNames as $propertyName)
                    <tr>
                        <td>{{ ucfirst($propertyName) }}</td>
                        <td>{{ $propertyValues[$propertyName] ?? '--' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
</div>
<style>
    .table-bordered {
    border: 1px solid black; /* Set the outer table border to black */
}

.table-bordered td, .table-bordered th {
    border: 1px solid black; /* Set the cell borders (for <td> and <th>) to black */
}
</style>