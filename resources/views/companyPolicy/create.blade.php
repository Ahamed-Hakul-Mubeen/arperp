{{Form::open(array('url'=>'company-policy','method'=>'post', 'enctype' => "multipart/form-data"))}}
<div class="modal-body">
    {{-- start for ai module--}}
    @php
        $plan= \App\Models\Utility::getChatGPTSettings();
    @endphp
    @if($plan->chatgpt == 1)
    <div class="text-end">
        <a href="#" data-size="md" class="btn btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['company policy']) }}"
           data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
            <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
        </a>
    </div>
    @endif
    {{-- end for ai module--}}
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('branch',__('Company'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::select('branch',$branch,null,array('class'=>'form-control select','required'=>'required'))}}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{Form::label('title',__('Title'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::text('title',null,array('class'=>'form-control','required'=>'required' , 'placeholder'=>__('Enter Title')))}}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{ Form::label('description', __('Description'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{ Form::textarea('description',null, array('class' => 'form-control' , 'placeholder'=>__('Enter Description'),'required'=>'required')) }}
            </div>
        </div>
        <div class="col-md-12">
            {{Form::label('attachment',__('Attachment'),['class'=>'form-label'])}}
            <div class="choose-file form-group">
                <label for="attachment" class="form-label">
                    <input type="file" class="form-control" name="attachment" id="attachment" data-filename="attachment_create">
                    <img id="image" class="mt-3" style="width:25%;"/>

                </label>
{{--                <p class="attachment_create"></p>--}}
            </div>
        </div>


    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{Form::close()}}


<script>
    document.getElementById('attachment').onchange = function () {
        var src = URL.createObjectURL(this.files[0])
        document.getElementById('image').src = src
    }
</script>

