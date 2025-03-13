@extends('layouts.admin')
@section('page-title')
    {{ __('Dashboard') }}
@endsection
@push('script-page')
    <script>
        $(document).ready(function() {
            get_data();
        });

        function get_data() {
            var calender_type = $('#calender_type :selected').val();
            $('#calendar').removeClass('local_calender');
            $('#calendar').removeClass('goggle_calender');
            if (calender_type == undefined) {
                $('#calendar').addClass('local_calender');
            }
            $('#calendar').addClass(calender_type);
            $.ajax({
                url: $("#event_dashboard").val() + "/event/get_event_data",
                method: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    'calender_type': calender_type
                },
                success: function(data) {
                    (function() {
                        var etitle;
                        var etype;
                        var etypeclass;
                        var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'timeGridDay,timeGridWeek,dayGridMonth'
                            },
                            buttonText: {
                                timeGridDay: "{{ __('Day') }}",
                                timeGridWeek: "{{ __('Week') }}",
                                dayGridMonth: "{{ __('Month') }}"
                            },
                            slotLabelFormat: {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false,
                            },
                            themeSystem: 'bootstrap',
                            navLinks: true,
                            droppable: true,
                            selectable: true,
                            selectMirror: true,
                            editable: true,
                            dayMaxEvents: true,
                            handleWindowResize: true,
                            height: 'auto',
                            timeFormat: 'H(:mm)',
                            {{-- events: {!! json_encode($arrEvents) !!}, --}}
                            events: data,
                            locale: '{{ basename(App::getLocale()) }}',
                            dayClick: function(e) {
                                var t = moment(e).toISOString();
                                $("#new-event").modal("show"), $(".new-event--title").val(""),
                                    $(".new-event--start").val(t), $(".new-event--end").val(t)
                            },
                            eventResize: function(event) {
                                var eventObj = {
                                    start: event.start.format(),
                                    end: event.end.format(),
                                };
                            },
                            viewRender: function(t) {
                                e.fullCalendar("getDate").month(), $(".fullcalendar-title")
                                    .html(t.title)
                            },
                            eventClick: function(e, t) {
                                var title = e.title;
                                var url = e.url;

                                if (typeof url != 'undefined') {
                                    $("#commonModal .modal-title").html(title);
                                    $("#commonModal .modal-dialog").addClass('modal-md');
                                    $("#commonModal").modal('show');
                                    $.get(url, {}, function(data) {
                                        $('#commonModal .modal-body').html(data);
                                    });
                                    return false;
                                }
                            }
                        });
                        calendar.render();
                    })();
                }
            });
        }
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('HRM') }}</li>
@endsection
@php
    // dd(date_default_timezone_get());
    use Carbon\Carbon;
    $setting = \App\Models\Utility::settings();

    if (\Auth::user()->type != 'client' && \Auth::user()->type != 'company') {
        $employeeAttendance_clock_in = '00:00:00';
        $employeeAttendance_clock_out = '00:00:00';
        $employeeAttendance_total_break_duration = '00:00:00';
        $count = count($employeeAttendance);
        $totalBreakDurationInSeconds = $employeeAttendance->sum(function ($attendance) {
            if (is_null($attendance->total_break_duration)) {
                return 0; 
            }

            return Carbon::parse($attendance->total_break_duration)->secondsSinceMidnight();
        });

        $total_break = gmdate('H:i:s', $totalBreakDurationInSeconds);
        $employeeAttendance_clock_in = count($employeeAttendance) > 0 && !empty($employeeAttendance[0]->clock_in)
            ? $employeeAttendance[0]->clock_in
            : '00:00:00';
        $employeeAttendance_total_break_duration = count($employeeAttendance) > 0 && !empty($total_break > 0)
            ? $total_break
            : '00:00:00';

        $break_time = $setting['break_time'];
        //dd($employeeAttendance_total_break_duration, $employeeAttendance_clock_in);
        // Find total hour in Office
        $start_date = new DateTime($officeTime['startTime']);
        $since_start = $start_date->diff(new DateTime($officeTime['endTime']));
        $since_start_h = $since_start->h < 10 ? '0' . $since_start->h : $since_start->h;
        $since_start_i = $since_start->i < 10 ? '0' . $since_start->i : $since_start->i;
        // Find total worked hour
        if ($employeeAttendance_clock_in != '00:00:00') {
            $clock_in = new DateTime($employeeAttendance_clock_in);
            $worked_hour = $clock_in->diff(new DateTime(date('H:i:s')));
            $worked_h = $worked_hour->h < 10 ? '0' . $worked_hour->h : $worked_hour->h;
            $worked_i = $worked_hour->i < 10 ? '0' . $worked_hour->i : $worked_hour->i;
            $total_worked_hour = $worked_h . ':' . $worked_i;
        } else {
            $total_worked_hour = '00:00:00';
            $worked_h = '00';
            $worked_i = '00';
        }

        // dd($since_start_h, $since_start_i);

        // Find Real work hour
        $break_duration_arr = explode(":", $employeeAttendance_total_break_duration);
        $real_worked_hour = date('H:i', strtotime($total_worked_hour.' - ' . $break_duration_arr[0] . ' hour, -' . $break_duration_arr[1] . ' minutes'));

        $real_worked_arr = explode(':', $real_worked_hour);
        $real_worked_h = $real_worked_arr[0];
        $real_worked_i = $real_worked_arr[1];

        // Find Balance

        $total_schedule_min = 60 * $since_start_h + $since_start_i;
        // dd($total_schedule_min);
        $total_schedule_work_min = $total_schedule_min - $break_time;

        $total_schedule_work_hour = $total_schedule_work_min / 60;
        $schedule_work_hour_arr = explode('.', $total_schedule_work_hour);

        // dd($schedule_work_hour_arr);

        $schedule_work_h = $schedule_work_hour_arr[0];
        $schedule_work_i = isset($schedule_work_hour_arr[1]) ? ($schedule_work_hour_arr[1] * 60) : 0;
        $schedule_work_h = $schedule_work_h < 10 ? '0' . $schedule_work_h : $schedule_work_h;

        if($schedule_work_i>60)
        {
            $schedule_work_i = substr($schedule_work_i, 0, 2) . '.' . substr($schedule_work_i, -4, 2);
            $schedule_work_i = round($schedule_work_i);
        }

        $schedule_work_i = $schedule_work_i < 10 ? '0' . $schedule_work_i : $schedule_work_i;

        if($schedule_work_h.":".$schedule_work_i > $real_worked_h.":".$real_worked_i)
        {
                $balane_work_hour = date(
                    'H:i',
                    strtotime(
                        $schedule_work_h .
                            ':' .
                            $schedule_work_i .
                            ':00 - ' .
                            $real_worked_h .
                            ' hour, -' .
                            $real_worked_i .
                            ' minutes',
                    ),
                );
                if($employeeAttendance_clock_out == "00:00:00") {
                    $over_time = "00:00";
                } else {
                    $over_time = $employeeAttendance_clock_out;
                }
        }
        else {
            $balane_work_hour = "00:00";
            if($employeeAttendance_clock_out == "00:00:00") {
                $over_time = date(
                        'H:i',
                        strtotime(
                            $real_worked_h .
                                ':' .
                                $real_worked_i .
                                ':00 - ' .
                                $schedule_work_h .
                                ' hour, -' .
                                $schedule_work_i .
                                ' minutes',
                        ),
                    );
            } else {
                $over_time = $employeeAttendance_clock_out;
            }
        }
    }

@endphp
@section('content')
    <style>
        .my-text-bold {
            font-weight: 800;
            font-size: 18px;
        }

        #breakTimer {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
         .form-check {
            padding: 15px;
            margin-bottom: 0px;
            border-radius: 8px;
            display: flex;
            align-items: center;

                }

        .form-check-input {
            margin-right: 10px;
            margin-top: 0;
            cursor: pointer;
        }

        .form-check-label {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }

        .form-check-label i {
            font-size: 24px;
            margin-right: 15px;
        }

        .form-check-label p {
            margin: 0;
            font-size: 12px;
            color: #6c757d;
        }

        .my-text-success
        {
            color: #039e1a;
            font-weight: 700;
        }
        .my-text-danger
        {
            color: #bc0101;
            font-weight: 700;
        }
        .in-out-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 100%;
            background-color: #f9f9f9;
            margin-right: 20px;
        }
        .punch-in-section,.punch-out-section{
            margin-right: 30px
        }

    </style>

    @if (\Auth::user()->type != 'client' && \Auth::user()->type != 'company')
        <div class="row">
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-xxl-12">
                        <div class="card">
                            {{-- <div class="card-header">
                                <h4>{{__('Mark Attandance')}}</h4>
                            </div> --}}
                            <div class="card-body dash-card-body">
                                <h4>Hi {{ \Auth::user()->name }}!</h4>
                                    {{-- {{ __('My Office Time: ' . date("h:i A", strtotime($officeTime['startTime'])) . ' to ' . date("h:i A", strtotime($officeTime['endTime']))) }} --}}
                                    <?php
                                    
                                        if(isset($employeeAttendance[0]->clock_in))
                                        {
                                            if($officeTime['startTime'].":00" >= $employeeAttendance[0]->clock_in)
                                            {
                                                echo '<p class="my-text-success pb-0-5">You clocked in at  '.date("h:i A", strtotime($employeeAttendance[0]->clock_in));
                                                if(isset($employeeAttendance[0]->late))
                                                {
                                                    echo " and you are ";
                                                    $late_arr = explode(":", $employeeAttendance[0]->late);
                                                    if(isset($late_arr[0]) && $late_arr[0] > 0)
                                                    {
                                                        echo $late_arr[0]." hour ";
                                                    }
                                                    if(isset($late_arr[1]) && $late_arr[1] > 0)
                                                    {
                                                        echo $late_arr[1]." minutes ";
                                                    }
                                                    echo "early today !";
                                                }
                                                echo "</p>";
                                            }
                                            else
                                            {
                                                echo '<p class="my-text-danger pb-0-5">You clocked in at  '.date("h:i A", strtotime($employeeAttendance[0]->clock_in));
                                                if(isset($employeeAttendance[0]->late))
                                                {
                                                    echo " and you are ";
                                                    $late_arr = explode(":", $employeeAttendance[0]->late);
                                                    if(isset($late_arr[0]) && $late_arr[0] > 0)
                                                    {
                                                        echo $late_arr[0]." hour ";
                                                    }
                                                    if(isset($late_arr[1]) && $late_arr[1] > 0)
                                                    {
                                                        echo $late_arr[1]." minutes ";
                                                    }
                                                    echo "late today !";
                                                }
                                                echo "</p>";
                                            }
                                        }

                                    ?>
                                    
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex">
                                            <div class="d-flex align-items-center my-3 punch-in-section">
                                              <div class="in-out-icon mr-2">
                                                <svg
                                                  xmlns="http://www.w3.org/2000/svg"
                                                  width="24"
                                                  height="24"
                                                  viewBox="0 0 24 24"
                                                  fill="none"
                                                  stroke="currentColor"
                                                  stroke-width="2"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  class="feather feather-log-in text-success"
                                              
                                                >
                                                  <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                                  <polyline points="10 17 15 12 10 7"></polyline>
                                                  <line x1="15" y1="12" x2="3" y2="12"></line>
                                                </svg>
                                              </div>
                                              <div>
                                                <h6 class="mb-1">
                                                    @php
                                                    if(count($employeeAttendance) > 0 && $employeeAttendance[0]->clock_in != "00:00:00")
                                                    {
                                                        $time = Carbon::createFromFormat('H:i:s', $employeeAttendance[0]->clock_in);

                                                        $formattedTime = $time->format('h:i A');
                                                    }else{
                                                        $formattedTime = __('Not yet');
                                                    }
                                                    @endphp
                                                    {{ $formattedTime }}
                                                </h6>
                                                <p class="text-secondary mb-1">Punch in time</p>
                                              </div>
                                            </div>
                                            <div class="d-flex align-items-center my-3 punch-out-section">
                                              <div class="in-out-icon">
                                                <svg
                                                  xmlns="http://www.w3.org/2000/svg"
                                                  width="24"
                                                  height="24"
                                                  viewBox="0 0 24 24"
                                                  fill="none"
                                                  stroke="currentColor"
                                                  stroke-width="2"
                                                  stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  class="feather feather-log-out text-warning"
                                              
                                                >
                                                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                  <polyline points="16 17 21 12 16 7"></polyline>
                                                  <line x1="21" y1="12" x2="9" y2="12"></line>
                                                </svg>
                                              </div>
                                              <div>
                                                <h6 class="mb-1">
                                                    @php
                                                    if(count($employeeAttendance) > 0 && $employeeAttendance[$count - 1]->clock_out != "00:00:00")
                                                    {
                                                        $time = Carbon::createFromFormat('H:i:s', $employeeAttendance[$count - 1]->clock_out);

                                                        $formattedTime = $time->format('h:i A');
                                                    }else{
                                                        $formattedTime = __('Not yet');
                                                    }
                                                    @endphp
                                                    {{ $formattedTime }}    
                                                </h6>
                                                <p class="text-secondary mb-1">Punch out time</p>
                                              </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-7">
                                        <div class="row">
                                            <div class="text-center col-sm-3 col-lg-3 col-6">
                                                <p class="my-text-bold">Worked</p>
                                                <p class="time"> {{ $real_worked_hour }}</p>
                                            </div>
                                            <div class="text-center col-sm-3 col-lg-3 col-6">
                                                <p class="my-text-bold">Break</p>
                                                <p class="time">
                                                    {{ date('H:i', strtotime($employeeAttendance_total_break_duration)) }}
                                                </p>
                                            </div>
                                            <div class="text-center col-sm-3 col-lg-3 col-6">
                                                <p class="my-text-bold">Balance</p>
                                                <p class="time">{{ $balane_work_hour }}</p>
                                            </div>
                                            <div class="text-center col-sm-3 col-lg-3 col-6 d-none">
                                                <p class="my-text-bold">Overtime</p>
                                                <p class="time">
                                                    {{ $over_time }}
                                                </p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-sm-5">
                                        <div class="d-flex justify-content-end">

                                            {{-- {{ Form::open(['url' => 'attendanceemployee/attendance', 'method' => 'post']) }}
                                            @if (empty($employeeAttendance) || $employeeAttendance->clock_out != '00:00:00')
                                                <button type="submit" value="0" name="in" id="clock_in"
                                                    class="mt-2 btn btn-success ">{{ __('CLOCK IN') }}</button>
                                            @else --}}
                                                {{-- <button type="submit" value="0" name="in" id="clock_in" class="btn btn-success disabled" disabled>{{__('CLOCK IN')}}</button> --}}
                                            {{-- @endif
                                            {{ Form::close() }} --}}
                                            
                                            @if ((count($employeeAttendance) > 0 && $employeeAttendance[$count - 1]->clock_out != '00:00:00') || count($employeeAttendance) == 0)
                                            <!-- Clock In Button that triggers the modal -->
                                            <button type="button" class="mt-2 btn btn-success" id="clock_in" data-bs-toggle="modal" data-bs-target="#clockInModal">
                                                {{ __('CLOCK IN') }}
                                            </button>
                                             @endif

                                            @if (count($employeeAttendance) > 0 && $employeeAttendance[$count - 1]->clock_out == '00:00:00')
                                                {{ Form::model($employeeAttendance, ['route' => ['attendanceemployee.update', $employeeAttendance[$count - 1]->id], 'method' => 'PUT']) }}
                                                <button type="submit" value="1" name="out" id="clock_out"
                                                    class="mt-2 btn btn-danger">{{ __('CLOCK OUT') }}</button>
                                            @else
                                                {{-- <button type="submit" value="1" name="out" id="clock_out" class="btn btn-danger disabled" disabled>{{__('CLOCK OUT')}}</button> --}}
                                            @endif
                                            {{ Form::close() }}

                                            <!-- Take a Break Button -->

                                            @if (count($employeeAttendance) && $employeeAttendance[$count - 1]->clock_out == '00:00:00')
                                                <button id="take_break" class="mt-2 btn btn-warning"
                                                    style="margin-left: 10px;">
                                                    {{ __('TAKE A BREAK') }}
                                                </button>
                                            @else
                                                {{-- <button id="take_break" class="btn btn-warning disabled d-none" disabled>
                                                    {{ __('TAKE A BREAK') }}
                                                </button> --}}
                                            @endif

                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>{{ __('Event') }}</h5>
                                    </div>
                                    <div class="col-lg-6">
                                        @if (isset($setting['google_calendar_enable']) && $setting['google_calendar_enable'] == 'on')
                                            <select class="form-control" name="calender_type" id="calender_type"
                                                onchange="get_data()">
                                                <option value="goggle_calender">{{ __('Google Calender') }}</option>
                                                <option value="local_calender" selected="true">{{ __('Local Calender') }}
                                                </option>
                                            </select>
                                        @endif
                                        <input type="hidden" id="event_dashboard" value="{{ url('/') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id='calendar' class='calendar e-height'></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-12">
                        <div class="card list_card">
                            <div class="card-header">
                                <h4>{{ __('Announcement List') }}</h4>
                            </div>
                            <div class="card-body dash-card-body">
                                <div class="table-responsive">
                                    <table class="table mb-0 table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Title') }}</th>
                                                <th>{{ __('Start Date') }}</th>
                                                <th>{{ __('End Date') }}</th>
                                                <th>{{ __('description') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($announcements as $announcement)
                                                <tr>
                                                    <td>{{ $announcement->title }}</td>
                                                    <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                                    <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>
                                                    <td>{{ $announcement->description }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4">
                                                        <div class="text-center">
                                                            <h6>{{ __('There is no Announcement List') }}</h6>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card list_card">
                            <div class="card-header">
                                <h4>{{ __('Meeting List') }}</h4>
                            </div>
                            <div class="card-body dash-card-body">
                                @if (count($meetings) > 0)
                                    <div class="table-responsive">
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Meeting title') }}</th>
                                                    <th>{{ __('Meeting Date') }}</th>
                                                    <th>{{ __('Meeting Time') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($meetings as $meeting)
                                                    <tr>
                                                        <td>{{ $meeting->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($meeting->date) }}</td>
                                                        <td>{{ \Auth::user()->timeFormat($meeting->time) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="p-2">
                                        {{ __('No meeting scheduled yet.') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __("Today's Not Clock In") }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row g-3 flex-nowrap team-lists horizontal-scroll-cards">
                                    @foreach ($notClockIns as $notClockIn)
                                        @php
                                            $user = $notClockIn->user;
                                            $logo = asset(Storage::url('uploads/avatar/'));
                                            $avatar = !empty($notClockIn->user->avatar)
                                                ? $notClockIn->user->avatar
                                                : 'avatar.png';
                                        @endphp
                                        <div class="col-auto">
                                            <img src="{{ $logo . $avatar }}" alt="">
                                            <p class="mt-2">{{ $notClockIn->name }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="row">
                    <div class="col-md-9">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <h5>{{ __('Event') }}</h5>
                                    </div>
                                    <div class="col-lg-6">

                                        @if (isset($setting['google_calendar_enable']) && $setting['google_calendar_enable'] == 'on')
                                            <select class="form-control" name="calender_type" id="calender_type"
                                                onchange="get_data()">
                                                <option value="goggle_calender">{{ __('Google Calender') }}</option>
                                                <option value="local_calender" selected="true">{{ __('Local Calender') }}
                                                </option>
                                            </select>
                                        @endif
                                        <input type="hidden" id="event_dashboard" value="{{ url('/') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id='calendar' class='calendar'></div>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-3">
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Staff') }}</h5>
                                    <div class="mt-4 row">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-users"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Total Staff') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $countUser + $countClient }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="my-3 col-md-6 col-sm-6 my-sm-0">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Total Employee') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $countUser }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Total Client') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $countClient }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Job') }}</h5>
                                    <div class="mt-4 row">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-award"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Total Jobs') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $activeJob + $inActiveJOb }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="my-3 col-md-6 col-sm-6 my-sm-0">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-check"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Active Jobs') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $activeJob }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-x"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Inactive Jobs') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $inActiveJOb }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5>{{ __('Training') }}</h5>
                                    <div class="mt-4 row">
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-primary">
                                                    <i class="ti ti-users"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Total Training') }}</p>
                                                    <h4 class="mb-0 text-success">{{ $onGoingTraining + $doneTraining }}
                                                    </h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="my-3 col-md-6 col-sm-6 my-sm-0">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-info">
                                                    <i class="ti ti-user"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Trainer') }}</p>
                                                    <h4 class="mb-0 text-info">{{ $countTrainer }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-danger">
                                                    <i class="ti ti-user-check"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Active Training') }}</p>
                                                    <h4 class="mb-0 text-danger">{{ $onGoingTraining }}</h4>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6">
                                            <div class="mb-3 d-flex align-items-start">
                                                <div class="theme-avtar bg-secondary">
                                                    <i class="ti ti-user-minus"></i>
                                                </div>
                                                <div class="ms-2">
                                                    <p class="mb-0 text-sm text-muted">{{ __('Done Training') }}</p>
                                                    <h4 class="mb-0 text-secondary">{{ $doneTraining }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">

                                <h5>{{ __('Announcement List') }}</h5>
                            </div>
                            <div class="card-body" style="min-height: 295px;">
                                <div class="table-responsive">
                                    @if (count($announcements) > 0)
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Title') }}</th>
                                                    <th>{{ __('Start Date') }}</th>
                                                    <th>{{ __('End Date') }}</th>

                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                @foreach ($announcements as $announcement)
                                                    <tr>
                                                        <td>{{ $announcement->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($announcement->start_date) }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($announcement->end_date) }}</td>

                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="p-2">
                                            {{ __('No accouncement present yet.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>{{ __('Meeting schedule') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    @if (count($meetings) > 0)
                                        <table class="table align-items-center">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Title') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Time') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="list">
                                                @foreach ($meetings as $meeting)
                                                    <tr>
                                                        <td>{{ $meeting->title }}</td>
                                                        <td>{{ \Auth::user()->dateFormat($meeting->date) }}</td>
                                                        <td>{{ \Auth::user()->timeFormat($meeting->time) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="p-2">
                                            {{ __('No meeting scheduled yet.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif


     <!-- Break Modal -->
<div class="modal fade" id="breakModal" tabindex="-1" role="dialog" aria-labelledby="breakModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="breakModalLabel">{{ __('Select Break Type') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </button>
            </div>
            <div class="modal-body">
                <form id="breakForm">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="breakType" id="break1" value="morning">
                        <label class="form-check-label" for="break1">{{ __('Morning Break') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="breakType" id="break2" value="lunch">
                        <label class="form-check-label" for="break2">{{ __('Lunch Break') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="breakType" id="break3" value="evening">
                        <label class="form-check-label" for="break3">{{ __('Evening Break') }}</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="startBreak" class="btn btn-primary">{{ __('Start') }}</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
<!-- Break Timer Modal -->
<div class="modal fade" id="breakTimerModal" tabindex="-1" role="dialog" aria-labelledby="breakTimerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="breakTimerModalLabel">{{ __('Break Timer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </button>
            </div>
            <div class="modal-body">
                <div id="breakTimer">00:00:00</div>
            </div>
            <div class="modal-footer">
                <button type="button" id="endBreak" class="btn btn-danger">{{ __('End') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clockInModal" tabindex="-1" aria-labelledby="clockInModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title my-text-bold" id="clockInModalLabel">{{ __('Clock In') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Toggle Button for Work from Home -->
                <div class="form-check1 form-switch">
                    <input class="m-1 form-check-input" type="checkbox" id="workFromHomeToggle">
                    <label class="form-check-label my-text-bold" for="workFromHomeToggle">{{ __('Work from Home') }}</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-success" id="clockInSubmit">{{ __('Clock In') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection
@push('script-page')

<script>
$(document).ready(function() {
    let timerInterval;
    let startTime;


    // Function to start the break timer
    function startBreakTimer() {
        timerInterval = setInterval(function() {
            const now = new Date();
            const elapsed = (now - startTime) / 1000; // in seconds
            const hours = Math.floor(elapsed / 3600);
            const minutes = Math.floor((elapsed % 3600) / 60);
            const seconds = Math.floor(elapsed % 60);
            document.getElementById('breakTimer').innerText =
                ('0' + hours).slice(-2) + ':' +
                ('0' + minutes).slice(-2) + ':' +
                ('0' + seconds).slice(-2);
        }, 1000);
    }


    function initializeTimer() {
        const storedStartTime = localStorage.getItem('breakStartTime');
        if (storedStartTime) {
            startTime = new Date(storedStartTime);
            startBreakTimer();
            $('#take_break').text('ON BREAK');

        }
    }

    $('#breakModal').on('show.bs.modal', function () {
        $('#startBreak').prop('disabled', true);
    });


    $('input[name="breakType"]').change(function() {
        $('#startBreak').prop('disabled', false);
    });


    $('#take_break').click(function() {
        if (localStorage.getItem('breakStartTime')) {
            $('#breakTimerModal').modal('show');
        } else {
            $('#breakModal').modal('show');
        }
    });

    $('#startBreak').click(function() {
        if (!$('input[name="breakType"]:checked').length) {
            alert('Please select a break type.');
            return;
        }

        const breakType = $('input[name="breakType"]:checked').val();
        startTime = new Date();
        localStorage.setItem('breakStartTime', startTime.toISOString());
        $('#breakModal').modal('hide');
        $('#breakTimerModal').modal('show');
        startBreakTimer();

        $('#take_break').text('ON BREAK');
        const formattedStartTime = startTime.toTimeString().split(' ')[0];
        $.post('{{ route("breaks.store") }}',
        {
          employee_id: {{ auth()->user()->id }},
          break_start_time: formattedStartTime,
          break_type: breakType,
          _token: '{{ csrf_token() }}'
        });
    });

    $('#endBreak').click(function() {
        const endTime = new Date();
        clearInterval(timerInterval);
        $('#breakTimerModal').modal('hide');
        $('#take_break').text('TAKE A BREAK');

        const formattedEndTime = endTime.toTimeString().split(' ')[0];

        // Send break end time to the server
        $.post('{{ route("breaks.end") }}',
        {
          employee_id: {{ auth()->user()->id }},
          break_end_time: formattedEndTime,
          _token: '{{ csrf_token() }}'
        });

        // Clear local storage
        localStorage.removeItem('breakStartTime');
        location.reload();
    });

    $('#clock_out').click(function(event) {
        const breakStartTime = localStorage.getItem('breakStartTime');
        if (breakStartTime) {
            event.preventDefault();
            alert('Please end your break before clocking out.');
            return false;
        }


    });
    $('#breakModal').on('hidden.bs.modal', function () {
        $('#breakForm')[0].reset(); // Reset form fields
        $('#startBreak').prop('disabled', true); // Disable the start button
    });


    initializeTimer();
});
$(function() {
    var employeeId = '{{ $emp->id ?? "" }}'; // Pass the employee ID to JavaScript

        $('#clockInSubmit').click(function() {
            $('<form>', {
                method: 'POST',
                action: '{{ url("attendanceemployee/attendance") }}'
            }).append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: '{{ csrf_token() }}'
            })).append($('<input>', {
                type: 'hidden',
                name: 'employee_id',
                value: employeeId
            })).append($('<input>', {
                type: 'hidden',
                name: 'date',
                value: '{{ date("Y-m-d") }}'
            })).append($('<input>', {
                type: 'hidden',
                name: 'clock_in',
                value: '{{ date("H:i:s") }}'
            })).append($('<input>', {
                type: 'hidden',
                name: 'work_from_home',
                value: $('#workFromHomeToggle').is(':checked') ? 1 : 0
            })).appendTo('body').submit();
        });
    });
</script>
@endpush
