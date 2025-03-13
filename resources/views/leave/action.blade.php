{{Form::open(array('url'=>'leave/changeaction','method'=>'post', 'id' => 'changeaction_form'))}}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <table class="table modal-table">
                <tr>
                    <th>{{__('Employee')}}</th>
                    <td>{{ !empty($employee->name)?$employee->name:'' }}</td>
                </tr>
                <tr>
                    <th>{{__('Leave Type ')}}</th>
                    <td>{{ !empty($leavetype->title)?$leavetype->title:'' }}</td>
                </tr>
                <tr>
                    <th>{{__('Applied On')}}</th>
                    <td>{{\Auth::user()->dateFormat( $leave->applied_on) }}</td>
                </tr>
                <tr>
                    <th>{{__('Start Date')}}</th>
                    <td>{{ \Auth::user()->dateFormat($leave->start_date) }}</td>
                </tr>
                <tr>
                    <th>{{__('End Date')}}</th>
                    <td>{{ \Auth::user()->dateFormat($leave->end_date) }}</td>
                </tr>
                <tr>
                    <th>{{__('Leave Reason')}}</th>
                    <td>{{ !empty($leave->leave_reason)?$leave->leave_reason:'' }}</td>
                </tr>
                <tr>
                    <th>{{ __('PM Approval') }}</th>
                    <td>
                        @if($leave->pm_approval == 'Approved')
                            <div class="p-2 px-3 rounded status_badge badge bg-success">{{ __('Approved') }}</div>
                        @elseif($leave->pm_approval == 'Rejected')
                            <div class="p-2 px-3 rounded status_badge badge bg-danger">{{ __('Rejected') }}</div>
                        @else
                            <div class="p-2 px-3 rounded status_badge badge bg-warning">{{ __('Pending') }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>{{ __('HR Approval') }}</th>
                    <td>
                        @if($leave->hr_approval == 'Approved')
                            <div class="p-2 px-3 rounded status_badge badge bg-success">{{ __('Approved') }}</div>
                        @elseif($leave->hr_approval == 'Rejected')
                            <div class="p-2 px-3 rounded status_badge badge bg-danger">{{ __('Rejected') }}</div>
                        @else
                            <div class="p-2 px-3 rounded status_badge badge bg-warning">{{ __('Pending') }}</div>
                        @endif
                    </td>
                </tr>
                {{-- <tr>
                    <th>{{__('Overall Status')}}</th>
                    <td>{{ $leave->status }}</td>
                </tr> --}}
                <input type="hidden" value="{{ $leave->id }}" name="leave_id">
                <input type="hidden" id="approval_type" name="approval_type">
            </table>
        </div>
    </div>
</div>

@if(\Auth::user()->type == 'company' || \Auth::user()->type == 'HR' || \Auth::user()->type == 'Project Manager')
<div class="modal-footer">
    @if(\Auth::user()->type == 'Project Manager')
        <input type="hidden" name="status" id="status_id">
        <input type="button" id="pm_approval_btn" class="btn btn-success" value="Approval">
        <input type="button" id="pm_reject_btn" class="btn btn-danger" value="Reject">
    @endif

    @if(\Auth::user()->type == 'HR')
        <input type="hidden" name="status" id="status_id">
        <input type="button" id="hr_approval_btn" class="btn btn-success" value="Approval">
        <input type="button" id="hr_reject_btn" class="btn btn-danger" value="Reject">
    @endif

    @if(\Auth::user()->type == 'company')
        <input type="hidden" name="status" id="status_id">
        <input type="button" id="company_approval_btn" class="btn btn-success" value="Approval">
        <input type="button" id="company_reject_btn" class="btn btn-danger" value="Reject">
    @endif
</div>
@endif
{{Form::close()}}

<script>
$(document).on("click", "#pm_approval_btn", function(e) {
    $("#status_id").val("Approved");
    $("#approval_type").val("PM");
    $("#changeaction_form").submit();
});
$(document).on("click", "#pm_reject_btn", function(e) {
    $("#status_id").val("Rejected");
    $("#approval_type").val("PM");
    $("#changeaction_form").submit();
});

$(document).on("click", "#hr_approval_btn", function(e) {
    $("#status_id").val("Approved");
    $("#approval_type").val("HR");
    $("#changeaction_form").submit();
});
$(document).on("click", "#hr_reject_btn", function(e) {
    $("#status_id").val("Rejected");
    $("#approval_type").val("HR");
    $("#changeaction_form").submit();
});

$(document).on("click", "#company_approval_btn", function(e) {
    $("#status_id").val("Approved");
    $("#approval_type").val("Final");
    $("#changeaction_form").submit();
});
$(document).on("click", "#company_reject_btn", function(e) {
    $("#status_id").val("Rejected");
    $("#approval_type").val("Final");
    $("#changeaction_form").submit();
});


</script>