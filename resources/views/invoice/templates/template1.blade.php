@php
    $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
@endphp
<!DOCTYPE html>
<html lang="en" dir="{{ $settings_data['SITE_RTL'] == 'on' ? 'rtl' : '' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style type="text/css">
        @page {
            margin: 0px;
        }

        body {
            font-family: 'Lato', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            color: #000000;
            position: relative;
        }

        .invoice-container {
            width: 100%;
            height: 100%;
            position: relative;
            background-image: url('{{ public_path('assets/invoice_logo/invoice.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #4e8fb8;
            text-align: center;
            padding-top: 185px;

        }

        .heading {
            color: #4e8fb8;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 20px;

        }

        .info {
            width: 100%;
            line-height: 1.8;
            padding-left: 20px;
        }

        .info1 {
            width: 100%;
            line-height: 1.8;
            padding-left: 30px;
        }

        .bill table tr {
            border: none !important;
        }

        .table-container {
            margin-top: 20px;
            width: 100%;
            padding-left: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container table {
            width: 90% !important;
        }

        table th,
        table td {
            padding: 8px;
            text-align: left;
        }


        table thead th {
            background-color: #dbe8eb;
            text-decoration: underline;
            padding: 10px;
        }

        tbody td {
            border-bottom: 1px dashed #4e8fb8;
        }


        .bank-info {
            width: 300px;
            padding-top: 10px;
            /* Adjusted for proper spacing */
            font-size: 12px;
            text-align: left;
            /* Align bank details to the left */
            padding-left: 30px;
        }

        .bank-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .bank-info table tr {
            border-bottom: 1px solid black;
        }

        .bank-info th {
            font-weight: 400;
        }

        .footer-sign {
            padding-top: 95px;
            */ padding-left: 30px;
        }
        .my-td
        {
            padding: 3px !important;
            margin: 3px !important;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Title Section -->
        <div class="title">{{ __('Tax Invoice') }}</div>
        <div class="">
            <table class="bill">
                <tr>
                    <td style="border-bottom:none !important; line-height: 1.8;">
                        <div class="info">
                            <strong class="heading">{{ __('Bill To') }}:</strong><br>
                            {{ $customer->billing_name ?? 'Name' }}<br>
                            {{ $customer->billing_address ?? 'Address' }}<br>
                            {{ $customer->billing_city ?? 'City' }}, {{ $customer->billing_state ?? 'State' }} -
                            {{ $customer->billing_zip ?? 'Zip Code' }}<br>
                            {{ $customer->billing_country ?? 'Country' }}<br>
                            TRN:104073808800003
                        </div>
                    </td>
                    <td style="border-bottom:none !important; line-height: 2.1;">

                        <span style="padding-left:350px;">{{ __('Invoice Number') }}:</span>
                        {{ Utility::invoiceNumberFormat($settings, $invoice->invoice_id) ?? '#INV0001' }}<br>
                        <span style="padding-left:350px;">{{ __('Invoice Date') }}:</span>
                        {{ Utility::dateFormat($settings, $invoice->issue_date) ?? 'Date' }}<br>
                        <span style="padding-left:350px;">{{ __('Due Date') }}:</span>
                        {{ Utility::dateFormat($settings, $invoice->due_date) ?? 'Date' }}<br>
                        <span style="padding-left:350px;">{{ __('TRN') }}:</span> 104073808800003
                       

                    </td>
                </tr>

            </table>
        </div>

        <!-- Billed By Section -->
        <div class="info-section">
            <div class="info1">
                <strong class="heading">{{ __('Billed By') }}:</strong><br>
                <span class="underline" style="border-bottom:1px solid black">Vendor Name:</span>&nbsp; Zero Gravity
                Advertisement Gifts<br>
                <span class="underline" style="border-bottom:1px solid black">Address:</span>&nbsp; Unit 802, Guardian
                Tower, Al Mustarhim St, Abu Dhabi<br>
                <span class="underline" style="border-bottom:1px solid black">PO Box:</span>&nbsp; 107018
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Item') }}</th>
                        <th>{{ __('Quantity') }}</th>
                        <th style="text-align: right;">{{ __('Rate') }}</th>
                        <th style="text-align: right;">{{ __('Discount') }}</th>
                        <th style="text-align: right;">{{ __('Tax') }}</th>
                        <th style="text-align: right;">{{ __('Price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if (isset($invoice->itemData) && count($invoice->itemData) > 0)
                        @foreach ($invoice->itemData as $key => $item)
                            <tr>
                                <td class="my-td">{{ $item->name }}</td>
                                @php
                                    $unitName = App\Models\ProductServiceUnit::find($item->unit);
                                @endphp
                                <td class="my-td">{{ $item->quantity }} {{ $unitName != null ? '(' . $unitName->name . ')' : '' }}
                                </td>
                                <td style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{ Utility::priceFormat($settings, $item->price, $invoice->currency_symbol) }}</span></td>
                                <td style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{ $item->discount != 0 ? Utility::priceFormat($settings, $item->discount, $invoice->currency_symbol) : '-' }}</span>
                                </td>
                                @php
                                    $itemtax = 0;
                                @endphp
                                <td style="text-align: right;" class="my-td">
                                    @if (!empty($item->itemTax))
                                        @foreach ($item->itemTax as $taxes)
                                            @php
                                                $itemtax += $taxes['tax_price'];
                                            @endphp
                                            <p>{{ $taxes['name'] }} ({{ $taxes['rate'] }}) <span style="font-family: DejaVu Sans; sans-serif;">{{ Utility::priceFormat($settings, $taxes['tax_price'], $invoice->currency_symbol) }}</span></p>
                                        @endforeach
                                    @else
                                        <span>-</span>
                                    @endif
                                </td>
                                <td style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{ Utility::priceFormat($settings, $item->price * $item->quantity - $item->discount + $itemtax, $invoice->currency_symbol) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                </tbody>
                <tfoot>
                    {{-- <tr>
                        <td>{{__('Total')}}</td>
                        <td>{{$invoice->totalQuantity}}</td>
                        <td style="text-align: right;"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->totalRate, $invoice->currency_symbol)}}</span></td>
                        <td style="text-align: right;"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->totalDiscount, $invoice->currency_symbol)}}</span></td>
                        <td style="text-align: right;"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->totalTaxPrice, $invoice->currency_symbol) }}</span></td>
                        <td style="text-align: right;"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->getSubTotal(), $invoice->currency_symbol)}}</span></td>
                    </tr> --}}
                    <tr>
                        <th style="text-align: right;" colspan="5" class="my-td">
                            {{__('SubTotal')}}:
                        </th>
                        <th style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->getSubTotal(), $invoice->currency_symbol)}}</span></th>
                    </tr>
                    @if($invoice->getTotalDiscount())
                    <tr>
                        <th style="text-align: right;" colspan="5" class="my-td">
                            {{__('Discount')}}:
                        </th>
                        <th style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->getTotalDiscount(), $invoice->currency_symbol)}}</span></th>
                    </tr>
                    @endif

                    @if(!empty($invoice->taxesData))
                        @foreach($invoice->taxesData as $taxName => $taxPrice)
                            <tr>
                                <th style="text-align: right;" colspan="5" class="my-td">
                                    {{ $taxName }} :
                                </th>
                                <th style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{ Utility::priceFormat($settings,$taxPrice, $invoice->currency_symbol)  }}</span></th>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <th style="text-align: right;" colspan="5" class="my-td">
                            {{__('Total')}}:
                        </th>
                        <th style="text-align: right;" class="my-td"><span style="font-family: DejaVu Sans; sans-serif;">{{Utility::priceFormat($settings,$invoice->getSubTotal()-$invoice->getTotalDiscount()+$invoice->getTotalTax(), $invoice->currency_symbol)}}</span></th>
                    </tr>
                  
            </table>
        </div>


        <!-- Bank Information -->
        <div class="bank-info">
            <table>
                <tr>
                    <th>{{ __('Bank Name') }}</th>
                    <td>{{ $settings['bank_name'] ?? 'National Bank of Fujairah' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Account Name') }}</th>
                    <td>{{ $settings['account_name'] ?? 'zero Gravity Advertisement Gifts' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Account Number') }}</th>
                    <td>{{ $settings['account_number'] ?? '012001973469' }}</td>
                </tr>
                <tr>
                    <th>{{ __('IBAN Number') }}</th>
                    <td>{{ $settings['iban_number'] ?? 'AE170380000012001973469' }}</td>
                </tr>
                <tr>
                    <th>{{ __('Swift Code') }}</th>
                    <td>{{ $settings['swift_code'] ?? 'NBFUAEAFDXB' }}</td>
                </tr>
                <tr style="border-bottom:none !important;">
                    <th>{{ __('Branch Name') }}</th>
                    <td style="border-bottom:none !important;">{{ $settings['branch'] ?? 'Abu Dhabi' }}</td>
                </tr>
            </table>
        </div>

        <table class="footer-sign">
            <tr>
                <td style="border-bottom:none !important;">
                    <strong style="padding-left:30px;">Zero Gravity Advertisement Gifts</strong>
                </td>
                <td style="border-bottom:none !important;">
                    <strong style="padding-right:30px;">Client Acknowledgement</strong>


                </td>
            </tr>

        </table>
    </div>
</body>

</html>
