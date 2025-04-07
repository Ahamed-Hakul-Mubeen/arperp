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
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body table-border-style">
                @if (count($employee_history) > 0)
                <div class="timeline">
                    <ul>
                        @foreach ($employee_history as $history)
                        <li>
                            <div class="content">
                                <h3>{{ $history->type }}</h3>
                                <p>{{ $history->description }}</p>
                            </div>
                            <div class="time">
                                <h4>{{ date('M d, Y') }}</h4>
                            </div>
                        </li>
                        @endforeach

                        {{-- <li>
                            <div class="content">
                                <h3>What is Lorem Ipsum?</h3>
                                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
                                    Ipsum has been the industry's standard dummy text ever since the 1500s, when an
                                    unknown printer took a galley of type and scrambled it to make a type specimen book.
                                    It has survived not only five centuries, but also the leap into electronic
                                    typesetting, remaining essentially unchanged. </p>
                            </div>
                            <div class="time">
                                <h4>February 2018</h4>
                            </div>
                        </li>

                        <li>
                            <div class="content">
                                <h3>What is Lorem Ipsum?</h3>
                                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem
                                    Ipsum has been the industry's standard dummy text ever since the 1500s, when an
                                    unknown printer took a galley of type and scrambled it to make a type specimen book.
                                    It has survived not only five centuries, but also the leap into electronic
                                    typesetting, remaining essentially unchanged. </p>
                            </div>
                            <div class="time">
                                <h4>March 2018</h4>
                            </div>
                        </li> --}}
                        <div style="clear:both;"></div>
                    </ul>
                </div>
                @else
                <div class="text-center">
                    <h2>{{__('No Employee History Found')}}</h2>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
@push('css-page')
<style>
    .timeline {
        position: relative;
        margin: 50px auto;
        padding: 40px 0;
        width: auto;
        box-sizing: border-box;
    }

    .timeline:before {
        content: '';
        position: absolute;
        left: 50%;
        width: 2px;
        height: 100%;
        background: #c5c5c5;
    }

    .timeline ul {
        padding: 0;
        margin: 0;
    }

    .timeline ul li {
        list-style: none;
        position: relative;
        width: 50%;
        padding: 20px 40px;
        box-sizing: border-box;
    }

    .timeline ul li:nth-child(odd) {
        float: left;
        text-align: right;
        clear: both;
    }

    .timeline ul li:nth-child(even) {
        float: right;
        text-align: left;
        clear: both;
    }

    .content {
        padding-bottom: 20px;
    }

    .timeline ul li:nth-child(odd):before {
        content: '';
        position: absolute;
        width: 10px;
        height: 10px;
        top: 24px;
        right: -6px;
        background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor);;
        color: #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(233, 33, 99, 0.2);
    }

    .timeline ul li:nth-child(even):before {
        content: '';
        position: absolute;
        width: 10px;
        height: 10px;
        top: 24px;
        left: -4px;
        background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor);;
        color: #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(233, 33, 99, 0.2);
    }

    .timeline ul li h3 {
        padding: 0;
        margin: 0;
        color: var(--color-customColor);
        font-weight: 600;
    }

    .timeline ul li p {
        margin: 10px 0 0;
        padding: 0;
    }

    .timeline ul li .time h4 {
        margin: 0;
        color: white;
        padding: 0;
        font-size: 14px;
    }

    .timeline ul li:nth-child(odd) .time {
        position: absolute;
        top: 12px;
        right: -165px;
        margin: 0;
        padding: 8px 16px;
        background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor);
        color: #fff;
        border-radius: 18px;
        box-shadow: 0 0 0 3px rgba(233, 33, 99, 0.3);
    }

    .timeline ul li:nth-child(even) .time {
        position: absolute;
        top: 12px;
        left: -165px;
        margin: 0;
        padding: 8px 16px;
        background: linear-gradient(141.55deg, var(--color-customColor) 3.46%, var(--color-customColor) 99.86%), var(--color-customColor);
        color: #fff;
        border-radius: 18px;
        box-shadow: 0 0 0 3px rgba(233, 33, 99, 0.3);
    }

    @media(max-width:1000px){
        .timeline {
            width: 100%;
        }
    }

    @media(max-width:767px){
        .timeline{
            width: 100%;
            padding-bottom: 0;
        }

        /* h1 {
            font-size: 40px;
            text-align: center;
        } */

        .timeline:before {
            left: 20px;
            height: 100%;
        }

        .timeline ul li:nth-child(odd),
        .timeline ul li:nth-child(even) {
            width: 100%;
            text-align: left;
            padding-left: 50px;
            padding-bottom: 50px;
        }

        .timeline ul li:nth-child(odd):before,
        .timeline ul li:nth-child(even):before {
            top: -18px;
            left: 16px;
        }

        .timeline ul li:nth-child(odd) .time,
        .timeline ul li:nth-child(even) .time {
            top: -30px;
            left: 50px;
            right: inherit;
        }
    }
</style>
@endpush
