<div class="col-form-label">
    <div class="row px-3">
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Employee') }}</h6>
            <h6 class="emp-title black-text">
                {{ !empty($payslip->employees) ? \Auth::user()->employeeIdFormat($payslip->employees->employee_id) : '' }}
            </h6>
        </div>
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Basic Salary') }}</h6>
            <h6 class="emp-title black-text">{{ \Auth::user()->priceFormat($payslip->basic_salary) }}</h6>
        </div>
        <div class="col-md-4 mb-3">
            <h6 class="emp-title mb-0">{{ __('Payroll Month') }}</h6>
            <h6 class="emp-title black-text">{{ \Auth::user()->dateFormat($payslip->salary_month) }}</h6>
        </div>

        <div class="col-lg-12 our-system">
            {{ Form::open(['route' => ['payslip.updateemployee', $payslip->employee_id], 'method' => 'post']) }}
            {!! Form::hidden('payslip_id', $payslip->id, ['class' => 'form-control']) !!}
            <div class="row">

                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" href="#allowance"
                            role="tab" aria-controls="pills-home" aria-selected="true">{{ __('Allowance') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" href="#commission"
                            role="tab" aria-controls="pills-profile"
                            aria-selected="false">{{ __('Commission') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#loan" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('Loan') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#deduction"
                            role="tab" aria-controls="pills-contact"
                            aria-selected="false">{{ __('Saturation Deduction') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#payment" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('Other Payment') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pills-contact-tab" data-bs-toggle="pill" href="#overtime" role="tab"
                            aria-controls="pills-contact" aria-selected="false">{{ __('Overtime') }}</a>
                    </li>
                </ul>
                {{-- @dd($payslip->allowance) --}}
                <div class="tab-content pt-4">
                    <div id="allowance" class="tab-pane in active">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $allowances = json_decode($payslip->allowance);
                                        @endphp
                                        @foreach ($allowances as $allownace)
                                            <div class="col-md-12 form-group">
                                                {!! Form::label('title', $allownace->title, ['class' => 'col-form-label']) !!}
                                                {!! Form::text('allowance[]', $allownace->amount, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('allowance_id[]', $allownace->id, ['class' => 'form-control']) !!}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="commission" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $commissions = json_decode($payslip->commission);
                                        @endphp
                                        @foreach ($commissions as $commission)
                                            <div class="col-md-12 form-group">
                                                {!! Form::label('title', $commission->title, ['class' => 'col-form-label']) !!}
                                                {!! Form::text('commission[]', $commission->amount, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('commission_id[]', $commission->id, ['class' => 'form-control']) !!}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="loan" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $loans = json_decode($payslip->loan);
                                        @endphp
                                        @foreach ($loans as $loan)
                                            <div class="col-md-12 form-group">
                                                {!! Form::label('title', $loan->title, ['class' => 'col-form-label']) !!}
                                                {!! Form::text('loan[]', $loan->amount, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('loan_id[]', $loan->id, ['class' => 'form-control']) !!}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="deduction" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $saturation_deductions = json_decode($payslip->saturation_deduction);
                                        @endphp
                                        @foreach ($saturation_deductions as $deduction)
                                            <div class="col-md-12 form-group">
                                                {!! Form::label('title', $deduction->title, ['class' => 'col-form-label']) !!}
                                                {!! Form::text('saturation_deductions[]', $deduction->amount, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('saturation_deductions_id[]', $deduction->id, ['class' => 'form-control']) !!}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="payment" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $other_payments = json_decode($payslip->other_payment);
                                        @endphp
                                        @foreach ($other_payments as $payment)
                                            <div class="col-md-12 form-group">
                                                {!! Form::label('title', $payment->title, ['class' => 'col-form-label']) !!}
                                                {!! Form::text('other_payment[]', $payment->amount, ['class' => 'form-control']) !!}
                                                {!! Form::hidden('other_payment_id[]', $payment->id, ['class' => 'form-control']) !!}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="overtime" class="tab-pane">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card bg-none mb-0">
                                    <div class="row px-3">
                                        @php
                                            $overtimes = json_decode($payslip->overtime);
                                            $overtimes_hours = $overtimes_minutes = $overtimes_amount = 0;
                                            if (isset($overtimes->hours)) {
                                                $overtimes_hours = $overtimes->hours;
                                            }
                                            if (isset($overtimes->minutes)) {
                                                $overtimes_minutes = $overtimes->minutes;
                                            }
                                            if (isset($overtimes->amount)) {
                                                $overtimes_amount = $overtimes->amount;
                                            }
                                        @endphp
                                        <div class="col-md-4 form-group">
                                            {!! Form::label('hours', __('Hours'), ['class' => 'col-form-label']) !!}
                                            {!! Form::text('overtime_hours', $overtimes_hours, ['class' => 'form-control']) !!}
                                        </div>
                                        <div class="col-md-4 form-group">
                                            {!! Form::label('minutes', __('Minutes'), ['class' => 'col-form-label']) !!}
                                            {!! Form::text('overtime_minutes', $overtimes_minutes, ['class' => 'form-control']) !!}
                                        </div>
                                        <div class="col-md-4 form-group">
                                            {!! Form::label('amount', __('Amount'), ['class' => 'col-form-label']) !!}
                                            {!! Form::text('overtime_amount', $overtimes_amount, ['class' => 'form-control']) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
                <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
