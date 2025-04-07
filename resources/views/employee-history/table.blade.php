@extends('layouts.admin')
@section('page-title')
{{ __('Employee History of ') }}
{{ $employee && $employee->name ? $employee->name : '' }}
@endsection
@section('breadcrumb')
<li class="breadcrumb-item"><a
        href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
<li class="breadcrumb-item">{{ __('Employee History') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        <a href="{{route('employee.history.view',\Illuminate\Support\Facades\Crypt::encrypt($employee->id))}}" data-size="md"  data-bs-toggle="tooltip" title="{{__('Timeline View')}}" data-ajax-popup="true"  class="btn btn-sm btn-primary">
            <i class="ti ti-vector"></i>
        </a>
        <a href="{{route('employee.history.table.view',\Illuminate\Support\Facades\Crypt::encrypt($employee->id))}}" data-bs-toggle="tooltip" title="{{__('Table View')}}" class="btn btn-sm btn-primary">
            <i class="ti ti-table"></i>
        </a>
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
                                <th>{{__('Title')}}</th>
                                <th>{{__('Date')}}</th>
                                <th>{{__('Description')}}</th>

                            </tr>
                            </thead>
                            <tbody>
                            @if (count($employee_history) > 0)
                            @foreach ($employee_history as $history)
                                <tr>
                                    <td>{{ $history->type }}</td>
                                    <td>{{ date('M d, Y',strtotime($history->created_at)) }}</td>
                                    <td>{{ $history->description }}</td>
                                </tr>


                            @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection