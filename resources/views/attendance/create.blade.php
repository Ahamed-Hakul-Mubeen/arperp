{{Form::open(array('url'=>'attendanceemployee','method'=>'post'))}}
<div class="modal-body">
{{-- <div class="card-body"> --}}
    <div class="row">
        <div class="form-group col-lg-12 col-md-6">
            {{Form::label('employee_id',__('Employee'))}}
            {{Form::select('employee_id',$employees,null,array('class'=>'form-control select2'))}}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('start_date',__('Start Date'))}}
            {{Form::date('start_date',null,array('class'=>'form-control datepicker w-100','max' => \Carbon\Carbon::today()->toDateString()))}}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('end_date',__('End Date'))}}
            {{Form::date('end_date',null,array('class'=>'form-control datepicker w-100','max' => \Carbon\Carbon::today()->toDateString()))}}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('clock_in',__('Clock In'))}}
            {{Form::time('clock_in',null,array('class'=>'form-control'))}}

        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{Form::label('clock_out',__('Clock Out'))}}
            {{Form::time('clock_out',null,array('class'=>'form-control '))}}
        </div>
    </div>
</div>
<div class="pr-0 modal-footer">
    <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{__('Cancel')}}</button>
    {{Form::submit(__('Create'),array('class'=>'btn btn-primary'))}}
</div>
{{Form::close()}}