@extends('layouts.admin')
@section('page-title')
    {{__('Manage Attendance List')}}
@endsection
@push('script-page')
    <script>
        $('input[name="type"]:radio').on('change', function (e) {
            var type = $(this).val();

            if (type == 'monthly') {
                $('.month').addClass('d-block');
                $('.month').removeClass('d-none');
                $('.date').addClass('d-none');
                $('.date').removeClass('d-block');
            } else {
                $('.date').addClass('d-block');
                $('.date').removeClass('d-none');
                $('.month').addClass('d-none');
                $('.month').removeClass('d-block');
            }
        });

        $('input[name="type"]:radio:checked').trigger('change');
        $(document).on('change', '#department', function () {
            var department_id = $(this).val();
            getEmployee(department_id);
        });

        function getEmployee(did) {
            $.ajax({
                url: '{{route('report.attendance.getemployee')}}',
                type: 'POST',
                data: {
                    "department_id": did, "_token": "{{ csrf_token() }}",
                },
                success: function (data) {
                    console.log(data);
                    $('#employee_id').empty();
                    $("#employee_div").html('');
                    // $('#employee_div').append('<select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>');
                    $('#employee_div').append('<label for="employee" class="form-label">{{__('Employee')}}</label><select class="form-control" id="employee_id" name="employee_id[]"  multiple></select>');
                    $('#employee_id').append('<option value="">{{__('Select Employee')}}</option>');
                    $('#employee_id').append('<option value="0"> {{__('All Employee')}} </option>');

                    $.each(data, function (key, value) {
                        $('#employee_id').append('<option value="' + key + '">' + value + '</option>');
                    });

                    var multipleCancelButton = new Choices('#employee_id', {
                        removeItemButton: true,
                    });
                }
            });
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Attendance')}}</li>
@endsection

{{--@section('action-btn')--}}
{{--    <div class="float-end">--}}
{{--        <a class="btn btn-sm btn-primary" data-bs-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1" data-bs-toggle="tooltip" title="{{__('Filter')}}">--}}
{{--            <i class="ti ti-filter"></i>--}}
{{--        </a>--}}
{{--    </div>--}}
{{--@endsection--}}
@section('action-btn')
<div class="float-end">
    @can('create leave')
    <a href="#" data-size="lg" data-url="{{ route('attendanceemployee.create') }}" data-ajax-popup="true" data-bs-toggle="tooltip" title="{{__('Create')}}" data-title="{{__('Create Attendance')}}" class="btn btn-sm btn-primary">
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
            <div class="mt-2 " id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(array('route' => array('attendanceemployee.index'),'method'=>'get','id'=>'attendanceemployee_filter')) }}
                        <div class="row align-items-center">
                            <div class="mt-2 col-xl-9">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{Form::label('start_date',__('Start Date'),['class'=>'form-label'])}}
                                            {{Form::date('start_date',isset($_GET['start_date'])?$_GET['start_date']:date('Y-m-01'),array('class'=>'month-btn form-control'))}}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{Form::label('end_date',__('End Date'),['class'=>'form-label'])}}
                                            {{Form::date('end_date',isset($_GET['end_date'])?$_GET['end_date']:date('Y-m-d'),array('class'=>'month-btn form-control'))}}
                                        </div>
                                    </div>
                                    {{-- <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 date">
                                        <div class="btn-box">
                                            {{ Form::label('date', __('Date'),['class'=>'form-label'])}}
                                            {{ Form::date('date',isset($_GET['date'])?$_GET['date']:'', array('class' => 'form-control month-btn')) }}
                                        </div>
                                    </div> --}}
                                    @if(\Auth::user()->type != 'Employee')
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('branch', __('Company'),['class'=>'form-label'])}}
                                                {{ Form::select('branch', $branch,isset($_GET['branch'])?$_GET['branch']:'', array('class' => 'form-control select')) }}
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                            <div class="btn-box">
                                                {{ Form::label('department', __('Department'),['class'=>'form-label'])}}
                                                {{ Form::select('department', $department,isset($_GET['department'])?$_GET['department']:'', array('class' => 'form-control select')) }}
                                            </div>
                                        </div>
                                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 col-12 mt-2">
                                            <div class="btn-box" id="employee_div">
                                                {{ Form::label('employee', __('Employee'),['class'=>'form-label']) }}
                                                <select class="form-control select" name="employee_id[]" id="employee_id" placeholder="Select Employee" >
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto">
                                        <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('attendanceemployee_filter').submit(); return false;" data-bs-toggle="tooltip" title="{{__('Apply')}}" data-original-title="{{__('apply')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{route('attendanceemployee.index')}}" class="btn btn-sm btn-danger " data-bs-toggle="tooltip"  title="{{ __('Reset') }}" data-original-title="{{__('Reset')}}">
                                            <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off "></i></span>
                                        </a>
                                        <a href="#" data-size="md"  data-bs-toggle="tooltip" title="{{__('Import')}}" data-url="{{ route('attendance.file.import') }}" data-ajax-popup="true" data-title="{{__('Import employee CSV file')}}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-file-import"></i>
                                        </a>
                                        <a href="{{route('attendance.export', request()->query())}}" data-bs-toggle="tooltip" title="{{__('Export')}}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-file-export"></i>
                                        </a>
                                        <a href="#" onclick="openPrintDialog('{{ route('attendance.print', request()->query()) }}'); return false;" class="btn btn-sm btn-primary">
                                            <i class="ti ti-printer"></i>
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
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                @if(\Auth::user()->type!='Employee')
                                    <th>{{__('Employee')}}</th>
                                @endif
                                <th>{{__('Date')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Clock In')}}</th>
                                <th>{{__('Clock Out')}}</th>
                                <th>{{__('Late')}}</th>
                                <th>{{__('Early Leaving')}}</th>
                                <th>{{__('Overtime')}}</th>
                                <th>{{__('Break Time')}}</th>
                                <th>{{__('Work From Home')}}</th>
                                @if(Gate::check('edit attendance') || Gate::check('delete attendance'))
                                    <th>{{__('Action')}}</th>
                                @endif
                            </tr>
                            </thead>
                            <tbody>
                                       
                            @foreach ($attendanceEmployee as $attendance)
                                <tr>
                                    @if(\Auth::user()->type!='Employee')
                                        <td>{{!empty($attendance->employees)?$attendance->employees->name:'' }}</td>
                                    @endif
                                    <td>{{ \Auth::user()->dateFormat($attendance->date) }}</td>
                                    <td>{{ $attendance->status }}</td>
                                    <td>
                                        {{ ($attendance->clock_in !='00:00:00') ?\Auth::user()->timeFormat( $attendance->clock_in):'00:00' }}
                                    </td>
                                    <td>{{ ($attendance->clock_out !='00:00:00') ?\Auth::user()->timeFormat( $attendance->clock_out):'00:00' }}</td>
                                    <td>{{ $attendance->late }}</td>
                                    <td>{{ $attendance->early_leaving }}</td>
                                    <td>{{ $attendance->overtime }}</td>
                                    <td>{{ $attendance->total_break_duration }}</td>
                                    <td>
                                        @if($attendance->work_from_home == 1)
                                        <div>
                                            <span class="p-2 px-3 rounded badge bg-danger">{{ __('Yes') }}</span>
                                        </div>
                                        @else
                                        <div>
                                            <span class="p-2 px-3 rounded badge bg-primary">{{ __('No') }}</span>
                                        </div>
                                        @endif
                                    </td>
                                    @if(Gate::check('edit attendance') || Gate::check('delete attendance'))
                                        <td>
                                            @can('edit attendance')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="#" data-url="{{ URL::to('attendanceemployee/'.$attendance->id.'/edit') }}" data-size="lg" data-ajax-popup="true" data-title="{{__('Edit Attendance')}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                        <i class="text-white ti ti-pencil"></i></a>
                                                </div>
                                            @endcan
                                            @can('delete attendance')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['attendanceemployee.destroy', $attendance->id],'id'=>'delete-form-'.$attendance->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}"
                                                       data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$attendance->id}}').submit();">
                                                        <i class="text-white ti ti-trash"></i></a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endif
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

@push('script-page')
    <script>
        $(document).ready(function () {
            $('.daterangepicker').daterangepicker({
                format: 'yyyy-mm-dd',
                locale: {format: 'YYYY-MM-DD'},
            });
        });
        function openPrintDialog(url) {
        // Create a new window with the URL for the print page
        const printWindow = window.open(url, '_blank');

        // Once the new window has loaded, trigger the print dialog
        printWindow.onload = function() {
            printWindow.print();
        };
    }
    </script>
@endpush
