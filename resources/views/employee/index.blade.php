@extends('layouts.admin')
@section('page-title')
    {{__('Manage Employee')}}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Employee')}}</li>
@endsection


@section('action-btn')
    <div class="float-end">
        <a href="#" data-size="md"  data-bs-toggle="tooltip" title="{{__('Import')}}" data-url="{{ route('employee.file.import') }}" data-ajax-popup="true" data-title="{{__('Import employee CSV file')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-import"></i>
        </a>
        <a href="{{route('employee.export')}}" data-bs-toggle="tooltip" title="{{__('Export')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-file-export"></i>
        </a>
        <a href="{{ route('employee.create') }}"
            data-title="{{ __('Create New Employee') }}" data-bs-toggle="tooltip" title="" class="btn btn-sm btn-primary"
            data-bs-original-title="{{ __('Create') }}">
            <i class="ti ti-plus"></i>
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class=" mt-2 " id="multiCollapseExample1">
            <div class="card">
                <div class="card-body">
                    {{ Form::open(array('route' => array('employee.index'),'method' => 'GET','id'=>'frm_submit')) }}
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="department">Department</label>
                                    <select class="form-control" name="department" id="department">
                                        <option value="">Select Department</option>
                                        @if(count($departments) > 0)
                                        @foreach ($departments as $department )
                                            <option value="{{$department['id']}}" {{isset($_GET['department'])?($_GET['department'] == $department['id'] ? 'selected'  : ''):''}}>{{$department['name']}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="company">Branch</label>
                                    <select class="form-control" name="branch" id="company">
                                        <option value="">Select Branch</option>
                                        @if(count($branches) > 0)
                                        @foreach ($branches as $branch )
                                            <option value="{{$branch['id']}}" {{isset($_GET['branch'])?($_GET['branch'] == $branch['id'] ? 'selected'  : ''):''}}>{{$branch['name']}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="designation">Dsesignation</label>
                                    <select class="form-control" name="designation" id="designation">
                                        <option value="">Select Designation</option>
                                        @if(count($designations) > 0)
                                        @foreach ($designations as $designation )
                                            <option value="{{$designation['id']}}" {{isset($_GET['designation'])?($_GET['designation'] == $designation['id'] ? 'selected'  : ''):''}}>{{$designation['name']}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-auto float-end ms-2 ">

                            <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('frm_submit').submit(); return false;" data-bs-toggle="tooltip" data-original-title="{{__('apply')}}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                            <a href="{{ route('employee.index') }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
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
                                                    <a href="{{route('employee.edit',\Illuminate\Support\Facades\Crypt::encrypt($employee->id))}}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}"
                                                     data-original-title="{{__('Edit')}}"><i class="ti ti-pencil text-white"></i></a>
                                                </div>

                                                    @endcan
                                                @can('delete employee')
                                                <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['employee.destroy', $employee->id],'id'=>'delete-form-'.$employee->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$employee->id}}').submit();"><i class="ti ti-trash text-white"></i></a>
                                                    {!! Form::close() !!}
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
