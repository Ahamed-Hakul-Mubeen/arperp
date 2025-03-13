@extends('layouts.admin')
@section('page-title')
    {{__('Manage Assets Type')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Assets Type')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create assets type')
            <a href="#" data-url="{{ route('product_type.create') }}" data-ajax-popup="true" data-title="{{__('Create Assets Product')}}" data-bs-toggle="tooltip" title="{{__('Create')}}"  class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Assets Type')}}</th>
                                <th width="200px">{{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody class="font-style">
                            @foreach ($productTypes as $productType)
                                <tr>
                                    <td>{{ $productType->name }}</td>
                                    <td class="Action text-end">
                                        <span>
                                            @can('edit assets type')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center" data-url="{{ URL::to('product_type/'.$productType->id.'/edit') }}"  data-ajax-popup="true" data-title="{{__('Edit Product Type')}}" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}"><i class="text-white ti ti-pencil"></i></a>
                                                </div>
                                          
                                            @endcan
                                            @can('delete assets type')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['product_type.destroy', $productType->id],'id'=>'delete-form-'.$productType->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$productType->id}}').submit();"><i class="text-white ti ti-trash"></i></a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </span>
                                    </td>
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