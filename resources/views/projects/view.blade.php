@extends('layouts.admin')
@section('page-title')
    {{ ucwords($project->project_name) }}
@endsection
@push('script-page')
    <script>
        (function() {
            var options = {
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: {
                        enabled: true,
                    },
                },
                colors: ["#ffa21d"],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                },
                series: [{
                    name: 'Bandwidth',
                    data: {{ json_encode(array_map('intval', $project_data['timesheet_chart']['chart'])) }}
                }],

                tooltip: {
                    followCursor: false,
                    fixed: {
                        enabled: false
                    },
                    x: {
                        show: false
                    },
                    y: {
                        title: {
                            formatter: function(seriesName) {
                                return ''
                            }
                        }
                    },
                    marker: {
                        show: false
                    }
                }
            }
            var chart = new ApexCharts(document.querySelector("#timesheet_chart"), options);
            chart.render();
        })();

        (function() {
            var options = {
                chart: {
                    type: 'area',
                    height: 60,
                    sparkline: {
                        enabled: true,
                    },
                },
                colors: ["#ffa21d"],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                },
                series: [{
                    name: 'Bandwidth',
                    data: {{ json_encode($project_data['task_chart']['chart']) }}
                }],

                tooltip: {
                    followCursor: false,
                    fixed: {
                        enabled: false
                    },
                    x: {
                        show: false
                    },
                    y: {
                        title: {
                            formatter: function(seriesName) {
                                return ''
                            }
                        }
                    },
                    marker: {
                        show: false
                    }
                }
            }
            var chart = new ApexCharts(document.querySelector("#task_chart"), options);
            chart.render();
        })();

        $(document).ready(function() {
            loadProjectUser();
            $(document).on('click', '.invite_usr', function() {
                var project_id = $('#project_id').val();
                var user_id = $(this).attr('data-id');

                $.ajax({
                    url: '{{ route('invite.project.user.member') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        'project_id': project_id,
                        'user_id': user_id,
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        if (data.code == '200') {
                            show_toastr(data.status, data.success, 'success')
                            setInterval('location.reload()', 5000);
                            loadProjectUser();
                        } else if (data.code == '404') {
                            show_toastr(data.status, data.errors, 'error')
                        }
                    }
                });
            });
            $(document).on('keyup change','#percentage', function(){
                var total_cost = $(this).attr('total_cost');
                var percentage = $(this).val();
                var cost = 0;
                if(total_cost > 0){
                    cost = (total_cost * percentage) / 100;
                }
                $('#cost').val(cost);
            });
        });

        function loadProjectUser() {
            var mainEle = $('#project_users');
            var project_id = '{{ $project->id }}';

            $.ajax({
                url: '{{ route('project.user') }}',
                data: {
                    project_id: project_id
                },
                beforeSend: function() {
                    $('#project_users').html(
                        '<tr><th colspan="2" class="pt-5 text-center h6">{{ __('Loading...') }}</th></tr>');
                },
                success: function(data) {
                    mainEle.html(data.html);
                    $('[id^=fire-modal]').remove();
                    // loadConfirm();
                }
            });
        }
    </script>

    {{-- share project copy link --}}
    <script>
        function copyToClipboard(element) {

            var copyText = element.id;
            navigator.clipboard.writeText(copyText);
            // document.addEventListener('copy', function (e) {
            //     e.clipboardData.setData('text/plain', copyText);
            //     e.preventDefault();
            // }, true);
            //
            // document.execCommand('copy');
            show_toastr('success', 'Url copied to clipboard', 'success');
        }

        function copyUrlToClipboard(element) {
            var copyText = element.href;
            navigator.clipboard.writeText(copyText);
            show_toastr('success', 'Url copied to clipboard', 'success');
        }
    </script>

    <script type="text/javascript">
        var attachment_file_count = 0;
        $(document).ready(function() {
            $('#add_file_btn_1').click(function() {
                attachment_file_count++;
                console.log(attachment_file_count);

                var add = `<div class="row" id="attachment_div${attachment_file_count}">
                                <div class="col-sm-10">
                                    <div class="form-group">
                                        <input type="file" class="form-control" name="file[]">
                                    </div>
                                </div>
                                <div class="col-sm-2">
                                    <button type="button" onclick=remove_attachment(${attachment_file_count}) class="btn btn-danger btn-sm mt-1"> <i class="text-white ti ti-trash"></i> </button>
                                </div>
                            </div>`;
                $("#attachment_list_div").append(add);
            });
        });

        function remove_attachment(file_index)
        {
            $(`#attachment_div${file_index}`).remove();
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Project') }}</a></li>
    <li class="breadcrumb-item">{{ ucwords($project->project_name) }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('share project')
            {{-- <a href="#" class="btn btn-sm btn-primary" data-ajax-popup="true" data-size="md"
                data-title="{{ __('Shared Project Settings') }}"
                data-url="{{ route('projects.copylink.setting.create', [$project->id]) }}" data-toggle="tooltip"
                title="{{ __('Shared project settings') }}">
                <i class="text-white ti ti-share"></i>
            </a> --}}
            {{--            @php $projectID= Crypt::encrypt($project->id); @endphp --}}
            {{--            <a href="#" id="{{ route('projects.link', \Illuminate\Support\Facades\Crypt::encrypt($project->id)) }}" class="m-1 btn btn-sm btn-primary btn-icon" --}}
            {{--               onclick="copyToClipboard(this)" data-bs-toggle="tooltip" title="{{__('Click to copy link')}}"> --}}
            {{--                <i class="text-white ti ti-link"></i> --}}
            {{--            </a> --}}
        @endcan
        @can('view grant chart')
            <a href="{{ route('projects.gantt', $project->id) }}" class="btn btn-sm btn-primary">
                {{ __('Gantt Chart') }}
            </a>
        @endcan
        {{-- @if (\Auth::user()->type != 'client' || \Auth::user()->type == 'client')
            <a href="{{ route('projecttime.tracker', $project->id) }}" class="btn btn-sm btn-primary">
                {{ __('Tracker') }}
            </a>
        @endif --}}
        @can('view expense')
            <a href="{{ route('projects.expenses.index', $project->id) }}" class="btn btn-sm btn-primary">
                {{ __('Expense') }}
            </a>
        @endcan
        @if (\Auth::user()->type != 'client')
            @can('view timesheet')
                <a href="{{ route('timesheet.index', $project->id) }}" class="btn btn-sm btn-primary">
                    {{ __('Timesheet') }}
                </a>
            @endcan
        @endif
        @can('manage bug report')
            <a href="{{ route('task.bug', $project->id) }}" class="btn btn-sm btn-primary">
                {{ __('Bug Report') }}
            </a>
        @endcan
        @can('create project task')
            <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-sm btn-primary">
                {{ __('Task') }}
            </a>
        @endcan
        @can('edit project')
            <a href="#" data-size="lg" data-url="{{ route('projects.edit', $project->id) }}" data-ajax-popup="true"
                data-bs-toggle="tooltip" title="{{ __('Edit Project') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-pencil"></i>
            </a>
        @endcan


    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-warning">
                                    <i class="ti ti-list"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted h6">{{ __('Total Task') }}</small>
                                    <h6 class="m-0">{{ $project_data['task']['total'] }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ $project_data['task']['done'] }}</h4>
                            <small class="text-muted h6">{{ __('Done Task') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mb-3 mb-sm-0">
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-danger">
                                    <i class="ti ti-report-money"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Total') }}</small>
                                    <h6 class="m-0">{{ __('Budget') }}</h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-end">
                            <h4 class="m-0">{{ \Auth::user()->priceFormat($project->budget) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (Auth::user()->type != 'client')
            <div class="col-lg-4 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center justify-content-between">
                            <div class="col-auto mb-3 mb-sm-0">
                                <div class="d-flex align-items-center">
                                    <div class="theme-avtar bg-primary">
                                        <i class="ti ti-report-money"></i>
                                    </div>
                                    <div class="ms-3">
                                        <small class="text-muted">{{ __('Total') }}</small>
                                        <h6 class="m-0">{{ __('Expense') }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto text-end">
                                <h4 class="m-0">{{ \Auth::user()->priceFormat($project_data['expense']['total']) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-lg-4 col-md-6"></div>
        @endif
        <div class="col-lg-4 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3">
                            <img {{ $project->img_image }} alt="" class="img-user wid-45 rounded-circle">
                        </div>
                        <div class="d-block align-items-center justify-content-between w-100">
                            <div class="mb-3 mb-sm-0">
                                <h5 class="mb-1"> {{ $project->project_name }}</h5>
                                <p class="mb-0 text-sm">
                                    @php
                                        $projectProgress = $project->project_progress($project, $last_task->id)[
                                            'percentage'
                                        ];
                                    @endphp
                                <div class="progress-wrapper">
                                    <span class="progress-percentage"><small
                                            class="font-weight-bold">{{ __('Completed:') }} :
                                        </small>{{ $projectProgress }}</span>
                                    <div class="mt-2 progress progress-xs">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            aria-valuenow="{{ $projectProgress }}" aria-valuemin="0" aria-valuemax="100"
                                            style="width: {{ $projectProgress }};"></div>
                                    </div>
                                </div>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-10">
                            <h4 class="mt-3 mb-1"></h4>
                            <p> {{ $project->description }}</p>
                        </div>
                    </div>
                    <div class="mb-0 card bg-primary">
                        <div class="card-body">
                            <div class="d-block d-sm-flex align-items-center justify-content-between">
                                <div class="row align-items-center">
                                    <span class="text-sm text-white">{{ __('Start Date') }}</span>
                                    <h5 class="text-white text-nowrap">
                                        {{ Utility::getDateFormated($project->start_date) }}</h5>
                                </div>
                                <div class="row align-items-center">
                                    <span class="text-sm text-white">{{ __('End Date') }}</span>
                                    <h5 class="text-white text-nowrap">{{ Utility::getDateFormated($project->end_date) }}
                                    </h5>
                                </div>

                            </div>
                            <div class="row">
                                <span class="text-sm text-white">{{ __('Client') }}</span>
                                <h5 class="text-white text-nowrap">
                                    {{ !empty($project->client) ? $project->client->name : '-' }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="theme-avtar bg-primary">
                            <i class="ti ti-clipboard-list"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 text-muted">{{ __('Last 7 days task done') }}</p>
                            <h4 class="mb-0">{{ $project_data['task_chart']['total'] }}</h4>

                        </div>
                    </div>
                    <div id="task_chart"></div>
                </div>

                <div class="card-body">
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="text-muted">{{ __('Day Left') }}</span>
                        </div>
                        <span>{{ $project_data['day_left']['day'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['day_left']['percentage'] }}%"></div>
                    </div>
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">

                            <span class="text-muted">{{ __('Open Task') }}</span>
                        </div>
                        <span>{{ $project_data['open_task']['tasks'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['open_task']['percentage'] }}%"></div>
                    </div>
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="text-muted">{{ __('Completed Milestone') }}</span>
                        </div>
                        <span>{{ $project_data['milestone']['total'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['milestone']['percentage'] }}%"></div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-lg-4 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="theme-avtar bg-primary">
                            <i class="ti ti-clipboard-list"></i>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 text-muted">{{ __('Last 7 days hours spent') }}</p>
                            <h4 class="mb-0">{{ $project_data['timesheet_chart']['total'] }}</h4>

                        </div>
                    </div>
                    <div id="timesheet_chart"></div>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="text-muted">{{ __('Total project time spent') }}</span>
                        </div>
                        <span>{{ $project_data['time_spent']['total'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['time_spent']['percentage'] }}%"></div>
                    </div>
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">

                            <span class="text-muted">{{ __('Allocated hours on task') }}</span>
                        </div>
                        <span>{{ $project_data['task_allocated_hrs']['hrs'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['task_allocated_hrs']['percentage'] }}%"></div>
                    </div>
                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="text-muted">{{ __('User Assigned') }}</span>
                        </div>
                        <span>{{ $project_data['user_assigned']['total'] }}</span>
                    </div>
                    <div class="mb-3 progress">
                        <div class="progress-bar bg-primary"
                            style="width: {{ $project_data['user_assigned']['percentage'] }}%"></div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5>{{ __('Members') }}</h5>
                        @can('edit project')
                            <div class="float-end">
                                <a href="#" data-size="lg"
                                    data-url="{{ route('users.project.member.create', $project->id) }}"
                                    data-ajax-popup="true" data-bs-toggle="tooltip"
                                    data-bs-original-title="{{ __('Add New Member') }}"
                                    data-title="{{ __('Add New Member') }}" class="btn btn-sm btn-secondary">
                                    <i class="ti ti-plus"></i>
                                </a>
                                <a href="#" data-size="lg"
                                    data-url="{{ route('invite.project.member.view', $project->id) }}" data-ajax-popup="true"
                                    data-bs-toggle="tooltip" title="" class="btn btn-sm btn-primary"
                                    data-bs-original-title="{{ __('Add Member') }}">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush list" id="project_users">
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5>{{ __('Milestones') }} ({{ count($project->milestones) }})</h5>
                        <div class="float-end">
                            @can('create milestone')
                            <a href="#" data-size="lg" data-url="{{ route('project.milestone', $project->id) }}"
                                data-ajax-popup="true" data-bs-toggle="tooltip" title="" 
                                class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create Milestone') }}">
                                <i class="ti ti-plus"></i>
                            </a>
                            @if($completed_milestone_percentage < 100) 
                                
                            @else
                            {{-- <a href="#" data-size="lg" data-bs-toggle="tooltip" title="" 
                                class="btn btn-sm btn-primary" data-bs-original-title="{{ __('100% Completed') }}">
                                <i class="ti ti-plus"></i>
                            </a> --}}
                            @endif
                            @endcan
                            @can('share milestone')
                                <a href="{{ route('project.milestone.share', Crypt::encryptString($project->id)) }}"
                                    target="_blank" data-bs-toggle="tooltip" onclick="copyUrlToClipboard(this)"
                                    title="" class="btn btn-sm btn-primary"
                                    data-bs-original-title="{{ __('Share Milestone') }}">
                                    <i class="ti ti-share"></i>
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @if ($project->milestones->count() > 0)
                            @foreach ($project->milestones as $milestone)
                                <li class="px-0 list-group-item">
                                    <div class="row align-items-center justify-content-between">
                                        <div class="mb-3 col-sm-auto mb-sm-0">
                                            <div class="d-flex align-items-center">
                                                <div class="div">
                                                    <h6 class="m-0">{{ $milestone->title }}</h6>
                                                    <small
                                                        class="text-muted">{{ $milestone->tasks->count() . ' ' . __('Tasks') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-auto text-sm-end d-flex align-items-center">
                                            @can('view milestone')
                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="#" data-size="lg"
                                                        data-url="{{ route('project.milestone.show', $milestone->id) }}"
                                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                                        title="{{ __('View') }}" class="btn btn-sm">
                                                        <i class="text-white ti ti-eye"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('edit milestone')
                                                <div class="action-btn bg-info ms-2">
                                                    <a href="#" data-size="lg"
                                                        data-url="{{ route('project.milestone.edit', $milestone->id) }}"
                                                        data-ajax-popup="true" data-bs-toggle="tooltip"
                                                        title="{{ __('Edit') }}"
                                                        data-title="{{ __('Edit Milestone') }}"class="btn btn-sm">
                                                        <i class="text-white ti ti-pencil"></i>
                                                    </a>
                                                </div>
                                            @endcan
                                            @can('delete milestone')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['project.milestone.destroy', $milestone->id]]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                            class="text-white ti ti-trash"></i></a>

                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan

                                        </div>
                                        <div class="mt-2">

                                            <div class="mb-2 d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <span class="text-muted">
                                                        <span class="badge-xs badge bg-{{ \App\Models\Project::$status_color[$milestone->status] }} p-2 px-3 rounded">{{ __(\App\Models\Project::$project_status[$milestone->status]) }}</span>
                                                    </span>
                                                </div>
                                                <span>{{ $milestone->progress }}%</span>
                                            </div>

                                            <div class="progress">
                                                <div class="progress-bar bg-primary" style="width: {{ $milestone->progress }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        @else
                            <div class="py-5">
                                <h6 class="text-center h6">{{ __('No Milestone Found.') }}</h6>
                            </div>
                        @endif
                    </ul>

                </div>
            </div>
        </div>
        @can('view activity')
            <div class="col-xl-6">
                <div class="card activity-scroll">
                    <div class="card-header">
                        <h5>{{ __('Activity Log') }}</h5>
                        <small>{{ __('Activity Log of this project') }}</small>
                    </div>
                    <div class="card-body vertical-scroll-cards">
                        @foreach ($project->activities as $activity)
                            <div class="p-2 mb-2 card">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="theme-avtar bg-primary">
                                            <i class="ti {{ $activity->logIcon($activity->log_type) }}"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0">{{ __($activity->log_type) }}</h6>
                                            <p class="mb-0 text-sm text-muted">{!! $activity->getRemark() !!}</p>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-sm text-muted">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endcan
        <div class="col-lg-6 col-md-6">
            <div class="card activity-scroll">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5>{{ __('Attachments') }} ({{ $project->projectAttachments()->count() }})</h5>
                        @can('create attachment')
                            <div class="float-end">
                                <a href="#" data-size="lg" data-url="{{ route('project.attachment', $project->id) }}"
                                    data-ajax-popup="true" data-bs-toggle="tooltip" title=""
                                    class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Add Attachment') }}">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                        @endcan
                    </div>
                    {{-- <small>{{ __('Attachment that uploaded in this project') }}</small> --}}
                </div>
                <div class="card-body mt-0 pt-0">
                    <ul class="list-group list-group-flush">
                        @if ($project->projectAttachments()->count() > 0)
                            @foreach ($project->projectAttachments() as $attachment)
                                <li class="px-0 list-group-item">
                                    <div class="row align-items-center justify-content-between mb-1">
                                        <div class="mb-3 col mb-sm-0">
                                            <div class="d-flex align-items-center">
                                                <div class="div w-100">
                                                    <h6 class="m-0 d-flex justify-content-between"><span>{{ $attachment->name }}</span><span>{{ $attachment->created_at->format('Y-m-d') }}</span></h6>
                                                    <small class="text-muted"></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="action-btn bg-warning ms-2">
                                                <a href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="modal" data-bs-target="#attachmentModal_{{ $attachment->id }}"   data-bs-toggle="tooltip" title="{{ __('View') }}">
                                                    <i class="text-white ti ti-eye"></i>
                                                </a>
                                            </div>
                                            @can('delete attachment')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['project.attachment.destroy', $attachment->id]]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                            class="text-white ti ti-trash"></i></a>
                                                    {!! Form::close() !!}
                                                </div>
                                            @endcan
                                        </div>
                                    </div>

                                    <div class="modal fade" id="attachmentModal_{{ $attachment->id }}" tabindex="-1" aria-labelledby="attachmentModalLabel_{{ $attachment->id }}" aria-hidden="true" style="z-index:1050">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="attachmentModalLabel_{{ $attachment->id }}">Attachment Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body pb-0">
                                                    <!-- Attachment files -->
                                                    <?php $attachment_list = json_decode($attachment->file); ?>
                                                    @if ($attachment_list)
                                                        @foreach ($attachment_list as $index => $al)
                                                            <div class="row align-items-center justify-content-between mb-1">
                                                                <div class="mb-3 col mb-sm-0">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="div">
                                                                            <!-- Display the individual attachment file name -->
                                                                            <p class="m-0"><i class="ti ti-file"></i> {{ $al }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-auto">
                                                                    @can('download attachment')
                                                                        <!-- View attachment -->
                                                                        <div class="action-btn bg-warning ms-2">
                                                                            <a href="{{ asset('storage/project/uploads/' . $al) }}" target="_blank" data-bs-toggle="tooltip" title="{{ __('View') }}" class="btn btn-sm">
                                                                                <i class="text-white ti ti-eye"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endcan
                                                                    @can('download attachment')
                                                                        <!-- Download attachment -->
                                                                        <div class="action-btn bg-info ms-2">
                                                                            <a href="{{ asset('storage/project/uploads/' . $al) }}" data-bs-toggle="tooltip" title="{{ __('Download') }}" class="btn btn-sm" download>
                                                                                <i class="text-white ti ti-download"></i>
                                                                            </a>
                                                                        </div>
                                                                    @endcan
                                                                    @can('delete attachment')
                                                                        <!-- Delete individual attachment file -->
                                                                        <div class="action-btn bg-danger ms-2">
                                                                            {!! Form::open(['method' => 'DELETE', 'route' => ['project.attachment.attachmentDestroyFile', $attachment->id, $index]]) !!}
                                                                            <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                                                <i class="text-white ti ti-trash"></i>
                                                                            </a>
                                                                            {!! Form::close() !!}
                                                                        </div>
                                                                    @endcan
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                    <div class="form-group pt-5">
                                                        {{ Form::open(['route' => ['project.attachment.add', $attachment->id], 'files' => true]) }}
                                                        <div class="row">
                                                            <div class="col-sm-12">
                                                                {{ Form::label('file', __('Upload Files'), ['class' => 'form-label']) }}
                                                            </div>
                                                            <div class="col-sm-10">
                                                                <div class="form-group">
                                                                    <input type="file" class="form-control" name="file[]" required>
                                                                </div>
                                                                @error('file')
                                                                    <span class="invalid-file" role="alert">
                                                                        <strong class="text-danger">{{ $message }}</strong>
                                                                    </span>
                                                                @enderror
                                                            </div>
                                                            <div class="col-sm-2">
                                                                <button type="button" id="add_file_btn_1" class="btn btn-info btn-sm mt-1"> <i class="ti ti-plus"></i></button>
                                                            </div>
                                                        </div>
                                                        <div id="attachment_list_div"></div>
                                                        <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                                                        {{ Form::close() }}
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </li>
                            @endforeach
                        @else
                            <div class="py-5">
                                <h6 class="text-center h6">{{ __('No Attachments Found.') }}</h6>
                            </div>
                        @endif
                    </ul>

                </div>
            </div>
        </div>
    </div>
@endsection
