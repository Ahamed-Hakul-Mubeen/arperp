{{ Form::open(['route' => ['project.milestone.store', $project->id]]) }}
<div class="modal-body">
    {{-- start for ai module --}}
    @php
        $plan = \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['project milestone']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module --}}
    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('title', __('Title'), ['class' => 'form-label']) }}
            {{ Form::text('title', null, ['class' => 'form-control', 'required' => 'required']) }}
            @error('title')
                <span class="invalid-title" role="alert">
                    <strong class="text-danger">{{ $message }}</strong </span>
                @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('status', __('Status'), ['class' => 'form-label']) }}
            {!! Form::select('status', \App\Models\Project::$project_status, null, [
                'class' => 'form-control select',
                'required' => 'required',
            ]) !!}
            @error('client')
                <span class="invalid-client" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('start_date', __('Start Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('start_date', '', ['class' => 'form-control', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('due_date', __('Due Date'), ['class' => 'col-form-label']) }}
            {{ Form::date('due_date', '', ['class' => 'form-control', 'required' => 'required']) }}
        </div>

        <div class="form-group col-md-6">
            {{ Form::label('percentage', __('Percentage (% of Budget)'), ['class' => 'col-form-label']) }}
            <div class="input-group search-form">
                {{ Form::number('percentage','', array('class' => 'form-control', 'required' => 'required','min' => '0', 'max' => $available_milestone_percentage, 'total_cost' => $total_cost, 'placeholder'=>__('Percentage'))) }}
                <span class="bg-transparent input-group-text">%</span>
            </div>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('cost', __('Cost ('.\Auth::user()->currencySymbol().')'), ['class' => 'col-form-label']) }}
            {{ Form::text('cost', null, ['class' => 'form-control', 'required' => 'required', 'readonly' => 'readonly']) }}
        </div>

        <div class="form-group col-md-6">
            <div class="d-flex justify-content-between">
                {{ Form::label('vender_id', __('Vendor'),['class'=>'col-form-label']) }}
                <a href="#" data-size="lg" data-url="{{ route('vender.create',['redirect_to_payment' => 1]) }}" data-ajax-popup="true" data-title="{{__('Create New Vendor')}}" data-bs-toggle="tooltip" title="{{ __('Create') }}">
                    <i class="ti ti-plus"></i>{{__('Add Vendor')}}
                </a>
            </div>
            {{ Form::select('vender_id', $vender,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
    </div>
    <div class="row">
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => '2']) !!}
            @error('description')
                <span class="invalid-description" role="alert">
                    <strong class="text-danger">{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
</div>
{{ Form::close() }}

<script>
    $(document).ready(function(){
        $('#start_date').on('change', function() {
            var startDate = $(this).val();
            $('#due_date').attr('min', startDate);
        });
    });
</script>
