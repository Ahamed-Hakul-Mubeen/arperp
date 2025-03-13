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
        <div class="row mt-2">
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
        <div class="row justify-content-center mt-4">
            <div class="col-lg-6 col-md-6">
                <div class="my-3">
                    <h4>Project Name : {{$project->project_name}}</h4>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5>{{ __('Milestones') }} ({{ $project->milestones->count() }})</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @if ($project->milestones->count() > 0)
                                @foreach ($project->milestones as $milestone)
                                    <li class="list-group-item px-0">
                                        <div class="row align-items-center justify-content-between">
                                            <div class="col-sm-auto mb-3 mb-sm-0">
                                                <div class="d-flex align-items-center">
                                                    <div class="div">
                                                        <h6 class="m-0">{{ $milestone->title }}
                                                            <span
                                                                class="badge-xs badge bg-{{ \App\Models\Project::$status_color[$milestone->status] }} p-2 px-3 rounded">
                                                                {{ __(\App\Models\Project::$project_status[$milestone->status]) }}
                                                            </span>
                                                        </h6>
                                                        <small
                                                            class="text-muted">{{ $milestone->tasks->count() . ' ' . __('Tasks') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-auto text-sm-end d-flex align-items-center">
                                                <div class="action-btn bg-warning ms-2">
                                                    <a href="{{ route('project.milestone.view', Crypt::encryptString($milestone->id)) }}"
                                                        class="btn btn-sm" data-bs-toggle="tooltip"
                                                        title="{{ __('View') }}">
                                                        <i class="ti ti-eye text-white"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @else
                                <div class="py-5">
                                    <h6 class="h6 text-center">{{ __('No Milestone Found.') }}</h6>
                                </div>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
