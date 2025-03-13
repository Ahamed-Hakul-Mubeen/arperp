{{Form::open(array('url'=>'customer','method'=>'post'))}}
@foreach(['redirect_to_invoice', 'redirect_to_proposal','redirect_to_advance','redirect_to_revenue'] as $param)
    @if(request()->has($param) && request()->get($param) == 1)
        <input type="hidden" name="{{ $param }}" value="1">
    @endif
@endforeach
<div class="modal-body">

    <h6 class="sub-title">{{__('Basic Info')}}</h6>
    <div class="row">
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{Form::label('name',__('Name'),array('class'=>'form-label')) }}<span class="text-danger">*</span>
                {{Form::text('name',null,array('class'=>'form-control','required'=>'required' ,'placeholder'=>_('Enter Name')))}}
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{Form::label('contact',__('Contact'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::number('contact',null,array('class'=>'form-control','required'=>'required' , 'placeholder'=>__('Enter Contact')))}}
            </div>
        </div>
        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{Form::label('email',__('Email'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::email('email',null,array('class'=>'form-control' , 'placeholder'=>__('Enter email'),'required'=>'required'))}}

            </div>
        </div>

        <div class="col-lg-4 col-md-4 col-sm-6">
            <div class="form-group">
                {{Form::label('tax_number',__('Tax Number'),['class'=>'form-label'])}}
                {{Form::text('tax_number',null,array('class'=>'form-control' , 'placeholder' => __('Enter Tax Number')))}}
            </div>
        </div>
        @if(!$customFields->isEmpty())
            <div class="col-lg-4 col-md-4 col-sm-6">
                <div class="tab-pane fade show" id="tab-2" role="tabpanel">
                    @include('customFields.formBuilder')
                </div>
            </div>
        @endif
    </div>

    <h6 class="sub-title">{{__('Billing Address')}}</h6>
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_name',__('Name'),array('class'=>'','class'=>'form-label')) }}
                {{Form::text('billing_name',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Name')))}}
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_phone',__('Phone'),array('class'=>'form-label')) }}
                {{Form::text('billing_phone',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Phone')))}}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('billing_address',__('Address'),array('class'=>'form-label')) }}
                {{Form::textarea('billing_address',null,array('class'=>'form-control','rows'=>3 , 'placeholder'=>__('Enter Address')))}}

            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_city',__('City'),array('class'=>'form-label')) }}
                {{Form::text('billing_city',null,array('class'=>'form-control' , 'placeholder'=>__('Enter City')))}}

            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_state',__('State'),array('class'=>'form-label')) }}
                {{Form::text('billing_state',null,array('class'=>'form-control' , 'placeholder' => __('Enter State')))}}
            </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_country',__('Country'),array('class'=>'form-label')) }}
                {{Form::text('billing_country',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Country')))}}
            </div>
        </div>

        <div class="col-lg-6 col-md-6 col-sm-6">
            <div class="form-group">
                {{Form::label('billing_zip',__('Zip Code'),array('class'=>'form-label')) }}
                {{Form::text('billing_zip',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Zip Code')))}}

            </div>
        </div>

    </div>

    @if(App\Models\Utility::getValByName('shipping_display')=='on')
        <div class="col-md-12 text-end">
            <input type="button" id="billing_data" value="{{__('Shipping Same As Billing')}}" class="btn btn-primary">
        </div>
        <h6 class="sub-title">{{__('Shipping Address')}}</h6>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_name',__('Name'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_name',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Name')))}}

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_phone',__('Phone'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_phone',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Phone')))}}

                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {{Form::label('shipping_address',__('Address'),array('class'=>'form-label')) }}
                    <label class="form-label" for="example2cols1Input"></label>
                    {{Form::textarea('shipping_address',null,array('class'=>'form-control','rows'=>3 , 'placeholder'=>__('Enter Address')))}}

                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_city',__('City'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_city',null,array('class'=>'form-control' , 'placeholder'=>__('Enter City')))}}

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_state',__('State'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_state',null,array('class'=>'form-control' , 'placeholder'=>__('Enter State')))}}

                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_country',__('Country'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_country',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Country')))}}

                </div>
            </div>


            <div class="col-lg-6 col-md-6 col-sm-6">
                <div class="form-group">
                    {{Form::label('shipping_zip',__('Zip Code'),array('class'=>'form-label')) }}
                    {{Form::text('shipping_zip',null,array('class'=>'form-control' , 'placeholder' => __('Enter Zip Code')))}}

                </div>
            </div>

        </div>
    @endif

</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{Form::close()}}
