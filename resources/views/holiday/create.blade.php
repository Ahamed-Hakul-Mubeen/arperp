{{Form::open(array('url'=>'holiday','method'=>'post'))}}
<link rel="stylesheet" href="{{ asset('assets/css/plugins/flatpickr.min.css') }}">
<div class="modal-body">
    {{-- start for ai module--}}
    @php
        $plan= \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if($plan->chatgpt == 1)
    <div class="text-end">
        <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['holiday']) }}"
           data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
            <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
        </a>
    </div>
    @endif
    {{-- end for ai module--}}
    <div class="row">
        <div class="form-group col-md-12">
            {{Form::label('occasion',__('Occasion'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{Form::text('occasion',null,array('class'=>'form-control' , 'placeholder'=>__('Enter Occation'),'required'=>'required'))}}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12">
            {{Form::label('date',__('Holiday Date'),['class'=>'form-label'])}}<span class="text-danger">*</span>
            {{ Form::text('date', isset($_GET['date'])?$_GET['date']:null, array('class' => 'form-control month-btn','id'=>'pc-daterangepicker-1','readonly','required'=>'required')) }}
        </div>
        {{-- <div class="form-group col-md-6">
            {{Form::label('end_date',__('End Date'),['class'=>'form-label'])}}
            {{Form::date('end_date',null,array('class'=>'form-control'))}}
        </div> --}}
    </div>
    @if (isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
        <div class="form-group col-md-6">
            {{Form::label('synchronize_type',__('Synchronize in Google Calendar ?'),array('class'=>'form-label')) }}
            <div class=" form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow" value="google_calender">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
    @endif
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>

{{Form::close()}}

<script>
$(document).ready(function() {
    $(document).on('shown.bs.modal', function (e) {
        // Check if the specific input element for Flatpickr exists in the currently displayed modal
        if ($(e.target).find("#pc-daterangepicker-1").length > 0) {
            // Initialize Flatpickr
            daterange();
        }
    });
});

// Function to initialize Flatpickr
function daterange() {
    if ($("#pc-daterangepicker-1").length > 0) {
        document.querySelector("#pc-daterangepicker-1").flatpickr({
            mode: "multiple", // Enable multiple date selection
            dateFormat: "Y-m-d", // Adjust the format as needed
        });
    }
}
</script>

