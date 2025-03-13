{{ Form::open(['url' => 'currency']) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('currency_code', __('Currency Code'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('currency_code', '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Currency Code')]) }}
            @error('currency_code')
                <small class="invalid-currency_code" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('currency_symbol', __('Currency Symbol'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('currency_symbol', '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Currency Symbol')]) }}
            @error('currency_symbol')
                <small class="invalid-currency_symbol" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
</div>
{{ Form::close() }}
