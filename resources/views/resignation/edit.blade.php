{{ Form::model($resignation, ['route' => ['resignation.update', $resignation->id], 'method' => 'PUT']) }}
<div class="modal-body">
    {{-- start for ai module --}}
    @php
        $plan = \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if ($plan->chatgpt == 1)
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true"
                data-url="{{ route('generate', ['resignation']) }}" data-bs-placement="top"
                data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{ __('Generate with AI') }}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module --}}
    <div class="row">
        @if (\Auth::user()->type != 'Employee')
            <div class="form-group col-lg-12">
                {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
                {{ Form::select('employee_id', $employees, null, ['class' => 'form-control select', 'required' => 'required']) }}
            </div>
        @endif
        <div class="form-group col-lg-6 col-md-6">
            {{ Form::label('notice_date', __('Notice Date'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::date('notice_date', null, ['class' => 'form-control ','required' => 'required']) }}
        </div>
        <div class="form-group col-lg-6 col-md-6">
            {{ Form::label('resignation_date', __('Resignation Date'), ['class' => 'form-label']) }}<span class="text-danger">*</span>
            {{ Form::date('resignation_date', null, ['class' => 'form-control ','required' => 'required']) }}
        </div>
        <div class="form-group col-lg-4 col-md-4">
            {{ Form::label('no_of_years', __('Number of Years worked'), ['class' => 'form-label']) }}
            {{ Form::number('no_of_years', null, ['id' => 'no_of_years', 'class' => 'form-control' , 'readonly' => 'readonly']) }}
        </div>
        <div class="form-group col-lg-4 col-md-4">
            {{ Form::label('base_salary', __('Base Salary'), ['class' => 'form-label']) }}
            {{ Form::number('base_salary', null, ['id' => 'base_salary', 'class' => 'form-control', 'readonly' => 'readonly']) }}
        </div>
        <div class="form-group col-lg-4 col-md-4">
            {{ Form::label('settlement', __('Settlement Amount'), ['class' => 'form-label']) }}
            {{ Form::number('settlement', null, ['id' => 'settlement', 'class' => 'form-control']) }}
        </div>
        <div class="form-group col-lg-12">
            {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
            {{ Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __('Enter Description')]) }}
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
</div>
{{ Form::close() }}
