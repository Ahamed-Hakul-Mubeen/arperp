@extends('layouts.admin')

@section('page-title')
    {{__('Manage Workshifts')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Work Shifts')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create assets management')
            <a href="#" data-url="{{ route('work_shift.create') }}" data-ajax-popup="true" data-title="{{__('Create Workshifts')}}" data-size="lg" data-bs-toggle="tooltip" title="{{__('Create')}}" class="btn btn-sm btn-primary">
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
    <div class="col-md-9">
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table datatable">
                        <thead>
                        <tr>
                            <th>{{__('Name')}}</th>
                            <th>{{__('Shift Type')}}</th>
                            <th>{{__('Description')}}</th>
                            <th>{{__('Department')}}</th>
                            <th>{{__('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody class="font-style">
                        @foreach ($workshifts as $workShift) <!-- Replace with your actual variable -->
                            <tr>
                                <!-- Display Name -->
                                <td>{{ $workShift->name }}</td>
                                
                                <!-- Display Shift Type (e.g., 'Regular' or 'Scheduled') -->
                                <td>
                                    @if($workShift->shift_type == 'regular')
                                    <span class="badge bg-success p-2 px-3 rounded">{{ __('Regular') }}</span>
                                    @else
                                    <span class="badge bg-primary p-2 px-3 rounded">{{ __('Scheduled') }}</span>
                                    @endif
                                </td>

                                <td>{{ $workShift->description }}</td>
                                <td>{{ $workShift->department }}</td>
                                <!-- Display Start Time and End Time -->
                                {{-- <td>{{ $workShift->start_time }}</td>
                                <td>{{ $workShift->end_time }}</td> --}}

                                <!-- Action Buttons (Modify according to your requirements) -->
                                <td class="Action">
                                    {{-- @can('edit work shift') --}}
                                    <div class="action-btn bg-primary ms-2">
                                        <a href="#" data-url="{{ URL::to('work_shift/'.$workShift->id.'/edit') }}"  data-size="lg" data-ajax-popup="true" data-title="{{__('Edit Work Shift')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}"><i class="ti ti-pencil text-white"></i></a>
                                    </div>
                                    {{-- @endcan --}}

                                    {{-- @can('delete work shift') --}}
                                    <div class="action-btn bg-danger ms-2">
                                        {!! Form::open(['method' => 'DELETE', 'route' => ['work_shift.destroy', $workShift->id], 'id'=>'delete-form-'.$workShift->id]) !!}
                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('Do you want to Delete This?')}}" data-confirm-yes="document.getElementById('delete-form-{{$workShift->id}}').submit();"><i class="ti ti-trash text-white"></i></a>
                                        {!! Form::close() !!}
                                    </div>
                                    {{-- @endcan --}}

                                    <!-- Add other action buttons if necessary -->
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
