{{ Form::model($deal, array('route' => array('deals.sources.update', $deal->id), 'method' => 'PUT')) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12 form-group">
            <div class="row gutters-xs">
                @foreach ($sources as $source)
                    <div class="mt-2 mb-2 col-12 custom-control custom-checkbox">
                        {{ Form::checkbox('sources[]',$source->id,($selected && array_key_exists($source->id,$selected))?true:false,['class' => 'form-check-input','id'=>'sources_'.$source->id]) }}
                        {{ Form::label('sources_'.$source->id, ucfirst($source->name),['class'=>'custom-control-label ml-4 text-sm font-weight-bold']) }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>
{{Form::close()}}


