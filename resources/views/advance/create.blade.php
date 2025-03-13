{{ Form::open(array('url' => 'advance','enctype' => 'multipart/form-data')) }}
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('currency_code', __('Currency'),['class'=>'form-label']) }} <span class="text-danger">*</span><span id="conversion_rate_span"></span>
                <select class="form-control" name="currency_code" id="currency_code" required>
                    {{-- <option value="">Select Currency</option> --}}
                    @php $currency_code = \Auth::user()->currencyCode() @endphp
                    @foreach($currency as $curr)
                        @if($currency_code == $curr->currency_code)
                            <option selected value="{{ $curr->currency_code }}" data-symbol="{{ $curr->currency_symbol }}">{{ $curr->currency_code }}</option>
                        @else
                            <option value="{{ $curr->currency_code }}" data-symbol="{{ $curr->currency_symbol }}">{{ $curr->currency_code }}</option>
                        @endif
                    @endforeach
                </select>
                <input type="hidden" name="currency_symbol" id="currency_symbol" value="{{ \Auth::user()->currencySymbol() }}">
                <input type="hidden" name="exchange_rate" id="exchange_rate" value="1">
                {{-- {{ Form::select('currency', $currency, null, array('class' => 'form-control select','id'=>'currency', 'required'=>'required')) }} --}}
            </div>
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            <div class="form-group input-group">
                <span class="bg-transparent input-group-text"><span class="my_currency_symbol">{{ \Auth::user()->currencySymbol() }}</span></span>
                {{ Form::number('amount', '', array('class' => 'form-control','required'=>'required','step'=>'0.01' , 'placeholder'=>__('Enter Amount'))) }}
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Account'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('account_id',$accounts,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{Form::date('date',null,array('class'=>'form-control','required'=>'required'))}}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'),['class'=>'form-label']) }}
            {{ Form::text('reference', '', array('class' => 'form-control' , 'placeholder'=>__('Enter Reference'))) }}
        </div>
        <div class="form-group col-md-6">
            <div class="d-flex justify-content-between">
                <div>
                    {{ Form::label('customer_id', __('Customer'),['class'=>'form-label']) }}
                    <span class="text-danger">*</span>
                </div>
            <a href="#" data-size="lg" data-url="{{ route('customer.create',['redirect_to_advance' => 1]) }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Customer')}}">
                <i class="ti ti-plus"></i>{{__('Add Customer')}}
            </a>
           </div>
            {{ Form::select('customer_id', $customers,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {{ Form::textarea('description', '', array('class' => 'form-control','rows'=>3 , 'placeholder'=>__('Enter Description'))) }}
        </div>

        <div class="form-group col-md-6">
            {{Form::label('add_receipt',__('Payment Receipt'),['class' => 'col-form-label'])}}
            {{Form::file('add_receipt',array('class'=>'form-control', 'id'=>'files'))}}
            <img id="image" class="mt-3" style="width:25%;"/>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" id="submit_btn" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{ Form::close() }}

<script>
    document.getElementById('files').onchange = function () {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
    $(document).on('change', '#currency_code', function() {
        var my_currency_symbol = $(this).find(':selected').data("symbol");
        $(".my_currency_symbol").html(my_currency_symbol);
        $("#currency_symbol").val(my_currency_symbol);

        var currency_code = $("#currency_code").val();

        $.ajax({
            url:"{{ route('fetch.exchange_rate') }}",
            data:{currency_code:currency_code},
            method:"GET",
            dataType: "json",
            success:function(data){
                $("#conversion_rate_span").html(``);
                $("#submit_btn").prop("disabled", false);
                if (data.status == 0) {
                    show_toastr('danger', data.message);
                    $("#submit_btn").prop("disabled", true);
                } else {
                    if(data.exchange_rate != 1)
                    {
                        $("#exchange_rate").val(data.exchange_rate);
                        $("#conversion_rate_span").html(` (1 ${ $("#currency_code").val() } = ${ data.exchange_rate } {{ \Auth::user()->currencyCode() }}  )`);
                    }
                }
            },
            error: function(code) {
                alert(code.statusText);
            },
        });
    });
</script>
