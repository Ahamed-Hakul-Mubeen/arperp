<div class="modal-body">
    <form action="{{ route('asset_management.assignAsset', $asset->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="employee_id">{{ __('Select Employee') }}</label>
            <select name="employee_id" class="form-control" required>
                <option value="" disabled selected>{{ __('Select Employee') }}</option>
              
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}"
                        @if (isset($latestHistory))
                        @if ($latestHistory->action === 'assigned' && $latestHistory->employee_id == $employee->id && $asset->status == 1)
                            selected
                        @elseif ($latestHistory->action === 'transferred' && $latestHistory->to_employee_id == $employee->id && $asset->status == 1)
                            selected disabled
                        @endif
                    @endif>
                    {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="assign_description">{{ __('Assign Description') }}</label>
            <textarea name="assign_description" class="form-control" required>{{ isset($latestHistory) && $asset->status == 1 ? $latestHistory->description : '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="assigned_date">{{ __('Assigned Date') }}</label>
            <input type="date" name="assigned_date" class="form-control"  min="{{ date('Y-m-d') }}"  value="{{ isset($latestHistory) && $asset->status == 1 ? $latestHistory->action_date : '' }}" required>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Assign') }}</button>
        
    </form>
</div>