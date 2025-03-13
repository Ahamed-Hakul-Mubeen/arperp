{{ Form::model($exchange_rate, array('route' => array('exchange_rate.update', $exchange_rate->id), 'method' => 'PUT')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('from_currency', __('From Currency'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('from_currency', array_combine($currency->toArray(), $currency->toArray()), null, ['class' => 'form-control', 'required' => 'required']) }}
            @error('from_currency')
                <small class="invalid-from_currency" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('to_currency', __('To Currency'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('to_currency', array_combine($currency->toArray(), $currency->toArray()), null, ['class' => 'form-control', 'required' => 'required']) }}
            @error('to_currency')
                <small class="invalid-to_currency" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('from_amount', __('From Amount'), ['class' => 'form-label']) }}
            {{ Form::number('from_amount', '1', ['class' => 'form-control', 'disabled' => 'disabled']) }}
            @error('from_amount')
                <small class="invalid-from_amount" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('exchange_rate', __('Exchange Rate'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::text('exchange_rate', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Currency Symbol')]) }}
            @error('exchange_rate')
                <small class="invalid-exchange_rate" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </small>
            @enderror
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
</div>
{{ Form::close() }}
