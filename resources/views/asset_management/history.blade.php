<div class="modal-body">
    <table class="table table-striped table-responsive">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Employee') }}</th>
                <th>{{ __('Description') }}</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $previousEmployeeName = null; 
                $wasLastActionTransfer = false;
            @endphp
            @foreach($historyRecords as $record)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($record->action_date)->format('d M Y') }}</td>
                    <td>
                        @if($record->action == 'assigned')
                            {{ __('Assigned') }}
                        @elseif($record->action == 'unassigned')
                            {{ __('Unassigned') }}
                        @elseif($record->action == 'transferred')
                            {{ __('Transferred') }}
                        @endif
                    </td>
                    <td>
                        @if($record->action == 'assigned')
                            {{ optional($record->employee)->name }}
                            @php 
                                // Reset the tracking variables when a normal assignment happens
                                $previousEmployeeName = null;
                                $wasLastActionTransfer = false;
                            @endphp
                        @elseif($record->action == 'unassigned')
                            @if($wasLastActionTransfer)
                                {{ $previousEmployeeName }}
                            @else
                                {{ optional($record->employee)->name }}
                            @endif
                        @elseif($record->action == 'transferred')
                            {{ optional($record->fromEmployee)->name }} to {{ optional($record->toEmployee)->name }}
                            @php 
                                // Store the toEmployee name for the next unassignment
                                $previousEmployeeName = optional($record->toEmployee)->name;
                                $wasLastActionTransfer = true;
                            @endphp
                        @endif
                    </td>
                    <td>
                       {{$record->description}}
                    <td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
</div>
<style>
    .modal-body {
    overflow-x: auto;
}

.table th, .table td {
    white-space: nowrap;
}
</style>
