@extends('layouts.admin')
@section('page-title')
    {{__('Restrict IP')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Restrict IP')}}</li>
@endsection


@section('action-btn')
<div class="all-button-box row d-flex justify-content-end">
    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6 col-6">
        <a href="#" data-url="{{ route('restrict-ip.create') }}" class="btn btn-sm btn-primary" data-ajax-popup="true" data-title="{{__('Create New Restricted IP')}}">
            <i class="ti ti-plus"></i>
        </a>
        
    </div>
</div>
@endsection

@section('content')

<div class="row">
    <div class="col-xl-12">
        <div class="card">
        <div class="card-body table-border-style">
                    <div class="table-responsive">
                    <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Restricted IP')}}</th>
                                <th>{{__('Created At')}}</th>
                                <th width="200px">{{__('Action')}}</th>

                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($restricted_ip as $ip)
                                <tr>
                                    <td class="Id">
                                        {{ $ip->ip }}
                                    </td>
                                    <td class="font-style">{{ date('M d, Y', strtotime($ip->created_at)) }}</td>
                                    
                                        <td>
                                            <div class="action-btn bg-primary ms-2">
                                                <a href="#" data-url="{{route('restrict-ip.edit',\Illuminate\Support\Facades\Crypt::encrypt($ip->id))}}" class="mx-3 btn btn-sm align-items-center" data-ajax-popup="true" data-title="{{__('Edit Restricted IP')}}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                                {{-- <a href="{{route('restrict-ip.edit',\Illuminate\Support\Facades\Crypt::encrypt($ip->id))}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}"
                                                    data-original-title="{{__('Edit')}}"><i class="ti ti-pencil text-white"></i></a> --}}
                                            </div>

                                            <div class="action-btn bg-danger ms-2">
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['restrict-ip.destroy', $ip->id],'id'=>'delete-form-'.$ip->id]) !!}

                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$ip->id}}').submit();"><i class="ti ti-trash text-white"></i></a>
                                                {!! Form::close() !!}
                                            </div>
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
</div>
@endsection
