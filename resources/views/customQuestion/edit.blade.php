 {{Form::model($customQuestion,array('route' => array('custom-question.update', $customQuestion->id), 'method' => 'PUT')) }}
 <div class="modal-body">

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('question',__('Question'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{Form::text('question',null,array('class'=>'form-control','placeholder'=>__('Enter question'),'required' => 'required'))}}
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                {{Form::label('is_required',__('Is Required'),['class'=>'form-label'])}}<span class="text-danger">*</span>
                {{ Form::select('is_required', $is_required,null, array('class' => 'form-control select','required'=>'required')) }}
            </div>
        </div>

    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Update')}}</button>
</div>
    {{Form::close()}}

