@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Bug Report') }}
@endsection


@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Project') }}</li>
    <li class="breadcrumb-item">{{ __('Bug Report') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">

        @if ($view == 'grid')
            <a href="{{ route('bugs.view', 'list') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
                title="{{ __('List View') }}">
                <span class="btn-inner--text"><i class="ti ti-list"></i></span>
            </a>
        @else
            <a href="{{ route('bugs.view', 'grid') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
                title="{{ __('Card View') }}">
                <span class="btn-inner--text"><i class="ti ti-table"></i></span>
            </a>
        @endif

        @can('manage project')
            <a href="{{ route('projects.index') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
                title="{{ __('Back') }}">
                <span class="btn-inner--icon"><i class="ti ti-arrow-left"></i></span>
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
                    {{ Form::open(array('route' => array('bugs.view' , $view),'method' => 'GET','id'=>'frm_submit')) }}
                    <div class="d-flex align-items-center justify-content-end">
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="name">Name</label>
                                    <input type="text" placeholder="Enter Name" class="form-control" name="name" value="{{isset($_GET['name'])?$_GET['name']:''}}">
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="status">Status</label>
                                    <select class="form-control" name="status" id="status">
                                        <option value="">Select Status</option>
                                        @if(count($bugStatus) > 0)
                                        @foreach ($bugStatus as $status )
                                            <option value="{{$status->id}}" {{isset($_GET['status'])?($_GET['status'] == $status->id ? 'selected'  : ''):''}}>{{$status->title}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 me-2">
                            <div class="btn-box form-group">
                                <label for="priority">Priority</label>
                                    <select class="form-control" name="priority" id="priority">
                                        <option value="">Select Priority</option>
                                        @if(count($priority) > 0)
                                        @foreach ($priority as $k=> $pri )
                                            <option value="{{$k}}" {{isset($_GET['priority'])?($_GET['priority'] == $k ? 'selected'  : ''):''}}>{{$pri}}</option>
                                        @endforeach
                                        @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-auto float-end ms-2 ">

                            <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('frm_submit').submit(); return false;" data-bs-toggle="tooltip" data-original-title="{{__('apply')}}">
                                <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                            </a>
                            <a href="{{ route('bugs.view',$view) }}" class="btn btn-sm btn-danger" data-bs-toggle="tooltip"
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
                        <table class="table align-items-center">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('Name') }}</th>
                                    <th scope="col">{{ __('Bug Status') }}</th>
                                    <th scope="col">{{ __('Priority') }}</th>
                                    <th scope="col">{{ __('End Date') }}</th>
                                    <th scope="col">{{ __('created By') }}</th>
                                    <th scope="col">{{ __('Assigned To') }}</th>
                                    <th scope="col"></th>
                                </tr>
                            </thead>
                            <tbody class="list">

                                @if (count($bugs) > 0)
                                    @foreach ($bugs as $bug)
                                        @php
                                            $checkProject = \Auth::user()->checkProject($bug->project_id);
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="h6 text-sm font-weight-bold mb-0"><a
                                                        href="{{ route('task.bug', $bug->project_id) }}">{{ $bug->title }}</a></span>
                                                <span class="d-flex text-sm text-muted justify-content-between">
                                                    <p class="m-0">
                                                        {{ !empty($bug->project) ? $bug->project->project_name : '' }}</p>
                                                    <span
                                                        class="me-5 badge p-2 px-3 rounded bg-{{ $checkProject == 'Owner' ? 'success' : 'warning' }}">{{ __($checkProject) }}</span>
                                                </span>
                                            </td>
                                            <td>{{ $bug->bug_status->title }}</td>
                                            <td>
                                                <span
                                                    class="status_badge badge p-2 px-3 rounded bg-{{ __(\App\Models\ProjectTask::$priority_color[$bug->priority]) }}">{{ __(\App\Models\ProjectTask::$priority[$bug->priority]) }}</span>
                                            </td>
                                            <td class="{{ strtotime($bug->due_date) < time() ? 'text-danger' : '' }}">
                                                {{ Utility::getDateFormated($bug->due_date) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    {{ $bug->createdBy->name }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="avatar-group">
                                                    @php
                                                    $user = $bug->users();
                                                    @endphp
                                                    @if ($user->count() > 0)

                                                        <a href="#" class="avatar rounded-circle avatar-sm">
                                                            <img data-original-title="{{ !empty($user[0]) ? $user[0]->name : '' }}"
                                                                @if ($user[0]->avatar) src="{{ asset('/storage/uploads/avatar/' . $user[0]->avatar) }}" @else src="{{ asset('/storage/uploads/avatar/avatar.png') }}" @endif
                                                                title="{{ $user[0]->name }}" class="hweb">
                                                        </a>
                                                        @if ($users = $user)
                                                            @foreach ($users as $key => $user)
                                                                @if ($key < 3)
                                                                @else
                                                                @break
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                    @if (count($users) > 3)
                                                        <a href="#" class="avatar rounded-circle avatar-sm">
                                                            <img src="{{ $user->getImgImageAttribute() }}">
                                                        </a>
                                                    @endif
                                                @else
                                                    {{ __('-') }}
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-end w-15">
                                            <div class="actions">
                                                <a class="action-item px-1" data-bs-toggle="tooltip"
                                                    title="{{ __('Attachment') }}"
                                                    data-original-title="{{ __('Attachment') }}">
                                                    <i class="ti ti-paperclip mr-2"></i>{{ count($bug->bugFiles) }}
                                                </a>
                                                <a class="action-item px-1" data-bs-toggle="tooltip"
                                                    title="{{ __('Comment') }}"
                                                    data-original-title="{{ __('Comment') }}">
                                                    <i class="ti ti-brand-hipchat mr-2"></i>{{ count($bug->comments) }}
                                                </a>
                                                <a class="action-item px-1" data-toggle="tooltip"
                                                    data-original-title="{{ __('Checklist') }}">
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <th scope="col" colspan="7">
                                        <h6 class="text-center">{{ __('No tasks found') }}</h6>
                                    </th>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
