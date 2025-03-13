@extends('layouts.admin')
@section('page-title')
    {{__('Pre Order Detail')}}
@endsection
@php
    $settings = Utility::settings();
@endphp
@push('script-page')
    <script>
        $(document).on('change', '.status_change', function () {
            var status = this.value;
            var url = $(this).data('url');
            $.ajax({
                url: url + '?status=' + status,
                type: 'GET',
                cache: false,
                success: function (data) {
                },
            });
        });

        $('.cp_link').on('click', function () {
            var value = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(value).select();
            document.execCommand("copy");
            $temp.remove();
            show_toastr('success', '{{__('Link Copy on Clipboard')}}')
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('pre_order.index')}}">{{__('Pre Order')}}</a></li>
    <li class="breadcrumb-item">{{__('Pre Order Details')}}</li>

@endsection


@section('content')

    @can('send proposal')
        @if($pre_order->status!=4)
            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row timeline-wrapper">
                                <div class="col-md-6 col-lg-4 col-xl-4 create_invoice">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-plus text-primary"></i>
                                    </div>
                                    <h6 class="text-primary my-3">{{__('Create Pre Order')}}</h6>
                                    <p class="text-muted text-sm mb-3"><i class="ti ti-clock mr-2"></i>{{__('Created on ')}}{{\Auth::user()->dateFormat($pre_order->issue_date)}}</p>
                                    @can('edit proposal')
                                        <a href="{{ route('pre_order.edit',\Crypt::encrypt($pre_order->id)) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-original-title="{{__('Edit')}}"><i class="ti ti-pencil mr-2"></i>{{__('Edit')}}</a>
                                    @endcan
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4 send_invoice">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-mail text-warning"></i>
                                    </div>
                                    <h6 class="text-warning my-3">{{__('Send Pre Order')}}</h6>
                                    <p class="text-muted text-sm mb-3">
                                        @if($pre_order->status!=0 && $pre_order->send_date)
                                            <i class="ti ti-clock mr-2"></i>{{__('Sent on')}} {{\Auth::user()->dateFormat($pre_order->send_date)}}
                                        @else
                                            @can('send proposal')
                                                <small>{{__('Status')}} : {{__('Not Sent')}}</small>
                                            @endcan
                                        @endif
                                    </p>

                                    @if($pre_order->status == 0 && (\Auth::user()->type == "company" || \Auth::user()->type == "Accountant"))
                                        @can('send proposal')
                                            <a id="send_btn" href="#" data-href="{{ route('pre_order.sent',$pre_order->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" data-original-title="{{__('Mark Sent')}}"><i class="ti ti-send mr-2"></i>{{__('Send')}}</a>
                                        @endcan
                                    @endif
                                </div>
                                <div class="col-md-6 col-lg-4 col-xl-4 create_invoice">
                                    <div class="timeline-icons"><span class="timeline-dots"></span>
                                        <i class="ti ti-report-money text-info"></i>
                                    </div>
                                    <h6 class="text-info my-3">{{__('Pre Order Status')}}</h6>
                                    <small>
                                        @if($pre_order->status == 0)
                                            <span class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 1)
                                            <span class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 2)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 3)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 4)
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @endif
                                    </small>
                                    <br>
                                    <div class="float-right mt-2 col-md-3 float-end ml-5" data-toggle="tooltip" data-original-title="{{__('Click to change status')}}">
                                        <select class="form-control status_change select2" name="status" data-url="{{route('pre_order.status.change',$pre_order->id)}}">
                                            @foreach($status as $k=>$val)
                                                <option value="{{$k}}" {{($pre_order->status==$k)?'selected':''}}>{{$val}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @if(\Auth::user()->type=='company')
        @if($pre_order->status!=0)
            <div class="row justify-content-between align-items-center mb-3">
                <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                    <div class="all-button-box mx-2">
                        <a href="{{ route('pre_order.resent',$pre_order->id) }}" class="btn btn-primary">{{__('Resend Pre Order')}}</a>
                    </div>
                    <div class="all-button-box">
                        <a href="{{ route('pre_order.pdf', Crypt::encrypt($pre_order->id))}}" class="btn btn-primary" target="_blank">{{__('Download')}}</a>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="row justify-content-between align-items-center mb-3">
            <div class="col-md-12 d-flex align-items-center justify-content-between justify-content-md-end">
                <div class="all-button-box">
                    <a href="{{ route('pre_order.pdf', Crypt::encrypt($pre_order->id))}}" class="btn btn-xs btn-white btn-icon-only width-auto" target="_blank">{{__('Download')}}</a>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="invoice">
                        <div class="invoice-print">
                            <div class="row invoice-title mt-2">
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12">
                                    <h4>{{__('Pre Order')}}</h4>
                                </div>
                                <div class="col-xs-12 col-sm-12 col-nd-6 col-lg-6 col-12 text-end">
                                    <h4 class="invoice-number">{{ Auth::user()->preOrderNumberFormat($pre_order->pre_order_id) }}</h4>
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
                                                {{\Auth::user()->dateFormat($pre_order->issue_date)}}<br><br>
                                            </small>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <small class="font-style">
                                        <strong>{{__('Billed To')}} :</strong><br>
                                        @if(!empty($vender->billing_name))
                                            {{!empty($vender->billing_name)?$vender->billing_name:''}}<br>
                                            {{!empty($vender->billing_address)?$vender->billing_address:''}}<br>
                                            {{!empty($vender->billing_city)?$vender->billing_city:'' .', '}}<br>
                                            {{!empty($vender->billing_state)?$vender->billing_state:'',', '}},
                                            {{!empty($vender->billing_zip)?$vender->billing_zip:''}}<br>
                                            {{!empty($vender->billing_country)?$vender->billing_country:''}}<br>
                                            {{!empty($vender->billing_phone)?$vender->billing_phone:''}}<br>

                                            @if($settings['vat_gst_number_switch'] == 'on')
                                                <strong>{{__('Tax Number ')}} : </strong>{{!empty($vender->tax_number)?$vender->tax_number:''}}
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
                                            @if(!empty($vender->shipping_name))
                                                {{!empty($vender->shipping_name)?$vender->shipping_name:''}}<br>
                                                {{!empty($vender->shipping_address)?$vender->shipping_address:''}}<br>
                                                {{!empty($vender->shipping_city)?$vender->shipping_city:'' . ', '}}<br>
                                                {{!empty($vender->shipping_state)?$vender->shipping_state:'' .', '}},
                                                {{!empty($vender->shipping_zip)?$vender->shipping_zip:''}}<br>
                                                {{!empty($vender->shipping_country)?$vender->shipping_country:''}}<br>
                                                {{!empty($vender->shipping_phone)?$vender->shipping_phone:''}}<br>
                                            @else
                                            -
                                            @endif
                                        </small>
                                    </div>
                                @endif
                                    <div class="col">
                                        <div class="float-end mt-3">
                                        {!! DNS2D::getBarcodeHTML( route('pre_order.link.copy',\Illuminate\Support\Facades\Crypt::encrypt($pre_order->id)), "QRCODE",2,2) !!}
                                        </div>
                                    </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col">
                                    <small>
                                        <strong>{{__('Status')}} :</strong><br>
                                        @if($pre_order->status == 0)
                                            <span class="badge bg-primary p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 1)
                                            <span class="badge bg-info p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 2)
                                            <span class="badge bg-success p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 3)
                                            <span class="badge bg-warning p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @elseif($pre_order->status == 4)
                                            <span class="badge bg-danger p-2 px-3 rounded">{{ __(\App\Models\PreOrder::$statues[$pre_order->status]) }}</span>
                                        @endif
                                    </small>
                                </div>


                            </div>

                            @if(!empty($customFields) && count($pre_order->customField)>0)
                                @foreach($customFields as $field)
                                    <div class="col text-end">
                                        <small>
                                            <strong>{{$field->name}} :</strong><br>
                                            {{!empty($pre_order->customField)?$pre_order->customField[$field->id]:'-'}}
                                            <br><br>
                                        </small>
                                    </div>
                                @endforeach
                            @endif
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="font-weight-bold">{{__('Product Summary')}}</div>
                                    <small>{{__('All items here cannot be deleted.')}}</small>
                                    <div class="table-responsive mt-2">
                                        <table class="table mb-0 invoice-body">
                                            <thead>
                                                <tr>
                                                <th class="text-dark" data-width="40">#</th>
                                                <th class="text-dark">{{__('Product')}}</th>
                                                <th class="text-dark">{{__('Quantity')}}</th>
                                                <th class="text-dark">{{__('Rate')}}</th>
                                                    <th class="text-dark"> {{__('Discount')}}</th>
                                                <th class="text-dark">{{__('Tax')}}</th>

                                                <th class="text-dark">{{__('Description')}}</th>
                                                <th class="text-end text-dark" width="12%">{{__('Price')}}<br>
                                                    <small class="text-danger font-weight-bold">{{__('after tax & discount')}}</small>
                                                </th>
                                            </tr>
                                            </thead>

                                            @php
                                                $totalQuantity=0;
                                                $totalRate=0;
                                                $totalTaxPrice=0;
                                                $totalDiscount=0;
                                                $taxesData=[];
                                            @endphp

                                            @foreach($iteams as $key =>$iteam)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    @php
                                                        $productName = $iteam->product;
                                                        $totalQuantity += $iteam->quantity;
                                                        $totalRate += $iteam->price;
                                                        $totalDiscount += $iteam->discount;
                                                        $taxPrice =0;
                                                    @endphp
                                                    <td>{{ !empty($productName) ? $productName->name : '' }}</td>
                                                    <td>{{ $iteam->quantity . ' (' . $productName->unit->name . ')' }}</td>
                                                    <td>{{\Auth::user()->priceFormat($iteam->price)}}</td>
                                                    <td>{{\Auth::user()->priceFormat($iteam->discount)}}</td>
                                                    <td>
                                                        @if (!empty($iteam->tax))
                                                            <table>
                                                                @php
                                                                    $itemTaxes = [];
                                                                    $getTaxData = Utility::getTaxData();
                                                            
                                                                    if (!empty($iteam->tax)) {
                                                                        foreach (explode(',', $iteam->tax) as $tax) {
                                                                            $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $iteam->price, $iteam->quantity, $iteam->discount);
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
                                                                        $iteam->itemTax = $itemTaxes;
                                                                    } else {
                                                                        $iteam->itemTax = [];
                                                                    }
                                                                @endphp
                                                                @foreach ($iteam->itemTax as $tax)

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

                                                    <td>{{!empty($iteam->description)?$iteam->description:'-'}}</td>
                                                    <td class="text-end">{{\Auth::user()->priceFormat(($iteam->price * $iteam->quantity - $iteam->discount) + $taxPrice)}}</td>
                                                </tr>
                                            @endforeach
                                            <tfoot>
                                            <tr>
                                                <td></td>
                                                <td><b>{{__('Total')}}</b></td>
                                                <td><b>{{$totalQuantity}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalRate)}}</b></td>
                                                <td><b>{{\Auth::user()->priceFormat($totalDiscount)}}</b>
                                                <td><b>{{\Auth::user()->priceFormat($totalTaxPrice)}}</b></td>

                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="text-end"><b>{{__('Sub Total')}}</b></td>
                                                <td class="text-end">{{\Auth::user()->priceFormat($pre_order->getSubTotal())}}</td>
                                            </tr>

                                                <tr>
                                                    <td colspan="6"></td>
                                                    <td class="text-end"><b>{{__('Discount')}}</b></td>
                                                    <td class="text-end">{{\Auth::user()->priceFormat($pre_order->getTotalDiscount())}}</td>
                                                </tr>

                                            @if(!empty($taxesData))
                                                @foreach($taxesData as $taxName => $taxPrice)
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td class="text-end"><b>{{$taxName}}</b></td>
                                                        <td class="text-end">{{ \Auth::user()->priceFormat($taxPrice) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr>
                                                <td colspan="6"></td>
                                                <td class="blue-text text-end"><b>{{__('Total')}}</b></td>
                                                <td class="blue-text text-end">{{\Auth::user()->priceFormat($pre_order->getTotal())}}</td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                        <div class="invoice-footer">
                                            <b>{{$settings['footer_title']}}</b> <br>
                                            {{-- {!! $settings['footer_notes'] !!} --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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