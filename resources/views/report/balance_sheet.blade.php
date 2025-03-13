@extends('layouts.admin')
@section('page-title')
    {{ __('Balance Sheet') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Balance Sheet') }}</li>
@endsection
@push('script-page')
    <script type="text/javascript" src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
    <script>
        var filename = $('#filename').val();

        function saveAsPDF() {
            var printContents = document.getElementById('printableArea').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
        html2pdf().set(opt).from(element).save();
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#filter").click(function() {
                $("#show_filter").toggle();
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            callback();

            function callback() {
                var start_date = $(".startDate").val();
                var end_date = $(".endDate").val();

                $('.start_date').val(start_date);
                $('.end_date').val(end_date);

            }
        });
    </script>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="#" onclick="saveAsPDF()" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
            title="{{ __('Print') }}" data-original-title="{{ __('Print') }}"><i class="ti ti-printer"></i></a>
    </div>
    <div class="float-end me-2">
        {{ Form::open(['route' => ['balance.sheet.export']]) }}
        <input type="hidden" name="start_date" class="start_date">
        <input type="hidden" name="end_date" class="end_date">
        <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Export') }}"
            data-original-title="{{ __('Export') }}"><i class="ti ti-file-export"></i></button>
        {{ Form::close() }}
    </div>

    <div class="float-end me-2" id="filter">
        <button id="filter" class="btn btn-sm btn-primary"><i class="ti ti-filter"></i></button>
    </div>

    <div class="float-end me-2">
        <a href="{{ route('report.balance.sheet', 'horizontal') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
            title="{{ __('Horizontal View') }}" data-original-title="{{ __('Horizontal View') }}"><i
                class="ti ti-separator-vertical"></i></a>
    </div>
@endsection

@section('content')
    <div class="mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="mt-2" id="multiCollapseExample1">
                    <div class="card" id="show_filter" style="display:none;">
                        <div class="card-body">
                            {{ Form::open(['route' => ['report.balance.sheet'], 'method' => 'GET', 'id' => 'report_balancesheet']) }}
                            <div class="col-xl-12">

                                <div class="row justify-content-between">
                                    <div class="mt-4 col-xl-3">
                                        <div class="btn-group btn-group-toggle" data-toggle="buttons"
                                            aria-label="Basic radio toggle button group">
                                            <label class="btn btn-primary month-label">
                                                <a href="{{ route('report.balance.sheet', ['vertical', 'collapse']) }}"
                                                    class="text-white" id="collapse"> {{ __('Collapse') }} </a>
                                            </label>

                                            <label class="btn btn-primary year-label active">
                                                <a href="{{ route('report.balance.sheet', ['vertical', 'expand']) }}"
                                                    class="text-white"> {{ __('Expand') }} </a>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-xl-9">
                                        <div class="row justify-content-end align-items-center">
                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                                    {{ Form::date('start_date', $filter['startDateRange'], ['class' => 'startDate form-control']) }}
                                                </div>
                                            </div>

                                            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                                <div class="btn-box">
                                                    {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                                    {{ Form::date('end_date', $filter['endDateRange'], ['class' => 'endDate form-control']) }}
                                                </div>
                                            </div>

                                            <div class="col-auto mt-4">
                                                <a href="#" class="btn btn-sm btn-primary"
                                                    onclick="document.getElementById('report_balancesheet').submit(); return false;"
                                                    data-bs-toggle="tooltip" title="{{ __('Apply') }}"
                                                    data-original-title="{{ __('apply') }}">
                                                    <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                                </a>

                                                <a href="{{ route('report.balance.sheet') }}"
                                                    class="btn btn-sm btn-danger " data-bs-toggle="tooltip"
                                                    title="{{ __('Reset') }}" data-original-title="{{ __('Reset') }}">
                                                    <span class="btn-inner--icon"><i
                                                            class="ti ti-trash-off text-white-off "></i></span>
                                                </a>

                                            </div>
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
        @php
            $authUser = \Auth::user()->creatorId();
            $user = App\Models\User::find($authUser);
        @endphp

        <div class="row justify-content-center" id="printableArea">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body {{ $collapseview == 'expand' ? 'collapse-view' : '' }}">
                        <div class="mb-5 account-main-title">
                            <h5>{{ 'Balance Sheet of ' . $user->name . ' as of ' . $filter['startDateRange'] . ' to ' . $filter['endDateRange'] }}
                                </h4>
                        </div>
                        <div
                            class="py-2 aacount-title d-flex align-items-center justify-content-between border-top border-bottom">
                            <h6 class="mb-0">{{ __('Account') }}</h6>
                            <h6 class="mb-0 text-center">{{ _('Account Code') }}</h6>
                            <h6 class="mb-0 text-end">{{ __('Total') }}</h6>
                        </div>
                        @php
                            $totalAmount = 0;
                        @endphp

                        @foreach ($totalAccounts as $type => $accounts)
                            @if ($accounts != [])
                                <div class="py-2 account-main-inner">
                                    @if ($type == 'Liabilities')
                                        <p class="mb-3 fw-bold"> {{ __('Liabilities & Equity') }}</p>
                                    @endif
                                    <p class="mb-2 fw-bold ps-2">{{ $type }}</p>

                                    @php
                                        $total = 0;
                                    @endphp
                                    @foreach ($accounts as $account)
                                        <div class="py-2 border-bottom">
                                            <p class="mb-2 fw-bold ps-4">
                                                {{ $account['subType'] == true ? $account['subType'] : '' }}</p>
                                            @foreach ($account['account'] as $records)
                                                @if ($collapseview == 'collapse')
                                                    @foreach ($records as $key => $record)
                                                        @if ($record['account'] == 'parentTotal')
                                                            <div
                                                                class="account-inner d-flex align-items-center justify-content-between ps-5">
                                                                <div class="mb-2 account-arrow">
                                                                    <div class="">
                                                                        <a
                                                                            href="{{ route('report.balance.sheet', ['vertical', 'expand']) }}"><i
                                                                                class="ti ti-chevron-down account-icon"></i></a>
                                                                    </div>
                                                                    <a href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                                        class="text-primary">{{ str_replace('Total ', '', $record['account_name']) }}</a>
                                                                </div>
                                                                <p class="mb-2 text-center ms-3">
                                                                    {{ $record['account_code'] }}
                                                                </p>
                                                                <p class="mb-2 text-primary float-end text-end">
                                                                    @if ($type == 'Assets')
                                                                        {{ \Auth::user()->priceFormat(-$record['netAmount']) }}
                                                                    @else
                                                                        {{ \Auth::user()->priceFormat($record['netAmount']) }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        @endif

                                                        @if (
                                                            !preg_match('/\btotal\b/i', $record['account_name']) &&
                                                                $record['account'] == '' &&
                                                                $record['account'] != 'subAccount')
                                                            <div
                                                                class="account-inner d-flex align-items-center justify-content-between ps-5">
                                                                <p class="mb-2 ms-3"><a
                                                                        href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                                        class="text-primary">{{ $record['account_name'] }}</a>
                                                                </p>
                                                                <p class="mb-2 text-center">{{ $record['account_code'] }}
                                                                </p>
                                                                <p class="mb-2 text-primary float-end text-end">
                                                                    @if ($type == 'Assets')
                                                                        {{ \Auth::user()->priceFormat(-$record['netAmount']) }}
                                                                    @else
                                                                        {{ \Auth::user()->priceFormat(-$record['netAmount']) }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    @foreach ($records as $key => $record)
                                                        @if ($record['account'] == 'parent' || $record['account'] == 'parentTotal')
                                                            <div
                                                                class="account-inner d-flex align-items-center justify-content-between ps-5">
                                                                @if ($record['account'] == 'parent')
                                                                    <div class="mb-2 account-arrow">
                                                                        <div class="">
                                                                            <a
                                                                                href="{{ route('report.balance.sheet', ['vertical', 'collapse']) }}"><i
                                                                                    class="ti ti-chevron-down account-icon"></i></a>
                                                                        </div>
                                                                        <a href="{{ route('report.ledger', $record['account_id']) }}?account={{ $record['account_id'] }}"
                                                                            class="{{ $record['account'] == 'parent' ? 'text-primary' : 'text-dark' }} fw-bold">{{ $record['account_name'] }}</a>
                                                                    </div>
                                                                @else
                                                                    <p class="mb-2"><a href="#"
                                                                            class="text-dark fw-bold">{{ $record['account_name'] }}</a>
                                                                    </p>
                                                                @endif
                                                                <p class="mb-2 text-center ms-3">
                                                                    {{ $record['account_code'] }}
                                                                </p>
                                                                <p class="mb-2 text-dark fw-bold float-end text-end">
                                                                    @if ($type == 'Assets')
                                                                        {{ \Auth::user()->priceFormat(-$record['netAmount']) }}
                                                                    @else
                                                                        {{ \Auth::user()->priceFormat($record['netAmount']) }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        @endif
                                                        {{-- @if ($key < count($account['account']) - 1) --}}
                                                        @if (
                                                            (!preg_match('/\btotal\b/i', $record['account_name']) && $record['account'] == '') ||
                                                                $record['account'] == 'subAccount')
                                                            <div
                                                                class="account-inner d-flex align-items-center justify-content-between ps-5">
                                                                <p class="mb-2 ms-3">
                                                                    <a href="@if($record['account_name'] != 'Net Income')
                                                                                {{ route('report.ledger', $record['account_id']) . '?account=' . $record['account_id'] }}
                                                                             @else
                                                                                {{ route('report.profit.loss') }}
                                                                             @endif"
                                                                       class="text-primary">
                                                                        {{ $record['account_name'] }}
                                                                    </a>
                                                                </p>
                                                                <p class="mb-2 text-center">{{ $record['account_code'] }}
                                                                </p>
                                                                <p class="mb-2 text-primary float-end text-end">
                                                                    @if ($type == 'Assets')
                                                                        {{ \Auth::user()->priceFormat(-$record['netAmount']) }}
                                                                    @else
                                                                        {{ \Auth::user()->priceFormat($record['netAmount']) }}
                                                                    @endif
                                                                </p>
                                                            </div>
                                                        @endif
                                                        {{-- @endif --}}
                                                    @endforeach
                                                @endif
                                            @endforeach
                                            <div
                                                class="account-inner d-flex align-items-center justify-content-between ps-4">
                                                <p class="mb-2 fw-bold">
                                                    {{ $record['account_name'] ? $record['account_name'] : '' }}
                                                </p>
                                                <p class="mb-2 fw-bold text-end">
                                                    @if ($type == 'Assets')
                                                        {{ $record['netAmount'] ? \Auth::user()->priceFormat(-$record['netAmount']) : \Auth::user()->priceFormat(0) }}
                                                    @else
                                                        {{ $record['netAmount'] ? \Auth::user()->priceFormat($record['netAmount']) : \Auth::user()->priceFormat(0) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        @php
                                            $total += $record['netAmount'] ? $record['netAmount'] : 0;
                                        @endphp
                                    @endforeach
                                    <div
                                        class="px-2 py-2 aacount-title d-flex align-items-center justify-content-between border-top border-bottom pe-0">
                                        <h6 class="mb-0 fw-bold">{{ 'Total for ' . $type }}</h6>
                                        @if ($type == 'Assets')
                                            <h6 class="mb-0 fw-bold text-end">{{ \Auth::user()->priceFormat(-$total) }}</h6>
                                        @else
                                            <h6 class="mb-0 fw-bold text-end">{{ \Auth::user()->priceFormat($total) }}</h6>
                                        @endif
                                    </div>
                                    @php
                                        if ($type != 'Assets') {
                                            $totalAmount += $total;
                                        }
                                    @endphp
                                </div>
                            @endif
                        @endforeach

                        @foreach ($totalAccounts as $type => $accounts)
                            @php
                                if ($type == 'Assets') {
                                    continue;
                                }
                            @endphp

                            @if ($accounts != [])
                                <div
                                    class="px-0 py-2 aacount-title d-flex align-items-center justify-content-between border-bottom">
                                    <h6 class="mb-0 fw-bold">{{ 'Total for Liabilities & Equity' }}</h6>
                                    @if ($type == 'Assets')
                                        <h6 class="mb-0 fw-bold text-end">{{ \Auth::user()->priceFormat(-$totalAmount) }}</h6>
                                    @else
                                        <h6 class="mb-0 fw-bold text-end">{{ \Auth::user()->priceFormat($totalAmount) }}</h6>
                                    @endif
                                </div>
                            @endif
                            @php
                                if ($type == 'Liabilities' || $type == 'Equity') {
                                    break;
                                }
                            @endphp
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script-page')

    <script>
        $(document).on('click', '#collapse', function() {
            view = "collapse";
            $.ajax({
                url: '{{ route('report.balance.sheet', 'vertical') }}',
                type: 'GET',
                data: {
                    "view": view,
                    ,
                },
                success: function(data) {
                    return false;

                    $('#employee_id').empty();
                    $('#employee_id').append('<option value="">{{ __('Select Employee') }}</option>');
                    $('#employee_id').append('<option value="0"> {{ __('All Employee') }} </option>');

                    $.each(data, function(key, value) {
                        $('#employee_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                }
            });
        });
    </script>
@endpush
