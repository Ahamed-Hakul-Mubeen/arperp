@extends('layouts.admin')
@section('page-title')
    {{__('Bill Detail')}}
@endsection
@push('script-page')
    <script>
        $(document).on('click', '#shipping', function () {
            var url = $(this).data('url');
            var is_display = $("#shipping").is(":checked");
            $.ajax({
                url: url,
                type: 'get',
                data:
                    'is_display': is_display,
                },
                success: function (data) {
                    // console.log(data);
                }
            });
        })



    </script>
@endpush
@php
    $settings = Utility::settings();
@endphp
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('bill.index')}}">{{__('Bill')}}</a></li>
    <li class="breadcrumb-item">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</li>
@endsection

@section('content')

    @can('send bill')
        @if($bill->status!=4)
            <div class="row">
                <div class="col-12">
                    <div class="card">
                    <div class="card-body">
                        <div class="row timeline-wrapper">
                            <div class="col-md-6 col-lg-4 col-xl-4 create_invoice">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-plus text-primary"></i>
                                </div>
                                <h6 class="my-3 text-primary">{{__('Create Bill')}}</h6>
                                <p class="mb-3 text-sm text-muted"><i class="mr-2 ti ti-clock"></i>{{__('Created on ')}}{{\Auth::user()->dateFormat($bill->bill_date)}}</p>
                                @can('edit bill')
                                    <a href="{{ route('bill.edit',\Crypt::encrypt($bill->id)) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}"><i class="mr-2 ti ti-pencil"></i>{{__('Edit')}}</a>

                                @endcan
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4 send_invoice">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-mail text-warning"></i>
                                </div>
                                <h6 class="my-3 text-warning">{{__('Send Bill')}}</h6>
                                <p class="mb-3 text-sm text-muted">
                                    @if($bill->status!=0 && $bill->send_date)
                                        <i class="mr-2 ti ti-clock"></i>{{__('Sent on')}} {{\Auth::user()->dateFormat($bill->send_date)}}
                                    @else
                                        @can('send bill')
                                            <small>{{__('Status')}} : {{__('Not Sent')}}</small>
                                        @endcan
                                    @endif
                                </p>

                                @if($bill->status ==0 && (\Auth::user()->type == "company" || \Auth::user()->type == "Accountant"))
                                    @can('send bill')
                                            <a id="send_btn" href="#" data-href="{{ route('bill.sent',$bill->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-original-title="{{__('Mark Sent')}}"><i class="mr-2 ti ti-send"></i>{{__('Send')}}</a>
                                    @endcan
                                @endif
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-4 create_invoice">
                                <div class="timeline-icons"><span class="timeline-dots"></span>
                                    <i class="ti ti-report-money text-info"></i>
                                </div>
                                <h6 class="my-3 text-info">{{__('Get Paid')}}</h6>
                                <p class="mb-3 text-sm text-muted">{{__('Status')}} : {{__('Awaiting payment')}} </p>
                                @if($bill->status!=0)
                                    @can('create payment bill')
                                        <a href="#" data-url="{{ route('bill.payment',$bill->id) }}" data-ajax-popup="true" data-title="{{__('Add Payment')}}" class="btn btn-sm btn-info" data-original-title="{{__('Add Payment')}}"><i class="mr-2 ti ti-report-money"></i>{{__('Add Payment')}}</a> <br>
                                    @endcan
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        @endif
    @endcan

    @if(\Auth::user()->type=='company')
        @if($bill->status!=0)
            <div class="mb-3 row justify-content-between align-items-center">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    {{-- @if(!empty($billPayment))
                        <div class="mx-2 all-button-box">
                            <a href="#" data-url="{{ route('bill.debit.note',$bill->id) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" class="btn btn-sm btn-primary">
                                {{__('Add Debit Note')}}
                            </a>
                        </div>

                    @endif --}}
                    <div class="mx-2 all-button-box">
                        <a href="{{ route('bill.resent',$bill->id) }}" class="btn btn-sm btn-primary">
                            {{__('Resend Bill')}}
                        </a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('bill.pdf', Crypt::encrypt($bill->id))}}" target="_blank" class="btn btn-sm btn-primary">
                            {{__('Download')}}
                        </a>
                    </div>
                </div>
            </div>
        @endif

    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="mt-2 row invoice-title">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{__('Bill')}}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ Auth::user()->billNumberFormat($bill->bill_id) }}</h4>
                                </div>
                                <div class="col-12">
                                    <hr>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col text-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="me-4">
                                            <small>
                                                <strong>{{__('Issue Date')}} :</strong><br>
                                                {{\Auth::user()->dateFormat($bill->bill_date)}}<br><br>
                                            </small>
                                        </div>
                                        <div>
                                            <small>
                                                <strong>{{__('Due Date')}} :</strong><br>
                                                {{\Auth::user()->dateFormat($bill->due_date)}}<br><br>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{__('Billed To')}} :</strong><br>
                                        @if(!empty($vendor->billing_name))
                                            {{!empty($vendor->billing_name)?$vendor->billing_name:''}}<br>
                                            {{!empty($vendor->billing_address)?$vendor->billing_address:''}}<br>
                                            {{!empty($vendor->billing_city)?$vendor->billing_city:'' .', '}}<br>
                                            {{!empty($vendor->billing_state)?$vendor->billing_state:'',', '}},
                                            {{!empty($vendor->billing_zip)?$vendor->billing_zip:''}}<br>
                                            {{!empty($vendor->billing_country)?$vendor->billing_country:''}}<br>
                                            {{!empty($vendor->billing_phone)?$vendor->billing_phone:''}}<br>
                                            @if($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{__('Tax Number ')}} : </strong>{{!empty($vendor->tax_number)?$vendor->tax_number:''}}
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </small>
                                </div>

                                @if(App\Models\Utility::getValByName('shipping_display')=='on')
                                    <div class="col">
                                        <small>
                                            <strong>{{__('Shipped To')}} :</strong><br>
                                            @if(!empty($vendor->shipping_name))
                                            {{!empty($vendor->shipping_name)?$vendor->shipping_name:''}}<br>
                                            {{!empty($vendor->shipping_address)?$vendor->shipping_address:''}}<br>
                                            {{!empty($vendor->shipping_city)?$vendor->shipping_city:'' . ', '}}<br>
                                            {{!empty($vendor->shipping_state)?$vendor->shipping_state:'' .', '}},
                                            {{!empty($vendor->shipping_zip)?$vendor->shipping_zip:''}}<br>
                                            {{!empty($vendor->shipping_country)?$vendor->shipping_country:''}}<br>
                                            {{!empty($vendor->shipping_phone)?$vendor->shipping_phone:''}}<br>
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </div>
                                @endif

                                <div class="col">
                                    <div class="mt-3 float-end">
                                        {!! DNS2D::getBarcodeHTML(route('bill.link.copy',\Illuminate\Support\Facades\Crypt::encrypt($bill->id)), "QRCODE",2,2) !!}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 row">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if($bill->status == 0)
                                            <span class="p-2 px-3 rounded badge bg-primary">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 1)
                                            <span class="p-2 px-3 rounded badge bg-warning">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 2)
                                            <span class="p-2 px-3 rounded badge bg-danger">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 3)
                                            <span class="p-2 px-3 rounded badge bg-info">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @elseif($bill->status == 4)
                                            <span class="p-2 px-3 rounded badge bg-primary">{{ __(\App\Models\Bill::$statues[$bill->status]) }}</span>
                                        @endif
                                    </small>
                                </div>


                                @if(!empty($customFields) && count($bill->customField)>0)
                                    @foreach($customFields as $field)
                                        <div class="col text-md-end">
                                            <small>
                                                <strong>{{$field->name}} :</strong><br>
                                                {{!empty($bill->customField)?$bill->customField[$field->id]:'-'}}
                                                <br><br>
                                            </small>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="mt-4 row">
                                <div class="col-md-12">
                                    <div class="mb-2 font-bold">{{__('Product Summary')}}</div>
                                    <small class="mb-2">{{__('All items here cannot be deleted.')}}</small>
                                    <div class="mt-3 table-responsive">
                                        <table class="table mb-0 table-striped">
                                            <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{__('Product')}}</th>
                                                <th class="text-dark">{{__('Quantity')}}</th>
                                                <th class="text-dark">{{__('Rate')}}</th>
                                                <th class="text-dark">{{__('Discount')}}</th>
                                                <th class="text-dark">{{__('Tax')}}</th>
                                                <th class="text-dark">{{__('Chart Of Account')}}</th>
                                                <th class="text-dark">{{__('Account Amount')}}</th>
                                                <th class="text-dark">{{__('Description')}}</th>
                                                <th class="text-end text-dark" width="12%">{{__('Price')}}<br>
                                                    <small class="text-danger font-weight-bold">{{__('after tax & discount')}}</small>
                                                </th>
                                                <th></th>
                                            </tr>
                                            @php
                                                $totalQuantity=0;
                                               $totalRate=0;
                                               $totalTaxPrice=0;
                                               $totalDiscount=0;
                                               $taxesData=[];
                                            @endphp



                                            @foreach($items as $key =>$item)

                                                {{-- @if(!empty($item->tax))
                                                    @php
                                                        $taxes=App\Models\Utility::tax($item->tax);
                                                        $totalQuantity+=$item->quantity;
                                                        $totalRate+=$item->price;
                                                        $totalDiscount+=$item->discount;
                                                        foreach($taxes as $taxe){
                                                            $taxDataPrice=App\Models\Utility::taxRate($taxe->rate,$item->price,$item->quantity,$item->discount);
                                                            if (array_key_exists($taxe->name,$taxesData))
                                                            {
                                                                $taxesData[$taxe->name] = $taxesData[$taxe->name]+$taxDataPrice;
                                                            }
                                                            else
                                                            {
                                                                $taxesData[$taxe->name] = $taxDataPrice;
                                                            }
                                                        }
                                                    @endphp
                                                @endif --}}

                                                @if(!empty($item->product_id))
                                                        <tr>
                                                            <td>{{$key+1}}</td>
                                                            
                                                            @php
                                                                $productName = $item->product;
                                                                $totalQuantity += $item->quantity;
                                                                $totalRate += $item->price;
                                                                $totalDiscount += $item->discount;
                                                                $taxPrice =0;
                                                            @endphp
                                                            <td>{{!empty($productName)?$productName->name:'-'}}</td>
                                                            <td>{{ $item->quantity . ' (' . $productName->unit->name . ')' }}</td>
                                                            <td>{{\Auth::user()->priceFormat($item->price)}}</td>
                                                            <td>{{\Auth::user()->priceFormat($item->discount)}}</td>

                                                            <td>
                                                                @if (!empty($item->tax))
                                                                    <table>
                                                                        @php
                                                                            $itemTaxes = [];
                                                                            $getTaxData = Utility::getTaxData();
        
                                                                            if (!empty($item->tax)) {
                                                                                foreach (explode(',', $item->tax) as $tax) {
                                                                                    $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $item->price, $item->quantity, $item->discount);
                                                                                    $totalTaxPrice += $taxPrice;
                                                                                    $itemTax['name'] = $getTaxData[$tax]['name'];
                                                                                    $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';
                                                                                    $itemTax['price'] = \Auth::user()->priceFormat($taxPrice);
        
                                                                                    $itemTaxes[] = $itemTax;
                                                                                    if (array_key_exists($getTaxData[$tax]['name'], $taxesData)) {
                                                                                        $taxesData[$getTaxData[$tax]['name']] = $taxesData[$getTaxData[$tax]['name']] + $taxPrice;
                                                                                    } else {
                                                                                        $taxesData[$getTaxData[$tax]['name']] = $taxPrice;
                                                                                    }
                                                                                }
                                                                                $item->itemTax = $itemTaxes;
                                                                            } else {
                                                                                $item->itemTax = [];
                                                                            }
                                                                        @endphp
                                                                        @foreach ($item->itemTax as $tax)
        
                                                                                <tr>
                                                                                    <td>{{$tax['name'] .' ('.$tax['rate'] .')'}}</td>
                                                                                    <td>{{ $tax['price']}}</td>
                                                                                </tr>
                                                                        @endforeach
                                                                    </table>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>

                                                            @php
                                                                $chartAccount = \App\Models\ChartOfAccount::find($item->chart_account_id);
                                                            @endphp

                                                            <td>{{!empty($chartAccount) ? $chartAccount->name : '-'}}</td>
                                                            <td>{{\Auth::user()->priceFormat($item->amount)}}</td>

                                                            <td>{{!empty($item->description)?$item->description:'-'}}</td>

                                                            <td class="text-end">{{\Auth::user()->priceFormat(($item->price * $item->quantity - $item->discount) + $taxPrice)}}</td>
                                                            <td></td>
                                                        </tr>
                                                    @else
                                                    <tr>
                                                        <td>{{$key+1}}</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        @php
                                                            $chartAccount = \App\Models\ChartOfAccount::find($item['chart_account_id']);
                                                        @endphp
                                                        <td>{{!empty($chartAccount) ? $chartAccount->name : '-'}}</td>
                                                        <td>{{\Auth::user()->priceFormat($item['amount'])}}</td>
                                                        <td>-</td>
                                                        <td class="text-end">{{\Auth::user()->priceFormat($item['amount'])}}</td>
                                                        <td></td>


                                                    </tr>

                                                @endif


                                            @endforeach
                                            <tfoot>
                                            <tr>
                                                <td></td>
                                                <td><b>{{__('Total')}}</b></td>
                                                <td><b>{{$totalQuantity}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalRate)}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalDiscount)}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalTaxPrice)}}</b></td>
                                                <td></td>
                                                <td><b>{{\Auth::user()->priceFormat($bill->getAccountTotal())}}</b></td>

                                            </tr>
                                            <tr>
                                                <td colspan="8"></td>
                                                <td class="text-end"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-end">{{\Auth::user()->priceFormat($bill->getSubTotal())}}</td>
                                            </tr>

                                                <tr>
                                                    <td colspan="8"></td>
                                                    <td class="text-end"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-end">{{\Auth::user()->priceFormat($bill->getTotalDiscount())}}</td>
                                                </tr>

                                            @if(!empty($taxesData))
                                                @foreach($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="8"></td>
                                                        <td class="text-end"><b>{{$taxName}}</b></td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="8"></td>
                                                <td class="blue-text text-end"><b>{{__('Total')}}</b></td>
                                                <td class="blue-text text-end">{{\Auth::user()->priceFormat($bill->getTotal())}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="8"></td>
                                                <td class="text-end"><b>{{__('Paid')}}</b></td>
                                                <td class="text-end">{{\Auth::user()->priceFormat(($bill->getTotal()-$bill->getDue())-($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="8"></td>
                                                <td class="text-end"><b>{{__('Debit Note')}}</b></td>
                                                <td class="text-end">{{\Auth::user()->priceFormat(($bill->billTotalDebitNote()))}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="8"></td>
                                                <td class="text-end"><b>{{__('Due')}}</b></td>
                                                <td class="text-end">{{\Auth::user()->priceFormat($bill->getDue())}}</td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="mb-5  d-inline-block">{{__('Payment Summary')}}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="text-dark">{{__('Payment Receipt')}}</th>
                                <th class="text-dark">{{__('Date')}}</th>
                                <th class="text-dark">{{__('Amount')}}</th>
                                <th class="text-dark">{{__('Account')}}</th>
                                <th class="text-dark">{{__('Reference')}}</th>
                                <th class="text-dark">{{__('Description')}}</th>
                                @can('delete payment bill')
                                    <th class="text-dark">{{__('Action')}}</th>
                                @endcan
                            </tr>
                            </thead>
                            @forelse($bill->payments as $key =>$payment)
                                <tr>
                                    <td>
                                        @if(!empty($payment->add_receipt))
                                            <a href="{{asset(Storage::url('uploads/payment')).'/'.$payment->add_receipt}}" download="" class="btn btn-sm btn-secondary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-download"></i></span></a>
                                            <a href="{{asset(Storage::url('uploads/payment')).'/'.$payment->add_receipt}}" class="btn btn-sm btn-secondary btn-icon rounded-pill" target="_blank"><span class="btn-inner--icon"><i class="ti ti-eye"></i></span></a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{\Auth::user()->dateFormat($payment->date)}}</td>
                                    <td>{{\Auth::user()->priceFormat($payment->amount)}}</td>
                                    <td>{{!empty($payment->bankAccount)?$payment->bankAccount->bank_name.' '.$payment->bankAccount->holder_name:''}}</td>
                                    <td>{{$payment->reference}}</td>
                                    <td>{{$payment->description}}</td>
                                    <td class="text-dark">
                                        @can('delete bill product')
                                            <div class="action-btn bg-danger ms-2">
                                                {!! Form::open(['method' => 'post', 'route' => ['bill.payment.destroy',$bill->id,$payment->id],'id'=>'delete-form-'.$payment->id]) !!}
                                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip"  title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$payment->id}}').submit();">
                                                        <i class="text-white ti ti-trash"></i>
                                                    </a>
                                                {!! Form::close() !!}
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-dark"><p>{{__('No Data Found')}}</p></td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    <h5 class="mb-5 d-inline-block">{{__('Debit Note Summary')}}</h5>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="text-dark">{{__('Date')}}</th>
                                <th class="text-dark">{{__('Amount')}}</th>
                                <th class="text-dark">{{__('Description')}}</th>
                                @if(Gate::check('edit debit note') || Gate::check('delete debit note'))
                                    <th class="text-dark">{{__('Action')}}</th>
                                @endif
                            </tr>
                            </thead>
                            @forelse($bill->debitNote as $key =>$debitNote)
                                <tr>
                                    <td>{{\Auth::user()->dateFormat($debitNote->date)}}</td>
                                    <td>{{\Auth::user()->priceFormat($debitNote->amount)}}</td>
                                    <td>{{$debitNote->description}}</td>
                                    <td>
                                        @can('edit debit note')
                                            <a data-url="{{ route('bill.edit.debit.note',[$debitNote->bill,$debitNote->id]) }}" data-ajax-popup="true" data-title="{{__('Add Debit Note')}}" href="#" class="mx-3 btn btn-sm align-items-center" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}">
                                                <i class="text-white ti ti-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('delete debit note')
                                                <div class="action-btn bg-danger ms-2">
                                                    {!! Form::open(['method' => 'DELETE', 'route' => array('bill.delete.debit.note', $debitNote->bill,$debitNote->id),'id'=>'delete-form-'.$debitNote->id]) !!}
                                                        <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para" data-bs-toggle="tooltip"  title="{{__('Delete')}}" data-original-title="{{__('Delete')}}" data-confirm="{{__('Are You Sure?').'|'.__('This action can not be undone. Do you want to continue?')}}" data-confirm-yes="document.getElementById('delete-form-{{$debitNote->id}}').submit();">
                                                            <i class="text-white ti ti-trash"></i>
                                                        </a>
                                                    {!! Form::close() !!}
                                                </div>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-dark"><p>{{__('No Data Found')}}</p></td>
                                </tr>
                            @endforelse
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
    $(document).ready(function(){
        $("#send_btn").click(function(event) {
            event.preventDefault();
            $(this).addClass("disabled").css("pointer-events", "none").attr("disabled", true);
            $("#send_btn").html("<i class='fa fa-spinner fa-spin'></i> Processing");
            window.location.href = $("#send_btn").data("href");
        });
    });
</script>
@endpush