@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Exchange Rate') }}
@endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Exchange Rate') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('create exchange')
            <a href="#" data-url="{{ route('exchange_rate.create') }}" data-ajax-popup="true"
                data-title="{{ __('Create Currency') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-3">
            @include('layouts.account_setup')
        </div>
        <div class="col-9">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                                <tr>
                                    <th> {{ __('From Currency') }}</th>
                                    <th> {{ __('To Currency') }}</th>
                                    <th> {{ __('Rate') }}</th>
                                    <th width="10%"> {{ __('Action') }}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($exchange_rate as $ex_rate)
                                    <tr class="font-style">
                                        <td>{{ $ex_rate->from_currency }}</td>
                                        <td>{{ $ex_rate->to_currency }}</td>
                                        <td>{{ $ex_rate->exchange_rate }}</td>
                                        <td class="Action">
                                            <span>
                                                @can('edit exchange')
                                                    <div class="action-btn bg-primary ms-2">
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center"
                                                            data-url="{{ route('exchange_rate.edit', $ex_rate->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Edit Currency') }}"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                            data-original-title="{{ __('Edit') }}">
                                                            <i class="text-white ti ti-pencil"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('delete exchange')
                                                    <div class="action-btn bg-danger ms-2">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['exchange_rate.destroy', $ex_rate->id],
                                                            'id' => 'delete-form-' . $ex_rate->id,
                                                        ]) !!}
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            data-original-title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are You Sure?') . '|' . __('This action can not be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $ex_rate->id }}').submit();">
                                                            <i class="text-white ti ti-trash"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
