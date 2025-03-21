{{Form::open(array('url'=>'payslip/bulkpayment/'.$date,'method'=>'post'))}}
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    {{ __('Total Unpaid Employee') }} <b>{{ count($unpaidEmployees) }}</b> {{_('out of')}} <b>{{ count($Employees) }}</b>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <input type="button" value="{{__('Cancel')}}" class="btn btn-light" data-bs-dismiss="modal">
        <button type="submit" class="btn btn-primary">{{ __('Bulk Payment') }}</button>
    </div>

{{Form::close()}}
