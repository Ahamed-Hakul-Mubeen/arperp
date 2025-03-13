@extends('layouts.admin')
@section('page-title')
    {{__('Manage Advance Payments')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Advance')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        {{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}">--}}
        {{--            <i class="ti ti-filter"></i>--}}
        {{--        </a>--}}

        @can('create advance')
            <a href="#" id="createAdvanceLink" data-url="{{ route('advance.create') }}" data-size="lg" data-ajax-popup="true" data-title="{{__('Create New Advance')}}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Create')}}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan

    </div>
@endsection

@push('script-page')
    <script>
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
        <div class="col-sm-12">
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(array('route' => array('advance.index'),'method' => 'GET','id'=>'advance_form')) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-8">
                                <div class="row">

                                    <div class="col-4">
                                        {{Form::label('date',__('Date'),['class'=>'form-label'])}}
                                        {{ Form::text('date', isset($_GET['date'])?$_GET['date']:null, array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1','readonly')) }}

                                    </div>

                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 month">
                                        <div class="btn-box">
                                            {{Form::label('account',__('Account'),['class'=>'form-label'])}}
                                            {{ Form::select('account',$account,isset($_GET['account'])?$_GET['account']:'', array('class' => 'form-control select')) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 date">
                                        <div class="btn-box">
                                            {{ Form::label('customer', __('Customer'),['class'=>'form-label'])}}
                                            {{ Form::select('customer',$customer,isset($_GET['customer'])?$_GET['customer']:'', array('class' => 'form-control select')) }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col-auto mt-4">
                                <div class="row">
                                    <div class="col-auto">

                                        <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('advance_form').submit(); return false;" data-bs-toggle="tooltip" title="{{__('Apply')}}" data-original-title="{{__('apply')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>

                                        <a href="{{route('advance.index')}}" class="btn btn-sm btn-danger " data-bs-toggle="tooltip"  title="{{ __('Reset') }}" data-original-title="{{__('Reset')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off "></i></span>
                                        </a>


                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="mt-2 card-body table-border-style">
                    <h5></h5>
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Advance')}}</th>
                                <th> {{__('Date')}}</th>
                                <th> {{__('Amount')}}</th>
                                <th> {{__('Balance')}}</th>
                                <th> {{__('Account')}}</th>
                                <th> {{__('Customer')}}</th>
                                <th> {{__('Created User')}}</th>
                                {{-- <th> {{__('Reference')}}</th> --}}
                                {{-- <th> {{__('Description')}}</th>
                                <th>{{__('Payment Receipt')}}</th> --}}
                                <th>{{__('Status')}}</th>

                                {{-- @if(Gate::check('edit advance') || Gate::check('delete advance')) --}}
                                    <th width="10%"> {{__('Action')}}</th>
                                {{-- @endif --}}
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $advancepath=\App\Models\Utility::get_file('uploads/advance');
                            @endphp
                            @foreach ($advances as $advance)

                                <tr class="font-style">
                                    <td class="Id">
                                        <a data-url="{{ route('advance.show', $advance->id) }}" data-ajax-popup="true" data-title="{{__('View Advance')}}" href="#" class="btn btn-outline-primary">{{ AUth::user()->advanceNumberFormat($advance->advance_id) }}</a>
                                    </td>
                                    <td>{{  Auth::user()->dateFormat($advance->date)}}</td>
                                    <td>{{  Auth::user()->priceFormat($advance->amount, null, $advance->currency_symbol)}}</td>
                                    <td>{{  Auth::user()->priceFormat($advance->balance, null, $advance->currency_symbol)}}</td>
                                    <td>{{ !empty($advance->bankAccount)?$advance->bankAccount->bank_name.' '.$advance->bankAccount->holder_name:''}}</td>
                                    <td>{{  (!empty($advance->customer)?$advance->customer->name:'-')}}</td>
                                    <td>{{  (!empty($advance->createdUser)?$advance->createdUser->name:'-')}}</td>
                                    {{-- <td>{{  !empty($advance->reference)?$advance->reference:'-'}}</td>
                                    <td>{{  !empty($advance->description)?$advance->description:'-'}}</td> --}}

                                    {{-- <td> --}}

                                        {{-- @if(!empty($advance->add_receipt))
                                            <a  class="action-btn bg-primary ms-2 btn btn-sm align-items-center" href="{{ $advancepath . '/' . $advance->add_receipt }}" download="">
                                                <i class="text-white ti ti-download"></i>
                                            </a>
                                            <a href="{{ $advancepath . '/' . $advance->add_receipt }}"  class="mx-3 action-btn bg-secondary ms-2 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank"><span class="btn-inner--icon"><i class="text-white ti ti-crosshair" ></i></span></a>
                                        @else
                                            -
                                        @endif --}}

                                    {{-- </td> --}}
                                    <td>
                                        @if($advance->status == 0)
                                            <span class="p-2 px-3 rounded status_badge badge bg-secondary">{{ __("Pending") }}</span>
                                        @else
                                            <span class="p-2 px-3 rounded status_badge badge bg-primary">{{ __("Closed") }}</span>
                                        @endif
                                    </td>
                                    {{-- @if(Gate::check('edit advance') || Gate::check('delete advance')) --}}
                                        <td class="Action">
                                            <span>

                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('advance.show', $advance->id) }}" data-ajax-popup="true" data-title="{{__('View Advance')}}" href="#" data-bs-toggle="tooltip" title="{{__('View')}}" data-original-title="{{__('View')}}">
                                                        <i class="text-white ti ti-eye"></i>
                                                    </a>
                                                </div>
                                                @if($advance->status == 0)
                                                    @can('edit advance')
                                                        <div class="action-btn bg-primary ms-2">
                                                            <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ route('advance.edit',$advance->id) }}" data-ajax-popup="true" data-size="lg" data-bs-toggle="tooltip" title="{{__('Edit')}}" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                                <i class="text-white ti ti-pencil"></i>
                                                            </a>
                                                        </div>
                                                    @endcan

                                                    @can('delete advance')
                                                        <div class="action-btn bg-danger ms-2">
                                                            {!! Form::open(['method' => 'DELETE', 'route' => ['advance.destroy', $advance->id],'class'=>'delete-form-btn','id'=>'delete-form-'.$advance->id]) !!}

                                                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$advance->id}}').submit();">
                                                                <i class="text-white ti ti-trash"></i>
                                                            </a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    @endcan
                                                @endif
                                            </span>
                                        </td>
                                    {{-- @endif --}}
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script-page')
<script>
    $(document).ready(function() {
    // Check if the URL contains the query parameter "event=new"
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('event') && urlParams.get('event') === 'new') {
        // Trigger the click event on the specific <a> tag
        $('#createAdvanceLink').trigger('click');
    }
});
</script>
@endpush
