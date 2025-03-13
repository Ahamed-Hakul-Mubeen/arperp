@extends('layouts.admin')
@section('page-title')
    {{__('Manage Pre Orders')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Pre Order')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">

        <a href="{{route('pre_order.export')}}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Export')}}">
            <i class="ti ti-file-export"></i>
        </a>

        @can('create preorder')
            <a href="{{ route('pre_order.create',0) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{__('Create')}}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>

@endsection
@push('css-page')

@endpush
@push('script-page')

@endpush
@section('content')

    <div class="row">
        <div class="col-sm-12">
            <div class=" mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                            {{ Form::open(array('route' => array('pre_order.index'),'method' => 'GET','id'=>'frm_submit')) }}
                        <div class="d-flex align-items-center justify-content-end">
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                                <div class="btn-box">
                                    {{ Form::label('issue_date', __('Date'),['class'=>'form-label']) }}
                                    {{ Form::text('issue_date', isset($_GET['issue_date'])?$_GET['issue_date']:null, array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1')) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 ">
                                <div class="btn-box">
                                    {{ Form::label('status', __('Status'),['class'=>'form-label']) }}
                                    {{ Form::select('status', [ ''=>'Select Status'] + $status,isset($_GET['status'])?$_GET['status']:'', array('class' => 'form-control select')) }}
                                </div>
                            </div>
                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2 mt-4">
                                <div class="btn-box form-group mx-2">
                                    <label for="category">Category</label>
                                        <select class="form-control" name="category" id="category">
                                            <option value="">Select Category</option>
                                            @if(count($category) > 0)
                                            @foreach ($category as $k=> $cat )
                                                <option value="{{$k}}" {{isset($_GET['category'])?($_GET['category'] == $k ? 'selected'  : ''):''}}>{{$cat}}</option>
                                            @endforeach
                                            @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto float-end ms-2 mt-4">

                                <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('frm_submit').submit(); return false;" data-bs-toggle="tooltip" data-original-title="{{__('apply')}}">
                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                </a>
                                <a href="{{ route('productservice.index') }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
                                   title="{{ __('Reset') }}">
                                    <span class="btn-inner--icon"><i class="ti ti-trash-off text-white "></i></span>
                                </a>
                            </div>

                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th> {{__('Pre Order')}}</th>
{{--                                @if(!\Auth::guard('customer')->check())--}}
{{--                                    <th> {{__('Customer')}}</th>--}}
{{--                                @endif--}}
                                <th> {{__('Category')}}</th>
                                <th> {{__('Issue Date')}}</th>
                                <th> {{__('Created User')}}</th>
                                <th> {{__('Status')}}</th>
                                @if(Gate::check('edit proposal') || Gate::check('delete proposal') || Gate::check('show proposal'))
                                    <th width="10%"> {{__('Action')}}</th>
                                @endif
                                {{-- <th>
                                    <td class="barcode">
                                        {!! DNS1D::getBarcodeHTML($invoice->sku, "C128",1.4,22) !!}
                                        <p class="pid">{{$invoice->sku}}</p>
                                    </td>
                                </th> --}}
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($preOrders as $preOrder)
                                <tr class="font-style">
                                    <td class="Id">
                                        <a href="{{ route('pre_order.show',\Crypt::encrypt($preOrder->id)) }}" class="btn btn-outline-primary">{{ AUth::user()->preOrderNumberFormat($preOrder->pre_order_id) }}
                                        </a>
                                    </td>

                                    <td>{{ !empty($preOrder->category)?$preOrder->category->name:''}}</td>
                                    <td>{{ Auth::user()->dateFormat($preOrder->issue_date) }}</td>
                                    <td>{{ $preOrder->createdUser ? $preOrder->createdUser->name : '' }}</td>
                                    <td>
                                        @if($preOrder->status == 0)
                                            <span class="status_badge badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$preOrder->status]) }}</span>
                                        @elseif($preOrder->status == 1)
                                            <span class="status_badge badge bg-info p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$preOrder->status]) }}</span>
                                        @elseif($preOrder->status == 2)
                                            <span class="status_badge badge bg-success p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$preOrder->status]) }}</span>
                                        @elseif($preOrder->status == 3)
                                            <span class="status_badge badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$preOrder->status]) }}</span>
                                        @elseif($preOrder->status == 4)
                                            <span class="status_badge badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$preOrder->status]) }}</span>
                                        @endif
                                    </td>
                                    @if(Gate::check('edit preorder') || Gate::check('delete preorder') || Gate::check('show preorder'))
                                        <td class="Action">
                                            @if($preOrder->is_convert==0)
                                                @can('convert invoice')
                                                    <div class="action-btn bg-warning ms-2">
                                                        {!! Form::open(['method' => 'get', 'route' => ['pre_order.convert', $preOrder->id],'id'=>'proposal-form-'.$preOrder->id]) !!}

                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip"
                                                           title="{{__('Convert Bill')}}" data-original-title="{{__('Convert to Invoice')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('You want to confirm convert to invoice. Press Yes to continue or Cancel to go back')}}" data-confirm-yes="document.getElementById('proposal-form-{{$preOrder->id}}').submit();">
                                                            <i class="ti ti-exchange text-white"></i>
                                                            {!! Form::close() !!}
                                                        </a>
                                                    </div>
                                                @endcan
                                            @else
                                                @can('show invoice')
                                                    <div class="action-btn bg-warning ms-2">
                                                        <a href="{{ route('bill.show',\Crypt::encrypt($preOrder->converted_bill_id)) }}"
                                                           class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Already convert to Bill')}}" data-original-title="{{__('Already convert to Bill')}}" >
                                                            <i class="ti ti-file text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                            @endif
                                            @can('duplicate preorder')
                                                <div class="action-btn bg-success ms-2">
                                                    {!! Form::open(['method' => 'get', 'route' => ['pre_order.duplicate', $preOrder->id],'id'=>'duplicate-form-'.$preOrder->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Duplicate')}}" data-original-title="{{__('Duplicate')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('You want to confirm duplicate this invoice. Press Yes to continue or Cancel to go back')}}" data-confirm-yes="document.getElementById('duplicate-form-{{$preOrder->id}}').submit();">
                                                        <i class="ti ti-copy text-white text-white"></i>
                                                        {!! Form::close() !!}
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('show preorder')

                                                    <div class="action-btn bg-info ms-2">
                                                        <a href="{{ route('pre_order.show',\Crypt::encrypt($preOrder->id)) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Show')}}" data-original-title="{{__('Detail')}}">
                                                            <i class="ti ti-eye text-white text-white"></i>
                                                        </a>
                                                    </div>
                                            @endcan
                                            @can('edit preorder')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{ route('pre_order.edit',\Crypt::encrypt($preOrder->id)) }}" class="mx-3 btn btn-sm  align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                        <i class="ti ti-pencil text-white"></i>
                                                    </a>
                                                </div>
                                            @endcan

                                            @can('delete preorder')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['pre_order.destroy', $preOrder->id],'id'=>'delete-form-'.$preOrder->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm  align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$preOrder->id}}').submit();">
                                                        <i class="ti ti-trash text-white text-white"></i>
                                                    </a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </td>
                                    @endif
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
