{{ Form::open(['route' => ['invoice.payment', $invoice->id], 'method' => 'post', 'id' => 'payment_form', 'enctype' => 'multipart/form-data']) }}
<div class="modal-body">
    <div class="row">
        @if (count($advance) != 0)
            <div class="form-group col-md-12">
                {{ Form::label('advance_id', __('Pending Advance'), ['class' => 'form-label']) }}
                <select class="form-control select" name="advance_id" id="advance_id">
                    <option value="">Select Advance</option>
                    @foreach ($advance as $adv)
                        <option data-date="{{ $adv->date }}" data-amount="{{ $adv->balance }}" data-account="{{ $adv->account_id }}" value="{{ $adv->id }}"> {{  Auth::user()->advanceNumberFormat($adv->advance_id)}} ({{  Auth::user()->priceFormat($adv->balance, null, $adv->currency_symbol)}}) </option>
                    @endforeach
                </select>
                {{-- {{ Form::select('advance_id', $advance, null, ['class' => 'form-control select', 'id' => 'advance_id', 'required' => 'required']) }} --}}
            </div>
        @endif
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'), ['class' => 'form-label']) }}
            {{ Form::date('date', '', ['id' => 'date', 'class' => 'form-control ', 'required' => 'required']) }}
        </div>
        <div class="col-md-6">
            {{ Form::label('amount', __('Amount'), ['class' => 'form-label']) }}
            <div class="form-group input-group">
                {{ Form::number('amount', number_format($invoice->getDue(), 2, '.', ''), ['id' => 'amount', 'class' => 'form-control', 'required' => 'required', 'step' => '0.01', 'placeholder' => __('Enter Amount')]) }}
                <span class="bg-transparent input-group-text">{{ $invoice->currency_code }}</span>
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Account'), ['class' => 'form-label']) }}
            {{ Form::select('account_id', $accounts, null, ['class' => 'form-control select', 'id' => 'account_id', 'required' => 'required', 'readonly' => "readonly"]) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'), ['class' => 'form-label']) }}
            {{ Form::text('reference', '', ['class' => 'form-control', 'placeholder' => __('Enter Reference')]) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', '', ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Enter Description')]) }}
        </div>

        <div class="col-md-6 form-group">
            {{ Form::label('add_receipt', __('Payment Receipt'), ['class' => 'form-label']) }}
            <div class="choose-file form-group">
                <label for="file" class="form-label">
                    <input type="file" name="add_receipt" id="image" class="form-control">
                </label>
                <p class="upload_file"></p>
            </div>
        </div>

    </div>
    <div class="modal-footer">
        <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-success">{{ __('Add') }}</button>
    </div>

</div>
{{ Form::close() }}
<script>
    $(document).ready(function(){
        $("#advance_id").change(function(){
            if(this.value === "")
            {
                $("#amount").val("");
                $("#date").val("").attr("disabled", false);
                $("#account_id").val("").attr("disabled", false);
            }
            else
            {
                var date = $("#advance_id option:selected").attr('data-date');
                var amount = $("#advance_id option:selected").attr('data-amount');
                var account_id = $("#advance_id option:selected").attr('data-account');
                $("#amount").val(amount);
                $("#date").val(date).attr("disabled", true);
                $("#account_id").val(account_id).prop('disabled', true);
            }
        });
        $('#payment_form').on('submit', function() {
            $('#account_id').prop('disabled', false);
            $('#date').prop('disabled', false);
        });
    });
</script>
