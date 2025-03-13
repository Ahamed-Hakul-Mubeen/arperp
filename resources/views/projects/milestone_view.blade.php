@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();

    $logo = \App\Models\Utility::get_file('uploads/logo');
    $company_favicon = $setting['company_favicon'] ?? '';
    $color = !empty($setting['color']) ? $setting['color'] : 'theme-3';

    $themeColor = isset($setting['color_flag']) && $setting['color_flag'] == 'true' ? 'custom-color' : $color;
    $SITE_RTL = $setting['SITE_RTL'] ?? '';

    $lang = \App::getLocale();
    if ($lang == 'ar' || $lang == 'he') {
        $SITE_RTL = 'on';
    }
    $company_logo = \App\Models\Utility::GetLogo();

    $metatitle = $setting['meta_title'] ?? '';
    $metsdesc = $setting['meta_desc'] ?? '';
    $meta_image = \App\Models\Utility::get_file('uploads/meta/');
    $meta_logo = $setting['meta_image'] ?? '';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $SITE_RTL == 'on' ? 'rtl' : '' }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="title" content="{{ $metatitle }}">
    <meta name="description" content="{{ $metsdesc }}">


    <title>{{ env('APP_NAME') }}</title>

    <link rel="icon" href="{{ $logo . '/' . ($company_favicon ?: 'favicon.png') }}" type="image" sizes="16x16">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">


    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
    @if ($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @endif
    @if ($setting['cust_darklayout'] == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <style>
        :root {
            --color-customColor: {{ $color }};
        }
    </style>
    @stack('css-page')
</head>

<body>
    <div class="container">
        <div class="mt-2 row">
            <center>
            <div class="col-md-5">
                <div class="m-header main-logo">
                    <a href="#" class="b-brand">
                        @if ($setting['cust_darklayout'] && $setting['cust_darklayout'] == 'on')
                            <img src="{{ $logo . '/' . (isset($company_logos) && !empty($company_logos) ? $company_logos : 'logo-dark.png') }}"
                                alt="{{ config('app.name', 'TZI-SaaS') }}" class="logo w-25">
                        @else
                            <img src="{{ $logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-light.png') }}"
                                alt="{{ config('app.name', 'TZI-SaaS') }}" class="logo w-25">
                        @endif

                    </a>
                </div>
            </div>
        </center>
        </div>
        <div class="mt-5 row justify-content-center">
            <div class="col-md-10">
                <div class="my-3">
                    <h4>Project Name : {{$project->project_name}}</h4>
                </div>
                <div class="mb-4 row">
                    <div class="col-md-6">
                        <span class="font-bold lab-title">{{ __('Status') }} : </span>
                        <span
                            class="badge-xs badge p-2 px-3 rounded bg-{{ \App\Models\Project::$status_color[$milestone->status] }} text-white">{{ __(\App\Models\Project::$project_status[$milestone->status]) }}</span>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-primary" style="float: right;" onclick="window.history.back();"> <i class="fa fa-arrow-left" aria-hidden="true"></i> Back</button>
                    </div>
                    <div class="pt-4 col-md-12">
                        <div class="font-weight-bold lab-title">{{ __('Description') }} :</div>
                        <p class="mt-1 lab-val">{{ !empty($milestone->description) ? $milestone->description : '-' }}
                        </p>
                    </div>
                    <div class="col-12">
                        <div class=" table-border-style">
                            <div class="table-responsive">
                                <table class="table ">
                                    <thead>
                                        <tr>
                                            <th scope="col">{{ __('Name') }}</th>
                                            <th scope="col">{{ __('Stage') }}</th>
                                            <th scope="col">{{ __('Priority') }}</th>
                                            <th scope="col">{{ __('End Date') }}</th>
                                            <th scope="col">{{ __('Completion') }}</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @if (count($milestone->tasks) > 0)
                                            @foreach ($milestone->tasks as $task)
                                                <tr>
                                                    <td>
                                                        <span class="text-sm h6">{{ $task->name }}</span>
                                                    </td>
                                                    <td>{{ $task->stage->name }}</td>
                                                    <td>
                                                        <span
                                                            class="badge p-2 px-3 rounded badge-sm bg-{{ __(\App\Models\ProjectTask::$priority_color[$task->priority]) }}">{{ __(\App\Models\ProjectTask::$priority[$task->priority]) }}</span>
                                                    </td>
                                                    <td
                                                        class="{{ strtotime($task->end_date) < time() ? 'text-danger' : '' }}">
                                                        {{ Utility::getDateFormated($task->end_date) }}</td>
                                                    <td>
                                                        @if (str_replace('%', '', $task->taskProgress($task)['percentage']) > 0)
                                                            <span
                                                                class="text-sm">{{ $task->taskProgress($task)['percentage'] }}</span>
                                                            <div class="progress" style="top:0px">
                                                                <div class="progress-bar bg-{{ $task->taskProgress($task)['color'] }}"
                                                                    role="progressbar"
                                                                    style="width: {{ $task->taskProgress($task)['percentage'] }};">
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="text-end w-15">
                                                        <div class="actions">
                                                            <a class="px-2 action-item" data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Attachment') }}">
                                                                <i
                                                                    class="mr-2 ti ti-paperclip"></i>{{ count($task->taskFiles) }}
                                                            </a>
                                                            <a class="px-2 action-item" data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Comment') }}">
                                                                <i
                                                                    class="mr-2 ti ti-brand-hipchat"></i>{{ count($task->comments) }}
                                                            </a>
                                                            <a class="px-2 action-item" data-bs-toggle="tooltip"
                                                                data-original-title="{{ __('Checklist') }}">
                                                                <i
                                                                    class="mr-2 ti ti-list-check"></i>{{ $task->countTaskChecklist() }}
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
        </div>
    </div>


</body>

</html>
