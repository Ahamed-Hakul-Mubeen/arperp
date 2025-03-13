<div class="modal-body">
    <form action="{{ route('asset_management.unassignAsset', $asset->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="employee_id">{{ __('Employee') }}</label>
            @if (isset($latestHistory))
                @if ($latestHistory->action === 'assigned' || $latestHistory->action === 'transferred')
                    <input type="text" class="form-control" value="{{ $latestHistory->employee->name }}" readonly>
                    <input type="hidden" name="employee_id" value="{{ $latestHistory->employee_id }}">
                @endif
            @else
                <select name="employee_id" class="form-control" required>
                    <option value="" disabled selected>{{ __('Select Employee') }}</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            @endif
            </div>
        <div class="form-group">
            <label for="unassign_description">{{ __('Unassign Description') }}</label>
            <textarea name="unassign_description" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label for="assigned_date">{{ __('UnAssigned Date') }}</label>
            <input type="date" name="assigned_date" class="form-control" min="{{ date('Y-m-d') }}"  required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('UnAssign') }}</button>
    </form>
</div>