@extends('layouts.admin')

@section('page-title')
    {{__('Manage Leave')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Manage Leave')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create leave')
        <a href="#" data-size="lg" data-url="{{ route('leave.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Leave')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
        @endcan
    </div>
@endsection

@section('content')

<div class="row">
    <div class="col-sm-12">
        <div class=" mt-2 " id="multiCollapseExample1">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(array('route' => array('leave.index'),'method' => 'GET','id'=>'frm_submit')) }}
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="leavetype">Leave Type</label>
                                    <select class="form-control" name="leavetype" id="leavetype">
                                        <option value="">Select Leave Type</option>
                                        @if(count($leavetypes) > 0)
                                        @foreach ($leavetypes as $leavetype )
                                            <option value="{{$leavetype->id}}" {{isset($_GET['leavetype'])?($_GET['leavetype'] == $leavetype->id ? 'selected'  : ''):''}}>{{$leavetype->title}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="startDate">Start Date</label>
                                 <input type="date" name="startDate" class="form-control" value="{{isset($_GET['startDate'])?$_GET['startDate']:''}}"  />
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="endDate">End Date</label>
                                 <input type="date" name="endDate" class="form-control" value="{{isset($_GET['endDate'])?$_GET['endDate']:''}}"  />
                            </div>
                        </div>

                        <div class="col-auto float-end ms-2 ">

                            <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('frm_submit').submit(); return false;" data-bs-toggle="tooltip" data-original-title="{{__('apply')}}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                            <a href="{{ route('leave.index') }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
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
    <div class="col-xl-12">
            <div class="card">
            <div class="card-body table-border-style">
                    <div class="table-responsive">
                    <table class="table datatable">
                            <thead>
                            <tr>
                                <th>{{__('Employee')}}</th>
                                <th>{{__('Leave Type')}}</th>
                                <th>{{__('Applied On')}}</th>
                                <th>{{__('Start Date')}}</th>
                                <th>{{__('End Date')}}</th>
                                <th>{{__('Total Days')}}</th>
                                <th>{{__('Leave Reason')}}</th>
                                <th>{{__('Approval')}}</th>
                                {{-- <th>{{__('HR Approval')}}</th> --}}
                                <th width="200px">{{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($leaves as $leave)
                                <tr>
                                    <td>{{ !empty($leave->employees) ? $leave->employees->name : '-'}}</td>
                                    <td>{{ !empty($leave->leaveType) ? $leave->leaveType->title : '-'}}</td>
                                    <td>{{ \Auth::user()->dateFormat($leave->applied_on )}}</td>
                                    <td>{{ \Auth::user()->dateFormat($leave->start_date ) }}</td>
                                    <td>{{ \Auth::user()->dateFormat($leave->end_date )  }}</td>
                                    <td>{{ $leave->total_leave_days }}</td>
                                    <td>{{ $leave->leave_reason }}</td>
                                    <td>
                                        @if($leave->pm_approval == 'Approved')
                                            <span class="p-2 px-3 rounded status_badge badge bg-success">{{__('Approved')}}</span>
                                        @elseif($leave->pm_approval == 'Rejected')
                                            <span class="p-2 px-3 rounded status_badge badge bg-danger">{{__('Rejected')}}</span>
                                        @else
                                            <span class="p-2 px-3 rounded status_badge badge bg-warning">{{__('Pending')}}</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        @if($leave->hr_approval == 'Approved')
                                            <span class="p-2 px-3 rounded status_badge badge bg-success">{{__('Approved')}}</span>
                                        @elseif($leave->hr_approval == 'Rejected')
                                            <span class="p-2 px-3 rounded status_badge badge bg-danger">{{__('Rejected')}}</span>
                                        @else
                                            <span class="p-2 px-3 rounded status_badge badge bg-warning">{{__('Pending')}}</span>
                                        @endif
                                    </td> --}}
                                    <td>
                                       @can('edit leave')
                                            <div class="action-btn bg-primary ms-2">
                                                <a href="#"
                                                data-url="{{ URL::to('leave/'.$leave->id.'/edit') }}"
                                                data-size="lg"
                                                data-ajax-popup="true"
                                                data-title="{{__('Edit Leave')}}"
                                                class="mx-3 btn btn-sm align-items-center"
                                                data-bs-toggle="tooltip"
                                                title="{{__('Edit')}}">
                                                    <i class="text-white ti ti-pencil"></i>
                                                </a>
                                            </div>
                                        @endcan

                                        {{-- @if($leave->pm_approval == 'Pending' || $leave->hr_approval == 'Pending') --}}
                                            <div class="action-btn bg-warning ms-2">
                                                <a href="#"
                                                data-url="{{ URL::to('leave/'.$leave->id.'/action') }}"
                                                data-size="lg"
                                                data-ajax-popup="true"
                                                data-title="{{__('Leave Action')}}"
                                                class="mx-3 btn btn-sm align-items-center"
                                                data-bs-toggle="tooltip"
                                                title="{{__('Leave Action')}}">
                                                    <i class="text-white ti ti-caret-right"></i>
                                                </a>
                                            </div>
                                        {{-- @endif --}}

                                        @can('delete leave')
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['leave.destroy', $leave->id],'id'=>'delete-form-'.$leave->id]) !!}
                                                <a href="#"
                                                class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                data-bs-toggle="tooltip"
                                                title="{{__('Delete')}}"
                                                data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}"
                                                data-confirm-yes="document.getElementById('delete-form-{{$leave->id}}').submit();">
                                                    <i class="text-white ti ti-trash"></i>
                                                </a>
                                                {!! Form::close() !!}
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

@push('script-page')
    <script>
        $(document).on('change', '#employee_id', function () {
            var employee_id = $(this).val();

            $.ajax({
                url: '{{route('leave.jsoncount')}}',
                type: 'POST',
                data: {
                    "employee_id": employee_id, "_token": "{{ csrf_token() }}",
                },
                success: function (data) {

                    $('#leave_type_id').empty();
                    $('#leave_type_id').append('<option value="">{{__('Select Leave Type')}}</option>');

                    $.each(data, function (key, value) {

                        if (value.total_leave >= value.days) {
                            $('#leave_type_id').append('<option value="' + value.id + '" disabled>' + value.title + '&nbsp(' + value.total_leave + '/' + value.days + ')</option>');
                        } else {
                            $('#leave_type_id').append('<option value="' + value.id + '">' + value.title + '&nbsp(' + value.total_leave + '/' + value.days + ')</option>');
                        }
                    });

                }
            });
        });

    </script>
@endpush
