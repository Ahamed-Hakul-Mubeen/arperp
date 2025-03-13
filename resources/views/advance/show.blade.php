@php
    $advancepath=\App\Models\Utility::get_file('uploads/advance');
@endphp
<div class="table-responsive">
    <table class="table table-md">
        <tbody>
            <tr>
                <th>{{ __("Advance") }}</th>
                <td>{{ AUth::user()->advanceNumberFormat($advance->advance_id) }}</td>
            </tr>
            <tr>
                <th>{{ __("Date") }}</th>
                <td>{{  Auth::user()->dateFormat($advance->date)}}</td>
            </tr>
            <tr>
                <th>{{ __("Amount") }}</th>
                <td>{{  Auth::user()->priceFormat($advance->amount)}}</td>
            </tr>
            <tr>
                <th> {{__('Balance')}}</th>
                <td>{{  Auth::user()->priceFormat($advance->balance)}}</td>
            </tr>
            <tr>
                <th>{{ __("Account") }}</th>
                <td>{{ !empty($advance->bankAccount)?$advance->bankAccount->bank_name.' '.$advance->bankAccount->holder_name:''}}</td>
            </tr>
            <tr>
                <th>{{ __("Customer") }}</th>
                <td>{{  (!empty($advance->customer)?$advance->customer->name:'-')}}</td>
            </tr>
            <tr>
                <th>{{ __("Reference") }}</th>
                <td>{{  !empty($advance->reference)?$advance->reference:'-'}}</td>
            </tr>
            <tr>
                <th>{{ __("Description") }}</th>
                <td>{{  !empty($advance->description)?$advance->description:'-'}}</td>
            </tr>
            <tr>
                <th>{{ __("Payment Receipt") }}</th>
                <td>
                    @if(!empty($advance->add_receipt))
                        <a  class="action-btn bg-primary ms-2 btn btn-sm align-items-center" href="{{ $advancepath . '/' . $advance->add_receipt }}" download="">
                            <i class="text-white ti ti-download"></i>
                        </a>
                        <a href="{{ $advancepath . '/' . $advance->add_receipt }}"  class="mx-3 action-btn bg-secondary ms-2 btn btn-sm align-items-center" data-bs-toggle="tooltip" title="{{__('Download')}}" target="_blank"><span class="btn-inner--icon"><i class="text-white ti ti-crosshair" ></i></span></a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            <tr>
                <th>{{ __("Status") }}</th>
                <th>
                    @if($advance->status == 0)
                        <span class="p-2 px-3 rounded status_badge badge bg-secondary">{{ __("Pending") }}</span>
                    @else
                        <span class="p-2 px-3 rounded status_badge badge bg-primary">{{ __("Closed") }}</span>
                    @endif
                </th>
            </tr>
        </tbody>
    </table>
</div>
