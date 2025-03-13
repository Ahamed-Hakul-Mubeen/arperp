@extends('layouts.admin')

@section('page-title')
    {{__('Manage Assets Management')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Asset Management')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create assets management')
            <a href="#" data-url="{{ route('asset_management.create') }}" data-ajax-popup="true" data-title="{{__('Create New Asset')}}" data-bs-toggle="tooltip" title="{{__('Create')}}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        @if (session('status'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {!! session('status') !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="mt-2">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(array('route' => array('asset_management.index'),'method'=>'get','id'=>'asset_management_filter')) }}
                    <div class="row justify-content-end">
                        <!-- Status Filter -->
                        <div class="col-xl-2 col-lg-3 col-md-6 col-sm-12 col-12 p-0">
                            <div class="btn-box">
                                {{ Form::label('asset_status', __('Status'),['class'=>'form-label'])}}
                                {{ Form::select('asset_status', ['' => __('All Status'), 'available' => __('Available'), 'unavailable' => __('Unavailable')], request('asset_status'), array('class' => 'form-control select')) }}
                            </div>
                        </div>
        
                        <!-- Search Input -->
                        <div class="col-auto d-flex align-items-end mb-1">
                            <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('asset_management_filter').submit(); return false;" data-bs-toggle="tooltip" title="{{__('Apply')}}" data-original-title="{{__('apply')}}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                        </div>
                    </div>
                    {{ Form::close() }}
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
                                <th>{{__('Assets Type')}}</th>
                                <th>{{__('Product Name')}}</th>
                                <th>{{__('Configuration')}}</th>
                                <th>{{__('Assigned Employee')}}</th>
                                <th>{{__('Asset Status')}}</th>
                                <th>{{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody class="font-style">
                            @foreach ($assets as $asset)
                                <tr>
                                    <td>{{ $asset->productType->name }}</td>
                                    <td>{{ $asset->product_description }}</td>
                                    <td>{{ $asset->product_configuration }}</td>
                                    <td>
                                        @php
                                            $latestHistory = \App\Models\AssetHistory::where('asset_id', $asset->id)
                                                ->latest()
                                                ->first();
                                        @endphp

                                        @if($latestHistory && $latestHistory->action != 'unassigned')
                                            {{ $latestHistory->employee->name }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if($asset->status == 0)
                                            <span class="badge bg-success p-2 px-3 rounded">{{__('Available')}}</span>
                                        @else
                                            <span class="badge bg-danger p-2 px-3 rounded">{{__('Unavailable')}}</span>
                                        @endif
                                    </td>
                                    <td class="Action">
                                        <div class="action-btn bg-info ms-2">
                                            <a href="#" data-url="{{ route('asset_management.showProperties', $asset->id) }}" data-ajax-popup="true" data-title="{{__('View Properties')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('View Properties')}}">
                                                <i class="ti ti-eye text-white"></i>
                                            </a>
                                        </div>
                                        @if($asset->status == 0)
                                        @can('edit assets management')
                                        <div class="action-btn bg-primary ms-2">
                                            <a href="#" data-url="{{ URL::to('asset_management/'.$asset->id.'/edit') }}" data-ajax-popup="true" data-title="{{__('Edit Asset')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}"><i class="ti ti-pencil text-white"></i></a>
                                        </div>
                                        @endcan
                                        @endif

                                        @can('delete assets management')
                                        <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['asset_management.destroy', $asset->id], 'id'=>'delete-form-'.$asset->id]) !!}
                                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$asset->id}}').submit();"><i class="ti ti-trash text-white"></i></a>
                                        {!! Form::close() !!}
                                        </div>
                                        @endcan

                                        @can('assign assets management')
                                        <div class="action-btn bg-success ms-2">
                                            <a href="#" data-url="{{ route('asset_management.showAssignForm', $asset->id) }}" data-ajax-popup="true" data-title="{{__('Assign Asset')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Assign')}}" data-original-title="{{__('Assign')}}"><i class="ti ti-user-plus text-white"></i></a>
                                        </div>
                                        @endcan

                                       @if($asset['status'] == 1)
                                       @can('transfer assets management')
                                        <div class="action-btn bg-warning ms-2">
                                            <a href="#" data-url="{{ route('asset_management.showtransfer', $asset->id) }}" data-ajax-popup="true" data-title="{{__('Transfer Asset')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Transfer')}}" data-original-title="{{__('Transfer')}}"><i class="fas fa-exchange-alt text-white"></i></a>
                                        </div>
                                       @endcan

                                       @can('unassign assets management')
                                        <div class="action-btn bg-info ms-2">
                                            <a href="#" data-url="{{ route('asset_management.unassignAsset', $asset->id) }}" data-ajax-popup="true" data-title="{{__('Unassign')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Unassign')}}" data-original-title="{{__('Unassign')}}">
                                                <i class="fas fa-unlink text-white"></i>
                                            </a>
                                        </div>
                                        @endcan
                                        
                                        @endif
                                        @can('history assets management')
                                        <div class="action-btn bg-secondary ms-2">
                                            <a href="#" data-url="{{ route('asset_management.history', $asset->id) }}"  data-ajax-popup="true"  data-title="{{__('History')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('History')}}" data-original-title="{{__('History')}}">
                                                <i class="ti ti-clock text-white"></i>
                                            </div>
                                        @endcan    
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