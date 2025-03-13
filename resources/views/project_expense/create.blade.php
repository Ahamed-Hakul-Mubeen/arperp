{{ Form::open(['route' => ['projects.expenses.store',$project->id],'id' => 'create_expense','enctype' => 'multipart/form-data']) }}
<div class="modal-body">

<div class="row">
    <div class="col-12 col-md-12">
        <div class="form-group">
            {{ Form::label('name', __('Name'),['class' => 'form-label']) }}
            {{ Form::text('name', null, ['class' => 'form-control','required'=>'required']) }}
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('date', __('Date'),['class' => 'form-label']) }}
            {{ Form::date('date', null, ['class' => 'form-control' ,'required'=>'required']) }}
        </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="form-group">
          {{Form::label('amount',__('Amount'),['class'=>'form-label'])}}
          <div class="form-group price-input input-group search-form">
              <span class="bg-transparent input-group-text">{{\Auth::user()->currencySymbol()}}</span>
              {{Form::number('amount',null,array('class'=>'form-control','required' => 'required','min' => '0'))}}
          </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('account_id', __('Account'),['class'=>'form-label']) }}
            {{ Form::select('account_id',$accounts,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('chart_accounts', __('Chart Of Account'),['class'=>'form-label']) }}
            {{ Form::select('chart_accounts',$chart_accounts,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('milestone_id', __('Milestone'),['class' => 'form-label']) }}
            <select class="form-control select" name="milestone_id" id="milestone_id">
                <option value="0" class="text-muted">{{__('Select Milestone')}}</option>
                @foreach($project->milestones as $m_val)
                    <option value="{{ $m_val->id }}">{{ $m_val->title }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('vender_id', __('Vendor'),['class'=>'form-label']) }}
            {{ Form::select('vender_id', $vender,null, array('class' => 'form-control select','required'=>'required')) }}
        </div>
    </div>

    <div class="col-12 col-md-12">
        <div class="form-group">
            {{ Form::label('description', __('Description'),['class' => 'form-label']) }}
            {{-- <small class="mt-0 mb-2 form-text text-muted">{{__('This textarea will autosize while you type')}}</small> --}}
            {{ Form::textarea('description', null, ['class' => 'form-control','rows' => '3','data-toggle' => 'autosize']) }}
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            {{ Form::label('task_id', __('Task'),['class' => 'form-label']) }}
            <select class="form-control select" name="task_id" id="task_id">
                <option value="0">Choose Task</option>
                @foreach($project->tasks as $task)
                    <option value="{{ $task->id }}">{{ $task->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            {{Form::label('attachment',__('Attachment'),['class'=>'form-label'])}}
            <div class="choose-file form-group">
                <label for="attachment" class="form-label">
                    {{-- <div>{{__('Choose file here')}}</div> --}}
                    <input type="file" class="form-control" name="attachment" id="attachment" data-filename="attachment_create">
                </label>
                <p class="attachment_create"></p>
            </div>
        </div>
    </div>


</div>
</div>

<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
    <button type="submit" class="btn btn-primary">{{__('Create')}}</button>
</div>

{{ Form::close() }}


<script>
$(document).ready(function() {
    var projectId = '{{ $project->id }}'; 

    $('#milestone_id').on('change', function() {
        var milestoneId = $(this).val();
        var taskSelect = $('#task_id');

        taskSelect.empty().append('<option value="0">Choose Task</option>');

       
        var url = '{{ route('projects.gettask', ['projectId' => ':projectId', 'milestoneId' => ':milestoneId']) }}';
        url = url.replace(':projectId', projectId).replace(':milestoneId', milestoneId);

        $.ajax({
            url: url, 
            type: 'GET',
            success: function(data) {
                $.each(data, function(id, name) {
                    taskSelect.append(new Option(name, id));
                });
            },
            error: function() {
                console.error('Error fetching tasks');
            }
        });
    });
});
</script>