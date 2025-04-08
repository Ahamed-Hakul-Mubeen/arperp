{{ Form::model($workShift, array('route' => array('work_shift.update', $workShift->id), 'method' => 'PUT'))  }}

<div class="modal-body">
    <div class="row">

        <!-- Name Input -->
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                {{ Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('Enter name')]) }}
                @error('name')
                <span class="invalid-name" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Start Date and End Date -->
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                {{ Form::date('start_date', $workShift->start_date, ['class' => 'form-control', 'required']) }}
                @error('start_date')
                <span class="invalid-start_date" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                {{ Form::date('end_date', $workShift->end_date, ['class' => 'form-control', 'required']) }}
                @error('end_date')
                <span class="invalid-end_date" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Shift Type Radio Buttons -->
        <div class="col-12 d-none">
            <div class="form-group">
                {{ Form::label('shift_type', __('Choose a working shift type'), ['class' => 'form-label']) }}
                <div>
                    <div class="form-check form-check-inline">
                        {{ Form::radio('shift_type', 'regular', $workShift->shift_type == 'regular', ['class' => 'form-check-input', 'id' => 'regular_shift']) }}
                        {{ Form::label('regular_shift', __('Regular'), ['class' => 'form-check-label']) }}
                    </div>
                    <div class="form-check form-check-inline">
                        {{ Form::radio('shift_type', 'scheduled', $workShift->shift_type == 'scheduled', ['class' => 'form-check-input', 'id' => 'scheduled_shift']) }}
                        {{ Form::label('scheduled_shift', __('Scheduled'), ['class' => 'form-check-label']) }}
                    </div>
                </div>
                @error('shift_type')
                <span class="invalid-shift_type" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Schedule Section (Hidden by default) -->
        <div id="schedule_section" class="col-12" style="display:none;">
            <div class="form-group">
                <h5>{{ __('Set Scheduled Week') }} <small>({{ __('Set week with customized time and without time it will be weekend.') }})</small></h5>
                <div class="row">
                    <!-- Days Schedule -->
                    <div class="col-md-6 mb-3">
                        <h6>{{ __('Sunday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('sunday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('sunday_start_time', $workShift->sunday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('sunday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('sunday_end_time', $workShift->sunday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <!-- Repeat for each day -->
                    <!-- Monday -->
                    <div class="col-md-6">
                        <h6>{{ __('Monday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('monday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('monday_start_time', $workShift->monday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('monday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('monday_end_time', $workShift->monday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>{{ __('Tuesday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('tuesday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('tuesday_start_time', $workShift->tuesday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('tuesday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('tuesday_end_time', $workShift->tuesday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>{{ __('Wednesday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('wednesday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('wednesday_start_time', $workShift->wednesday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('wednesday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('wednesday_end_time', $workShift->wednesday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6>{{ __('Thursday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('thusday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('thusday_start_time', $workShift->thusday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('thursday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('thursday_end_time', $workShift->thursday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>{{ __('Friday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('friday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('friday_start_time', $workShift->friday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('friday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('friday_end_time', $workShift->friday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>{{ __('Saturday') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                {{ Form::label('saturday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                {{ Form::time('saturday_start_time', $workShift->saturday_start_time, ['class' => 'form-control']) }}
                            </div>
                            <div class="col-md-6">
                                {{ Form::label('saturday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                {{ Form::time('saturday_end_time', $workShift->saturday_end_time, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>
                    <!-- You can repeat for each day -->
                </div>
            </div>
        </div>

        <!-- Start Time and End Time for Regular Shift -->
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_time', __('Start Time'), ['class' => 'form-label']) }}
                {{ Form::time('start_time', $workShift->start_time, ['class' => 'form-control']) }}
                @error('start_time')
                <span class="invalid-start_time" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_time', __('End Time'), ['class' => 'form-label']) }}
                {{ Form::time('end_time', $workShift->end_time, ['class' => 'form-control']) }}
                @error('end_time')
                <span class="invalid-end_time" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Weekend Selection (Checkboxes) -->
                <div class="row mb-2">
                    {{ Form::label('weekend', __('Select weekend day (off days)'), ['class' => 'form-label text-danger']) }}
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_sunday_off', 1, $workShift->is_sunday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_sunday_off']) }}
                            {{ Form::label('is_sunday_off', __('Sunday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_monday_off', 1, $workShift->is_monday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_monday_off']) }}
                            {{ Form::label('is_monday_off', __('Monday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_tuesday_off', 1, $workShift->is_tuesday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_tuesday_off']) }}
                            {{ Form::label('is_tuesday_off', __('Tuesday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_wednesday_off', 1, $workShift->is_wednesday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_wednesday_off']) }}
                            {{ Form::label('is_wednesday_off', __('Wednesday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_thursday_off', 1, $workShift->is_thursday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_thursday_off']) }}
                            {{ Form::label('is_thursday_off', __('Thursday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_friday_off', 1, $workShift->is_friday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_friday_off']) }}
                            {{ Form::label('is_friday_off', __('Friday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            {{ Form::checkbox('is_saturday_off', 1, $workShift->is_saturday_off, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_saturday_off']) }}
                            {{ Form::label('is_saturday_off', __('Saturday'), ['class' => 'form-check-label']) }}
                        </div>
                    </div>
                    <!-- Repeat for other days -->
                </div>
                @error('weekend')
                <span class="invalid-weekend" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
          

        <!-- Break Time, Description, Department, Employee -->
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('break_time', __('Break time'), ['class' => 'form-label']) }}
                {{ Form::text('break_time', $workShift->break_time, ['class' => 'form-control', 'placeholder' => __('+ Add')]) }}
                @error('break_time')
                <span class="invalid-break_time" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                {{ Form::textarea('description', $workShift->description, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Add description here')]) }}
                @error('description')
                <span class="invalid-description" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('department', __('Department'), ['class' => 'form-label']) }}
                {{ Form::text('department', $workShift->department, ['class' => 'form-control', 'placeholder' => __('Add description here')]) }}
                @error('department')
                <span class="invalid-department" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <div class="col-12">
            <div class="form-group">
                {{ Form::label('user', __('Employee'),['class'=>'form-label']) }}<span class="text-danger"></span>
                {!! Form::select('employee[]', $employees, $selectedEmployees, array('class' => 'form-control select2', 'id' => 'choices-multiple1', 'multiple' => 'multiple', 'required' => 'required')) !!}
            </div>
        </div>

    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
</div>

{{ Form::close() }}

<!-- Include necessary JavaScript to initialize multi-select and other UI interactions -->
<script type="text/javascript">
    $(document).ready(function () {

       
        const regularShift = $('#regular_shift');
        const scheduledShift = $('#scheduled_shift');
        const scheduleSection = $('#schedule_section');
    
        const startTimeInput = $('input[name="start_time"]');
        const endTimeInput = $('input[name="end_time"]');
        const weekendCheckboxSection = $('.weekend-checkbox'); // Ensure the weekend checkboxes have this class
    
        // Function to toggle the schedule section and manage required fields
        function toggleScheduleSection() {
            if (scheduledShift.is(':checked')) {
                scheduleSection.show(); // Show the schedule section (for start/end time by day)
                
                startTimeInput.removeAttr('required').closest('.col-md-6').hide(); // Hide and remove 'required' from start_time
                endTimeInput.removeAttr('required').closest('.col-md-6').hide(); // Hide and remove 'required' from end_time
                
                weekendCheckboxSection.closest('.row.mb-2').hide(); // Hide the weekend checkbox section
            } else {
                scheduleSection.hide(); // Hide the schedule section (for start/end time by day)
                
                startTimeInput.attr('required', 'required').closest('.col-md-6').show(); // Show and add 'required' to start_time
                endTimeInput.attr('required', 'required').closest('.col-md-6').show(); // Show and add 'required' to end_time
                
                weekendCheckboxSection.closest('.row.mb-2').show(); // Show the weekend checkbox section
            }
        }
    
        // Add event listeners to radio buttons
        regularShift.on('change', toggleScheduleSection);
        scheduledShift.on('change', toggleScheduleSection);
    
        // Initialize based on the current state (if one is already checked)
        toggleScheduleSection();
    });
    
   
    </script>
