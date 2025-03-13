@if($latestHistory)
<div class="modal-body">
    <form action="{{ route('asset_management.transfer', $latestHistory->asset_id) }}" method="POST">
        @csrf

        <!-- Display the "From Employee" -->
        <div class="form-group">
            <label for="from_employee_id">{{ __('From Employee') }}</label>
            <input type="text" name="from_employee_name" class="form-control" value="{{ $latestHistory->employee->name ?? $latestHistory->toEmployee->name }}" readonly>
            <input type="hidden" name="from_employee_id" value="{{ $latestHistory->employee_id ?? $latestHistory->to_employee_id }}">
        </div>

        <!-- Select the new employee to transfer the asset to -->
        <div class="form-group">
            <label for="to_employee_id">{{ __('To Employee') }}</label>
            <select name="to_employee_id" class="form-control" required>
                <option value="" disabled selected>{{ __('Select To Employee') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Automatically fill the transfer date -->
        <div class="form-group">
            <label for="transfer_description">{{ __('Transfer Description') }}</label>
            <textarea  name="transfer_description" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label for="transfer_date">{{ __('Transfer Date') }}</label>
            <input type="date" name="transfer_date" class="form-control"  min="{{ date('Y-m-d') }}"  value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('Transfer') }}</button>
    </form>
</div>
@else
    <p>{{ __('No assets are assigned.') }}</p>
@endif
