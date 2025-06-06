@extends('layouts.admin')
@section('page-title')
    {{__('Manage Journal Entry')}}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item">{{__('Journal Entry')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('create journal entry')
            <a href="{{ route('journal-entry.create') }}" data-title="{{__('Create New Journal')}}" data-bs-toggle="tooltip"  title="{{__('Create')}}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
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
                    {{ Form::open(array('route' => array('journal-entry.index'),'method' => 'GET','id'=>'transfer_form')) }}
                    <div class="row align-items-center justify-content-end">
                        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12 month">
                            <div class="btn-box">
                                {{ Form::label('date', __('Date'),['class'=>'form-label'])}}
                                {{ Form::text('date', isset($_GET['date'])?$_GET['date']:null, array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1','readonly')) }}

                            </div>
                        </div>

                        <div class="col-auto mt-4">
                            <div class="row">
                                <div class="col-auto">

                                    <a href="#" class="btn btn-sm btn-primary" onclick="document.getElementById('transfer_form').submit(); return false;" data-bs-toggle="tooltip" title="{{__('Apply')}}" data-original-title="{{__('apply')}}">
                                        <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                    </a>

                                    <a href="{{route('journal-entry.index')}}" class="btn btn-sm btn-danger " data-bs-toggle="tooltip"  title="{{ __('Reset') }}" data-original-title="{{__('Reset')}}">
                                        <span class="btn-inner--icon"><i class="ti ti-trash-off text-white-off "></i></span>
                                    </a>


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
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table datatable">
                            <thead>
                            <tr>
                                <th> {{__('Journal ID')}}</th>
                                <th> {{__('Date')}}</th>
                                <th> {{__('Amount')}}</th>
                                <th> {{__('Created User')}}</th>
                                <th> {{__('Description')}}</th>
                                <th> {{__('Attachment')}}</th>
                                <th width="10%"> {{__('Action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $journalpath=\App\Models\Utility::get_file('');
                            @endphp
                            @foreach ($journalEntries as $journalEntry)
                                <tr>
                                    <td class="Id">
                                        <a href="{{ route('journal-entry.show',$journalEntry->id) }}" class="btn btn-outline-primary">{{ AUth::user()->journalNumberFormat($journalEntry->journal_id) }}</a>
                                    </td>
                                    <td>{{ Auth::user()->dateFormat($journalEntry->date) }}</td>
                                    <td>
                                        {{ \Auth::user()->priceFormat($journalEntry->totalCredit())}}
                                    </td>
                                    <td>{{!empty($journalEntry->createdUser)?$journalEntry->createdUser->name:'-'}}</td>
                                    <td>{{!empty($journalEntry->description)?$journalEntry->description:'-'}}</td>
                                    <td>
                                        @if(!empty($journalEntry->attachment))
                                        <a class="action-btn bg-primary ms-2 btn btn-sm align-items-center" href="{{ $journalpath . '/' . $journalEntry->attachment }}" data-bs-toggle="tooltip" title="{{__('Download')}}" download="">
                                            <i class="ti ti-download text-white"></i>
                                        </a>
                                        <a href="{{ $journalpath . '/' . $journalEntry->attachment }}"  class="action-btn bg-secondary ms-2 mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('View')}}" target="_blank"><span class="btn-inner--icon"><i class="ti ti-crosshair text-white" ></i></span></a>
                                        @else
                                            <span class="px-5">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('edit journal entry')
                                            <div class="action-btn bg-primary ms-2">
                                                <a data-title="{{__('Edit Journal')}}" href="{{ route('journal-entry.edit',[$journalEntry->id]) }}" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Edit')}}" data-original-title="{{__('Edit')}}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                            </div>
                                        @endcan
                                        @can('delete journal entry')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => array('journal-entry.destroy', $journalEntry->id),'id'=>'delete-form-'.$journalEntry->id]) !!}

                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip" title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$journalEntry->id}}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                    {!! Form::close() !!}
                                                </div>
                                        @endcan
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
