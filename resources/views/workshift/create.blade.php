{{ Form::open(['url' => 'work_shift', 'method' => 'post']) }}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
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
                {{ Form::date('start_date', null, ['class' => 'form-control', 'required']) }}
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
                {{ Form::date('end_date', null, ['class' => 'form-control', 'required']) }}
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
                        {{ Form::radio('shift_type', 'regular', true, ['class' => 'form-check-input', 'id' => 'regular_shift']) }}
                        {{ Form::label('regular_shift', __('Regular'), ['class' => 'form-check-label']) }}
                    </div>
                    <div class="form-check form-check-inline">
                        {{ Form::radio('shift_type', 'scheduled', false, ['class' => 'form-check-input', 'id' => 'scheduled_shift']) }}
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
                    <!-- Sunday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Sunday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('Sunday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('Sunday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('Sunday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('Sunday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Monday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Monday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('monday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('monday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('monday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('monday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Tuesday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Tuesday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('tuesday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('tuesday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('tuesday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('tuesday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Wednesday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Wednesday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('wednesday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('wednesday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('wednesday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('wednesday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Thursday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Thursday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('thursday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('thursday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('thursday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('thursday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Friday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Friday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('friday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('friday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('friday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('friday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
        
                    <!-- Saturday -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <h6>{{ __('Saturday') }}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    {{ Form::label('saturday_start_time', __('Start Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('saturday_start_time', null, ['class' => 'form-control']) }}
                                </div>
                                <div class="col-md-6">
                                    {{ Form::label('saturday_end_time', __('End Time'), ['class' => 'form-label']) }}
                                    {{ Form::time('saturday_end_time', null, ['class' => 'form-control']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

        <!-- Start Time and End Time for Regular Shift -->
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_time', __('Start Time'), ['class' => 'form-label']) }}
                {{ Form::time('start_time', null, ['class' => 'form-control', 'required']) }}
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
                {{ Form::time('end_time', null, ['class' => 'form-control', 'required']) }}
                @error('end_time')
                <span class="invalid-end_time" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Weekend Selection (Checkboxes) -->
        <div class="row mb-2">
                {{ Form::label('weekend', __('Select weekend day (off days)'), ['class' => 'form-label text-danger ']) }}
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_sunday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_sunday_off']) }}
                    {{ Form::label('is_sunday_off', __('Sunday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_monday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_monday_off']) }}
                    {{ Form::label('is_monday_off', __('Monday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_tuesday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_tuesday_off']) }}
                    {{ Form::label('is_tuesday_off', __('Tuesday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_wednesday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_wednesday_off']) }}
                    {{ Form::label('is_wednesday_off', __('Wednesday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_thursday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_thursday_off']) }}
                    {{ Form::label('is_thursday_off', __('Thursday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_friday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_friday_off']) }}
                    {{ Form::label('is_friday_off', __('Friday'), ['class' => 'form-check-label']) }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    {{ Form::checkbox('is_saturday_off', 1, false, ['class' => 'form-check-input weekend-checkbox', 'id' => 'is_saturday_off']) }}
                    {{ Form::label('is_saturday_off ', __('Saturday'), ['class' => 'form-check-label']) }}
                </div>
            </div>

       
    </div>

        <!-- Break Time, Description, Department, Employee -->
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('break_time', __('Break Time'), ['class' => 'form-label']) }}
                <input type="number" id="break_time_input" class="form-control" name="break_time" step="1" min="0">
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
                {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Add description here')]) }}
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
                    {{ Form::text('department', null, ['class' => 'form-control', 'placeholder' => __('Add department here')]) }}
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
                    {!! Form::select('employee[]', $employees, null,array('class' => 'form-control select2', 'id'=>'choices-multiple1', 'multiple'=>'multiple', 'required'=>'required')) !!}
                </div>
            </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
</div>
{{ Form::close() }}

<!-- CSS -->
<style>
    #schedule_section h5 {
        margin-bottom: 20px;
        font-weight: bold;
    }

    #schedule_section h6 {
        font-size: 16px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    #schedule_section .row {
        margin-bottom: 15px;
    }

    .form-control {
        font-size: 14px;
    }
</style>
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

$(document).ready(function () {
        // Initialize Select2 with multiple tag functionality
        if($('.select2').length) {
                        $('.select2').select2();
        }
    $('#break_time_select').on('change', function() {
    let selectedValues = $(this).val();
    let valid = true;

    selectedValues.forEach(function(value) {
        if (isNaN(value)) {
            valid = false;
        }
    });

    if (!valid) {
        alert('Please select only numeric values.');
        $(this).val([]); // Clear the invalid selection
    }
});
});
</script>

