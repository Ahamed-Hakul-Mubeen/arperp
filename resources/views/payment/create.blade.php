{{ Form::open(array('url' => 'payment','enctype' => 'multipart/form-data')) }}
<div class="modal-body">
    <div class="row">
        <div class="form-group col-md-6">
            <div class="d-flex justify-content-between">
                <div>
                    {{ Form::label('vender_id', __('Vendor'),['class'=>'form-label']) }}<span class="text-danger">*</span>
                </div>
                <a href="#" data-size="lg" data-url="{{ route('vender.create',['redirect_to_payment' => 1]) }}" data-ajax-popup="true" data-title="{{__('Create New Vendor')}}" data-bs-toggle="tooltip" title="{{ __('Create') }}">
                    <i class="ti ti-plus"></i>{{__('Add Vendor')}}
                </a>
            </div>
            {{ Form::select('vender_id', $venders,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Date'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{Form::date('date',null,array('class'=>'form-control','required'=>'required'))}}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('amount', __('Amount'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::number('amount', '', array('class' => 'form-control','required'=>'required','step'=>'0.01' , 'placeholder'=>__('Enter Amount'))) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('category_id', __('Category'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('category_id', $categories,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('account_id', __('Account'),['class'=>'form-label']) }}<span class="text-danger">*</span>
            {{ Form::select('account_id',$accounts,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
{{--        <div class="form-group col-md-6">--}}
{{--            {{ Form::label('chart_account_id', __('Chart Of Account'),['class'=>'form-label']) }}--}}
{{--            {{ Form::select('chart_account_id',$chartAccounts,null, array('class' => 'form-control select','required'=>'required')) }}--}}
{{--        </div>--}}
        <div class="form-group col-md-6">
            {{ Form::label('reference', __('Reference'),['class'=>'form-label']) }}
            {{ Form::text('reference', '', array('class' => 'form-control' , 'placeholder'=>__('Enter Reference'))) }}
        </div>
        <div class="form-group col-md-6">
            {{Form::label('add_receipt',__('Payment Receipt'),['class' => 'form-label'])}}
            {{Form::file('add_receipt',array('class'=>'form-control', 'id'=>'files'))}}
            <img id="image" class="mt-2" style="width:25%;"/>
        </div>
        <div class="form-group col-md-6">
                {{ Form::label('project_id', __('Project'),['class'=>'form-label']) }}
                {{ Form::select('project_id', $project_list,null, array('class' => 'form-control select')) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {{ Form::textarea('description', '', array('class' => 'form-control','rows'=>3 , 'placeholder'=>__('Enter Description'))) }}
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>

{{ Form::close() }}


<script>
    document.getElementById('files').onchange = function () {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>
