@extends('layouts.admin')
@section('page-title')
    {{__('Proposal Create')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('proposal.index')}}">{{__('Proposal')}}</a></li>
    <li class="breadcrumb-item">{{__('Proposal Create')}}</li>
@endsection
@push('script-page')
    <script src="{{asset('js/jquery-ui.min.js')}}"></script>
    <script src="{{asset('js/jquery.repeater.min.js')}}"></script>
    <script>
        var selector = "body";
        if ($(selector + " .repeater").length) {
            var $dragAndDrop = $("body .repeater tbody").sortable({
                handle: '.sort-handler'
            });
            var $repeater = $(selector + ' .repeater').repeater({
                initEmpty: false,
                defaultValues: {
                    'status': 1
                },
                show: function () {
                    var my_currency_symbol = $('#currency_code').find(':selected').data("symbol");
                    $(".my_currency_symbol").html(my_currency_symbol);
                    $("#currency_symbol").val(my_currency_symbol);

                    $(".item").trigger("change");

                    $(this).slideDown();
                    var file_uploads = $(this).find('input.multi');
                    if (file_uploads.length) {
                        $(this).find('input.multi').MultiFile({
                            max: 3,
                            accept: 'png|jpg|jpeg',
                            max_size: 2048
                        });
                    }
                    $('.select2').select2();
                },
                hide: function (deleteElement) {
                    if (confirm('Are you sure you want to delete this element?')) {
                        $(this).slideUp(deleteElement);
                        $(this).remove();

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }
                        $('.subTotal').html(subTotal.toFixed(2));
                        $('.totalAmount').html(subTotal.toFixed(2));
                    }
                },
                ready: function (setIndexes) {
                    $dragAndDrop.on('drop', setIndexes);
                },
                isFirstItemUndeletable: true
            });
            var value = $(selector + " .repeater").attr('data-value');
            if (typeof value != 'undefined' && value.length != 0) {
                value = JSON.parse(value);
                $repeater.setList(value);
            }

        }

        $(document).on('change', '#customer', function () {
            $('#customer_detail').removeClass('d-none');
            $('#customer_detail').addClass('d-block');
            $('#customer-box').removeClass('d-block');
            $('#customer-box').addClass('d-none');
            var id = $(this).val();
            var url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'id': id
                },
                cache: false,
                success: function (data) {
                    if (data != '') {
                        $('#customer_detail').html(data);
                    } else {
                        $('#customer-box').removeClass('d-none');
                        $('#customer-box').addClass('d-block');
                        $('#customer_detail').removeClass('d-block');
                        $('#customer_detail').addClass('d-none');
                    }
                },

            });
        });

        $(document).on('click', '#remove', function () {
            $('#customer-box').removeClass('d-none');
            $('#customer-box').addClass('d-block');
            $('#customer_detail').removeClass('d-block');
            $('#customer_detail').addClass('d-none');
        })

        $(document).on('change', '.tax-select', function () {
            // alert("tax changed");
            var el = $(this).parent().parent().parent().parent().parent();
            // console.log(el);
            var totalItemTaxRate = $(this).find('option:selected').attr('data-taxrate');
            var taxid = $(this).find('option:selected').attr('value');

            $(el.find('.itemTaxRate')).val(parseFloat(totalItemTaxRate).toFixed(2));

            var tax = [];
            tax.push(taxid);
            $(el.find('.tax')).val(tax);

            var quantity = $(el.find('.quantity')).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();

            if(discount.length <= 0)
            {
                discount = 0 ;
            }

            var totalItemPrice = (quantity * price) - discount;
            var amount = (totalItemPrice);

            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            if(isNaN(totalItemTaxRate)) {
                totalItemTaxRate = 0;
            }
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html((parseFloat(itemTaxPrice)+parseFloat(amount)).toFixed(2));
            // alert("1 - "+$(el.find('.amount')).html());
            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }

            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

        });
        $(document).on('change', '.item', function () {

            var currency_code = $("#currency_code").val();
            var iteams_id = $(this).val();
            var url = $(this).data('url');
            var el = $(this);
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': jQuery('#token').val()
                },
                data: {
                    'product_id': iteams_id,
                    currency_code:currency_code
                },
                cache: false,
                success: function (data) {
                    var item = JSON.parse(data);

                    if (item.hasOwnProperty('status') && item.status == 0) {
                        show_toastr('danger', item.message);
                    } else {
                        if(item.exchange_rate != 1)
                        {
                            $("#conversion_rate_span").html(` (1 ${ $("#currency_code").val() } = ${ item.exchange_rate } {{ \Auth::user()->currencyCode() }}  )`);
                        } else {
                            $("#conversion_rate_span").html(``);
                        }
                        $("#exchange_rate").val(item.exchange_rate);

                        $(el.parent().parent().find('.quantity')).val(1);
                        $(el.parent().parent().find('.price')).val(item.product.sale_price);
                        $(el.parent().parent().parent().find('.pro_description')).val(item.product.description);
                        // $('.pro_description').text(item.product.description);

                        var taxes = '';
                        var tax = [];

                        var totalItemTaxRate = 0;
                        if (item.taxes == 0) {
                            taxes += '-';
                        } else {
                            taxes += `<select class='form-control tax-select' required><option value=''>--</option>`;
                            for (var i = 0; i < item.taxes.length; i++) {
                                taxes += `<option data-taxrate='${item.taxes[i].rate}' value='${item.taxes[i].id}'>${item.taxes[i].name} (${item.taxes[i].rate}%)</option>`;
                                // taxes += '<span class="mt-1 mr-2 badge bg-primary">' + item.taxes[i].name + ' ' + '(' + item.taxes[i].rate + '%)' + '</span>';
                                // tax.push(item.taxes[i].id);
                                // totalItemTaxRate += parseFloat(item.taxes[i].rate);
                            }
                            taxes += '</select>';
                        }

                        var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (item.product.sale_price * 1));

                        $(el.parent().parent().find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));
                        $(el.parent().parent().find('.itemTaxRate')).val(totalItemTaxRate.toFixed(2));
                        $(el.parent().parent().find('.taxes')).html(taxes);
                        $(el.parent().parent().find('.tax')).val(tax);
                        $(el.parent().parent().find('.unit')).html(item.unit);
                        $(el.parent().parent().find('.discount')).val(0);
                        $(el.parent().parent().find('.amount')).html(parseFloat(item.totalAmount).toFixed(2));
                        // alert("2 - "+$(el.parent().parent().find('.amount')).html());

                        var inputs = $(".amount");
                        var subTotal = 0;
                        for (var i = 0; i < inputs.length; i++) {
                            subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
                        }

                        var totalItemPrice = 0;
                        var priceInput = $('.price');
                        for (var j = 0; j < priceInput.length; j++) {
                            totalItemPrice += parseFloat(priceInput[j].value);
                        }

                        var totalItemTaxPrice = 0;
                        var itemTaxPriceInput = $('.itemTaxPrice');
                        for (var j = 0; j < itemTaxPriceInput.length; j++) {
                            if(itemTaxPriceInput[j].value)
                            {
                                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
                                // alert(itemTaxPriceInput[j].value);
                                $(el.parent().parent().find('.amount')).html((parseFloat(item.totalAmount)+parseFloat(itemTaxPriceInput[j].value)).toFixed(2));
                                // alert("3 - "+$(el.parent().parent().find('.amount')).html());
                            }
                        }

                        var totalItemDiscountPrice = 0;
                        var itemDiscountPriceInput = $('.discount');

                        for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                            totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
                        }

                        $('.subTotal').html(totalItemPrice.toFixed(2));
                        $('.totalTax').html(totalItemTaxPrice.toFixed(2));
                        $('.totalAmount').html((parseFloat(totalItemPrice) - parseFloat(totalItemDiscountPrice) + parseFloat(totalItemTaxPrice)).toFixed(2));
                    }
                },
            });
        });


        $(document).on('keyup', '.quantity', function () {
            var quntityTotalTaxPrice = 0;

            var el = $(this).parent().parent().parent().parent();

            var quantity = $(this).val();
            var price = $(el.find('.price')).val();
            var discount = $(el.find('.discount')).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }

            var totalItemPrice = (quantity * price) - discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            if(isNaN(totalItemTaxRate)) {
                totalItemTaxRate = 0;
            }
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html((parseFloat(itemTaxPrice)+parseFloat(amount)).toFixed(2));
            // alert("4 - "+$(el.find('.amount')).html());

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));

        })

        $(document).on('keyup change', '.price', function () {
            var el = $(this).parent().parent().parent().parent();
            var price = $(this).val();
            var quantity = $(el.find('.quantity')).val();

            var discount = $(el.find('.discount')).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }
            var totalItemPrice = (quantity * price)-discount;

            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            if(isNaN(totalItemTaxRate)) {
                totalItemTaxRate = 0;
            }
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html((parseFloat(itemTaxPrice)+parseFloat(amount)).toFixed(2));
            // alert("5 - "+$(el.find('.amount')).html());

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }

            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));


        })

        $(document).on('keyup change', '.discount', function () {
            var el = $(this).parent().parent().parent();
            var discount = $(this).val();
            if(discount.length <= 0)
            {
                discount = 0 ;
            }

            var price = $(el.find('.price')).val();
            var quantity = $(el.find('.quantity')).val();
            var totalItemPrice = (quantity * price) - discount;


            var amount = (totalItemPrice);


            var totalItemTaxRate = $(el.find('.itemTaxRate')).val();
            if(isNaN(totalItemTaxRate)) {
                totalItemTaxRate = 0;
            }
            var itemTaxPrice = parseFloat((totalItemTaxRate / 100) * (totalItemPrice));
            $(el.find('.itemTaxPrice')).val(itemTaxPrice.toFixed(2));

            $(el.find('.amount')).html((parseFloat(itemTaxPrice)+parseFloat(amount)).toFixed(2));
            // alert("6 - "+$(el.find('.amount')).html());

            var totalItemTaxPrice = 0;
            var itemTaxPriceInput = $('.itemTaxPrice');
            for (var j = 0; j < itemTaxPriceInput.length; j++) {
                totalItemTaxPrice += parseFloat(itemTaxPriceInput[j].value);
            }


            var totalItemPrice = 0;
            var inputs_quantity = $(".quantity");

            var priceInput = $('.price');
            for (var j = 0; j < priceInput.length; j++) {
                totalItemPrice += (parseFloat(priceInput[j].value) * parseFloat(inputs_quantity[j].value));
            }

            var inputs = $(".amount");

            var subTotal = 0;
            for (var i = 0; i < inputs.length; i++) {
                subTotal = parseFloat(subTotal) + parseFloat($(inputs[i]).html());
            }


            var totalItemDiscountPrice = 0;
            var itemDiscountPriceInput = $('.discount');

            for (var k = 0; k < itemDiscountPriceInput.length; k++) {

                totalItemDiscountPrice += parseFloat(itemDiscountPriceInput[k].value);
            }


            $('.subTotal').html(totalItemPrice.toFixed(2));
            $('.totalTax').html(totalItemTaxPrice.toFixed(2));

            $('.totalAmount').html((parseFloat(subTotal)).toFixed(2));
            $('.totalDiscount').html(totalItemDiscountPrice.toFixed(2));




        })

        var customerId = '{{$customerId}}';
        if (customerId > 0) {
            $('#customer').val(customerId).change();
        }

        $(document).on('change', '#currency_code', function() {
            var my_currency_symbol = $(this).find(':selected').data("symbol");
            $(".my_currency_symbol").html(my_currency_symbol);
            $("#currency_symbol").val(my_currency_symbol);
            $(".item").change();
        });

    </script>

    <script>
        $(document).on('click', '[data-repeater-delete]', function () {
            $(".price").change();
            $(".discount").change();
        });
    
        $(document).on('click', '#billing_data', function () {
            $("[name='shipping_name']").val($("[name='billing_name']").val());
            $("[name='shipping_country']").val($("[name='billing_country']").val());
            $("[name='shipping_state']").val($("[name='billing_state']").val());
            $("[name='shipping_city']").val($("[name='billing_city']").val());
            $("[name='shipping_phone']").val($("[name='billing_phone']").val());
            $("[name='shipping_zip']").val($("[name='billing_zip']").val());
            $("[name='shipping_address']").val($("[name='billing_address']").val());
        })

    </script>
    
@endpush
@section('content')
    <div class="row">
        {{ Form::open(array('url' => 'proposal','class'=>'w-100')) }}
        <div class="col-12">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="customer-box">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        {{ Form::label('customer_id', __('Customer'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                                    </div>
                                    <a href="#" data-size="lg" data-url="{{ route('customer.create',['redirect_to_proposal' => 1]) }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Customer')}}">
                                        <i class="ti ti-plus"></i>{{__('Add Customer')}}
                                    </a>
                                </div>
                                {{ Form::select('customer_id', $customers,$customerId, array('class' => 'form-control select','id'=>'customer','data-url'=>route('proposal.customer'),'required'=>'required')) }}
                            </div>

                            <div id="customer_detail" class="d-none">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('issue_date', __('Issue Date'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                                        <div class="form-icon-user">
                                            {{Form::date('issue_date',null,array('class'=>'form-control','required'=>'required'))}}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('category_id', __('Category'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                                        {{ Form::select('category_id', $category,null, array('class' => 'form-control select','required'=>'required')) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('proposal_number', __('Proposal Number'),['class'=>'form-label']) }}
                                        <div class="form-icon-user">
                                            <input type="text" class="form-control" value="{{$proposal_number}}" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('currency_code', __('Currency'),['class'=>'form-label']) }} <span id="conversion_rate_span"></span>
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

{{--                                <div class="col-md-6">--}}
{{--                                    <div class="mt-4 form-check custom-checkbox">--}}
{{--                                        <input class="form-check-input" type="checkbox" name="discount_apply" id="discount_apply">--}}
{{--                                        <label class="form-check-label " for="discount_apply">{{__('Discount Apply')}}</label>--}}
{{--                                    </div>--}}
{{--                                </div>--}}


{{--                                <div class="col-md-6">--}}
{{--                                    <div class="form-group">--}}
{{--                                        {{Form::label('sku',__('SKU')) }}--}}
{{--                                        {!!Form::text('sku', null,array('class' => 'form-control','required'=>'required')) !!}--}}
{{--                                    </div>--}}
{{--                                </div>--}}
                                @if(!$customFields->isEmpty())
                                    <div class="col-md-6">
                                        <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                                            @include('customFields.formBuilder')
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <h5 class="mb-4 d-inline-block">{{__('Product & Services')}}</h5>
            <div class="card repeater">
                <div class="py-2 item-section">
                    <div class="d-flex align-items-center float-end me-2">
                        <a href="#" data-repeater-create="" class="mb-2 btn btn-primary" data-bs-toggle="modal" data-target="#add-bank">
                            <i class="ti ti-plus"></i> {{__('Add item')}}
                        </a>
                    </div>

                    <div class="mt-3 card-body">
                    <div class="table-responsive">
                        <table class="table mb-0" data-repeater-list="items">
                            <thead>
                            <tr>
                                <th>{{__('Items')}}<span class="text-danger">*</span></th>
                                <th>{{__('Quantity')}}</th>
                                <th>{{__('Price')}} </th>
                                <th>{{__('Discount')}}</th>
                                <th>{{__('Tax')}} (%)</th>

                                <th class="text-end">{{__('Amount')}} <br>
                                    <small class="text-danger font-weight-bold">{{__('after tax & discount')}}</small>
                                </th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody class="ui-sortable" data-repeater-item>
                            <tr>
                                <td width="25%" class="pt-0 form-group">
                                    {{ Form::select('item', $product_services,'', array('class' => 'form-control item'  ,'data-url'=>route('proposal.product'),'required'=>'required')) }}
                                </td>
                                <td>
                                    <div class="form-group price-input input-group search-form">
                                        {{ Form::text('quantity','', array('class' => 'form-control quantity','required'=>'required','placeholder'=>__('Qty'),'required'=>'required')) }}
                                        <span class="bg-transparent unit input-group-text"></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group price-input input-group search-form">
                                        {{ Form::text('price','', array('class' => 'form-control price','required'=>'required','placeholder'=>__('Price'),'required'=>'required')) }}
                                        <span class="bg-transparent input-group-text"><span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group price-input input-group search-form">
                                        {{ Form::text('discount','', array('class' => 'form-control discount','required'=>'required','placeholder'=>__('Discount'))) }}
                                        <span class="bg-transparent input-group-text"><span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="taxes"></div>
                                            {{ Form::hidden('tax','', array('class' => 'form-control tax')) }}
                                            {{ Form::hidden('itemTaxPrice','', array('class' => 'form-control itemTaxPrice')) }}
                                            {{ Form::hidden('itemTaxRate','', array('class' => 'form-control itemTaxRate')) }}
                                        </div>
                                    </div>
                                </td>

                                <td class="text-end amount">
                                    0.00
                                </td>
                                <td>
                                    <a href="#" class="text-white ti ti-trash repeater-action-btn bg-danger ms-2" data-repeater-delete></a>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div class="form-group">
                                        {{ Form::textarea('description', null, ['class'=>'form-control pro_description','rows'=>'1','placeholder'=>__('Description')]) }}
                                    </div>
                                </td>
                                <td colspan="5"></td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr class="border-none">
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Sub Total')}} (<span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span>)</strong></td>
                                <td class="text-end subTotal">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Discount')}} (<span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span>)</strong></td>
                                <td class="text-end totalDiscount">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td></td>
                                <td><strong>{{__('Tax')}} (<span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span>)</strong></td>
                                <td class="text-end totalTax">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td class="border-none blue-text"><strong>{{__('Total Amount')}} (<span class="my_currency_symbol">{{\Auth::user()->currencySymbol()}}</span>)</strong></td>
                                <td class="border-none text-end totalAmount blue-text"></td>
                                <td></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                </div>
            </div>

            <div class="modal-footer">
                <input type="button" value="{{__('Cancel')}}" onclick="location.href = '{{route("proposal.index")}}';" class="btn btn-light">
                <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
@endsection


