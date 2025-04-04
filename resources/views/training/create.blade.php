{{Form::open(array('url'=>'training','method'=>'post'))}}
<div class="modal-body">

    {{-- start for ai module--}}
    @php
        $plan= \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if($plan->chatgpt == 1)
    <div class="text-end">
        <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['training']) }}"
          data-bs-placement="top"  data-title="{{ __('Generate content with AI') }}">
            <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
        </a>
    </div>
    @endif
    {{-- end for ai module--}}

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('branch',__('Company'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('branch',$branches,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('trainer_option',__('Trainer Option'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('trainer_option',$options,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('training_type',__('Training Type'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('training_type',$trainingTypes,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('trainer',__('Trainer'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('trainer',$trainers,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('training_cost',__('Training Cost'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::number('training_cost',null,array('class'=>'form-control','step'=>'0.01','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('employee',__('Employee'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('employee',$employees,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('start_date',__('Start Date'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::date('start_date',null,array('class'=>'form-control ','required' => 'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('end_date',__('End Date'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::date('end_date',null,array('class'=>'form-control ','required' => 'required'))}}
            </div>
        </div>
        <div class="form-group col-lg-12">
            {{Form::label('description',__('Description'),['class'=>'form-label'])}}
            {{Form::textarea('description',null,array('class'=>'form-control','placeholder'=>__('Description')))}}
        </div>


    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>

{{Form::close()}}

<script>
    $(document).ready(function(){
        $('#start_date').on('change', function() {
            var startDate = $(this).val();
            $('#end_date').attr('min', startDate);
        });
    });
</script>
