@extends('layouts.admin')
@section('page-title')
    {{__('Employee History')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Employee History')}}</li>
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
                                <th>{{__('Employee ID')}}</th>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Email')}}</th>
                                <th>{{__('Company') }}</th>
                                <th>{{__('Department') }}</th>
                                <th>{{__('Designation') }}</th>
                                <th>{{__('Date Of Joining') }}</th>
                                <th> {{__('Last Login')}}</th>
                                <th width="200px">{{__('Action')}}</th>

                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($employees as $employee)
                                <tr>
                                    <td class="Id">
                                        @can('show employee profile')
                                            <a href="{{route('employee.show',\Illuminate\Support\Facades\Crypt::encrypt($employee->id))}}" class="btn btn-outline-primary">{{ \Auth::user()->employeeIdFormat($employee->employee_id) }}</a>
                                        @else
                                            <a href="#"  class="btn btn-outline-primary">{{ \Auth::user()->employeeIdFormat($employee->employee_id) }}</a>
                                        @endcan
                                    </td>
                                    <td class="font-style">{{ $employee->name }}</td>
                                    <td>{{ $employee->email }}</td>
                                    @if($employee->branch_id)
                                        <td class="font-style">{{$employee->branch  ? $employee->branch->name:''}}</td>
                                    @else
                                        <td>-</td>
                                    @endif
                                    @if($employee->department_id)
                                        <td class="font-style">{{$employee->department ? $employee->department->name:''}}</td>
                                    @else
                                        <td>-</td>
                                    @endif
                                    @if($employee->designation_id)
                                        <td class="font-style">{{$employee->designation ? $employee->designation->name:''}}</td>
                                    @else
                                        <td>-</td>
                                    @endif
                                    @if($employee->company_doj)
                                        <td class="font-style">{{ \Auth::user()->dateFormat($employee->company_doj )}}</td>
                                    @else
                                        <td>-</td>
                                    @endif
                                    <td>
                                        {{ (!empty($employee->user->last_login_at)) ? $employee->user->last_login_at : '-' }}
                                    </td>
                                    @if(Gate::check('edit employee') || Gate::check('delete employee'))
                                        <td>
                                            @if($employee->is_active==1)
                                                @can('edit employee')
                                                <div class="action-btn bg-primary ms-2">
                                                    <a href="{{route('employee.history.view',\Illuminate\Support\Facades\Crypt::encrypt($employee->id))}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('View')}}"
                                                     data-original-title="{{__('View')}}"><i class="ti ti-eye text-white"></i></a>
                                                </div>

                                                @endcan
                                                
                                            @else

                                                <i class="ti ti-lock"></i>
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
</div>
@endsection