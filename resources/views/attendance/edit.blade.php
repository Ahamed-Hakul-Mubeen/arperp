{{Form::model($attendanceEmployee,array('route' => array('attendanceemployee.update', $attendanceEmployee->id), 'method' => 'PUT')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-lg-6 ">
            {{Form::label('employee_id',__('Employee'), ['class' => 'form-label'])}}
            {{Form::select('employee_id',$employees,null,array('class'=>'form-control select'))}}
        </div>
        <div class="form-group col-lg-6 ">
            {{Form::label('date',__('Date'), ['class' => 'form-label'])}}
            {{Form::date('date',null,array('class'=>'form-control'))}}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-lg-6 ">
            {{Form::label('clock_in',__('Clock In'), ['class' => 'form-label'])}}
            {{Form::time('clock_in',null,array('class'=>'form-control '))}}
        </div>

        <div class="form-group col-lg-6 ">
            {{Form::label('clock_out',__('Clock Out'), ['class' => 'form-label'])}}
            {{Form::time('clock_out',null,array('class'=>'form-control '))}}
        </div>

        <div class="form-group col-lg-6">
            {{ Form::label('total_break_duration', __('Break Time (In Minutes)'), ['class' => 'form-label']) }}
            {{ Form::number('total_break_duration', null, array('class' => 'form-control', 'min' => '0' , 'pattern' => '[0-9]*', 'inputmode' => 'numeric')) }}
        </div>
        <div class="form-group col-lg-6">
            {{ Form::label('work_from_home', __('Work From Home'), ['class' => 'form-label']) }}
            {{Form::select('work_from_home', array("0" => "No", "1" => "Yes"),null,array('class'=>'form-control select'))}}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
</div>
{{ Form::close() }}



