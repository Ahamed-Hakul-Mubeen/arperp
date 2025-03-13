<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Models\Advance;
use App\Models\BankAccount;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\InvoiceBankTransfer;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\Plan;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use App\Models\TransactionLines;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class InvoiceController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage invoice')) {
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');
            $status = Invoice::$statues;
            $query = Invoice::with('createdUser')->where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }
            if (count(explode('to', $request->issue_date)) > 1) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (!empty($request->issue_date)) {
                $date_range = [$request->issue_date, $request->issue_date];
                $query->whereBetween('issue_date', $date_range);
            }
            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($customerId)
    {
        if (\Auth::user()->can('create invoice')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('--', '');

            $currency = Currency::select('currency_code', 'currency_symbol')->where('created_by', \Auth::user()->creatorId())->get();
            return view('invoice.create', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId', 'currency'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();
        return view('invoice.customer_detail', compact('customer'));
    }

    public function product(Request $request)
    {
        $currency_code = $request->currency_code;

        if ($currency_code != \Auth::user()->currencyCode()) {
            $exchange_rate = ExchangeRate::where("from_currency", $currency_code)->where("to_currency", \Auth::user()->currencyCode())->first();
            if (!$exchange_rate) {
                return json_encode(array("status" => 0, "message" => __("No conversion rate found")));
            }
            $product = ProductService::find($request->product_id);
            $product->sale_price = number_format($product->sale_price / $exchange_rate->exchange_rate, 2, ".", "");
            $data['exchange_rate'] = $exchange_rate->exchange_rate;
            $data['product'] = $product;
        } else {
            $data['exchange_rate'] = 1;
            $data['product'] = $product = ProductService::find($request->product_id);
        }
        $data['unit'] = (!empty($product->unit)) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->sale_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function store(Request $request)
    {
        //    dd($request->all());
        if (\Auth::user()->can('create invoice')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'customer_id' => 'required',
                    'invoice_number' => 'required',
                    'issue_date' => 'required',
                    'due_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',
                    'currency_code' => 'required'
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }
            $status = Invoice::$statues;
            $invoice = new Invoice();
            $invoice->invoice_id = $this->invoiceNumber();
            $invoice->actual_invoice_number = $request->invoice_number;
            $invoice->customer_id = $request->customer_id;
            $invoice->status = 0;
            $invoice->issue_date = $request->issue_date;
            $invoice->due_date = $request->due_date;
            $invoice->category_id = $request->category_id;
            $invoice->ref_number = $request->ref_number;
            $invoice->currency_code = $request->currency_code;
            $invoice->currency_symbol = $request->currency_symbol;
            $invoice->exchange_rate = $request->exchange_rate;
            //            $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
            $invoice->created_by = \Auth::user()->creatorId();
            $invoice->created_user = \Auth::user()->id;
            $invoice->save();
            CustomField::saveData($invoice, $request->customField);
            $products = $request->items;

            for ($i = 0; $i < count($products); $i++) {

                $invoiceProduct = new InvoiceProduct();
                $invoiceProduct->invoice_id = $invoice->id;
                $invoiceProduct->product_id = $products[$i]['item'];
                $invoiceProduct->quantity = $products[$i]['quantity'];
                $invoiceProduct->tax = $products[$i]['tax'];
                //                $invoiceProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                $invoiceProduct->discount = $products[$i]['discount'];
                $invoiceProduct->price = $products[$i]['price'];
                $invoiceProduct->description = $products[$i]['description'];
                $invoiceProduct->save();

                //inventory management (Quantity)
                Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

                //For Notification
                $setting = Utility::settings(\Auth::user()->creatorId());
                $customer = Customer::find($request->customer_id);
                $invoiceNotificationArr = [
                    'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
                    'user_name' => \Auth::user()->name,
                    'invoice_issue_date' => $invoice->issue_date,
                    'invoice_due_date' => $invoice->due_date,
                    'customer_name' => $customer->name,
                ];
                //Slack Notification
                if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
                    Utility::send_slack_msg('new_invoice', $invoiceNotificationArr);
                }
                //Telegram Notification
                if (isset($setting['telegram_invoice_notification']) && $setting['telegram_invoice_notification'] == 1) {
                    Utility::send_telegram_msg('new_invoice', $invoiceNotificationArr);
                }
                //Twilio Notification
                if (isset($setting['twilio_invoice_notification']) && $setting['twilio_invoice_notification'] == 1) {
                    Utility::send_twilio_msg($customer->contact, 'new_invoice', $invoiceNotificationArr);
                }
            }

            //Product Stock Report
            $type = 'invoice';
            $type_id = $invoice->id;
            StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
            $description = $invoiceProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $type_id);

            //webhook
            $module = 'New Invoice';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($invoice);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {
        if (\Auth::user()->can('edit invoice')) {
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::find($id);
            $invoice_number = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $currency = Currency::select('currency_code', 'currency_symbol')->where('created_by', \Auth::user()->creatorId())->get();
            return view('invoice.edit', compact('customers', 'product_services', 'invoice', 'invoice_number', 'category', 'customFields', 'currency'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        // dd($request->all());
        if (\Auth::user()->can('edit invoice')) {
            if ($invoice->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'invoice_number' => 'required',
                        'issue_date' => 'required',
                        'due_date' => 'required',
                        'category_id' => 'required',
                        'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('invoice.index')->with('error', $messages->first());
                }
                if($invoice->customer_id != 0 && $invoice->status != 0)
                {
                    // Log::info($invoice->customer_id." - ".$invoice->getDue()." - ".'credit');
                    Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getDue() * $invoice->exchange_rate, 'credit');
                }

                $invoice->customer_id = $request->customer_id;
                $invoice->issue_date = $request->issue_date;
                $invoice->due_date = $request->due_date;
                $invoice->ref_number = $request->ref_number;
                $invoice->actual_invoice_number = $request->invoice_number;
                $invoice->exchange_rate = $request->exchange_rate;
                //                $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $invoice->category_id = $request->category_id;
                $invoice->created_user = \Auth::user()->id;
                $invoice->save();

                Utility::starting_number($invoice->invoice_id + 1, 'invoice');
                CustomField::saveData($invoice, $request->customField);
                $products = $request->items;

                for ($i = 0; $i < count($products); $i++) {
                    $invoiceProduct = InvoiceProduct::find($products[$i]['id']);

                    if ($invoiceProduct == null) {
                        $invoiceProduct = new InvoiceProduct();
                        $invoiceProduct->invoice_id = $invoice->id;

                        Utility::total_quantity('minus', $products[$i]['quantity'], $products[$i]['item']);

                        // $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);
                        // Utility::updateUserBalance('customer', $request->customer_id, $updatePrice * $invoice->exchange_rate, 'credit');
                    } else {
                        Utility::total_quantity('plus', $invoiceProduct->quantity, $invoiceProduct->product_id);
                    }

                    if (isset($products[$i]['item'])) {
                        $invoiceProduct->product_id = $products[$i]['item'];
                    }

                    $invoiceProduct->quantity = $products[$i]['quantity'];
                    $invoiceProduct->tax = $products[$i]['tax'];
                    $invoiceProduct->discount = $products[$i]['discount'];
                    $invoiceProduct->price = $products[$i]['price'];
                    $invoiceProduct->description = $products[$i]['description'];
                    $invoiceProduct->save();

                    if ($products[$i]['id'] > 0) {
                        Utility::total_quantity('minus', $products[$i]['quantity'], $invoiceProduct->product_id);
                    }

                    //Product Stock Report
                    $type = 'invoice';
                    $type_id = $invoice->id;
                    StockReport::where('type', '=', 'invoice')->where('type_id', '=', $invoice->id)->delete();
                    $description = $products[$i]['quantity'] . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
                    if (empty($products[$i]['id'])) {
                        Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
                    }
                }

                TransactionLines::where('reference_id', $invoice->id)->where('reference', 'Invoice')->delete();

                $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
                foreach ($invoice_products as $invoice_product) {
                    $product = ProductService::find($invoice_product->product_id);
                    $totalTaxPrice = 0;
                    $taxPrice = 0;
                    if ($invoice_product->tax != null) {
                        $taxes = \App\Models\Utility::tax($invoice_product->tax);
                        foreach ($taxes as $tax) {
                            $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                            $totalTaxPrice += $taxPrice;
                        }
                    }

                    $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $taxPrice;
                    $product_price = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount);

                    if($invoice->status != 0)
                    {
                        // Sales Income
                        $data = [
                            'account_id' => $product->sale_chartaccount_id,
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $product_price * $invoice->exchange_rate,
                            'reference' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'reference_sub_id' => $product->id,
                            'date' => $invoice->issue_date,
                        ];
                        Utility::addTransactionLines($data, "new");

                        $chart_accounts = ChartOfAccount::where('code', 2150)->where('created_by', \Auth::user()->creatorId())->first();
                        $data = [
                            'account_id' => $chart_accounts->id,
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $totalTaxPrice * $invoice->exchange_rate,
                            'reference' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'reference_sub_id' => $product->id,
                            'date' => $invoice->issue_date,
                        ];
                        Utility::addTransactionLines($data, "new");

                        // Account Recivable
                        $account_recivable = ChartOfAccount::where('code', 1200)->where('created_by', \Auth::user()->creatorId())->first();
                        $data2 = [
                            'account_id' => $account_recivable->id,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $itemAmount * $invoice->exchange_rate,
                            'reference' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'reference_sub_id' => $product->id,
                            'date' => $invoice->issue_date,
                        ];
                        Utility::addTransactionLines($data2, "new");


                        if($invoice->customer_id != 0 && $invoice->status != 0)
                        {
                            // Log::info($invoice->customer_id." - ".$itemAmount." - ".'debit');
                            Utility::updateUserBalance('customer', $invoice->customer_id, $itemAmount * $invoice->exchange_rate, 'debit');
                        }


                        $purchase_price = $product->purchase_price * $invoice_product->quantity;

                        // Cost of Sales on service
                        $cof_sale = ChartOfAccount::where('code', 5005)->where('created_by', \Auth::user()->creatorId())->first();
                        $data = [
                            'account_id' => $cof_sale->id,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $purchase_price * $invoice->exchange_rate,
                            'reference' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'reference_sub_id' => $product->id,
                            'date' => $invoice->issue_date,
                        ];
                        Utility::addTransactionLines($data, "new");

                        // Inventory
                        $inventory = ChartOfAccount::where('code', 1510)->where('created_by', \Auth::user()->creatorId())->first();
                        $data = [
                            'account_id' => $inventory->id,
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $purchase_price * $invoice->exchange_rate,
                            'reference' => 'Invoice',
                            'reference_id' => $invoice->id,
                            'reference_sub_id' => $product->id,
                            'date' => $invoice->issue_date,
                        ];
                        Utility::addTransactionLines($data, "new");
                    }
                }

                return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function invoiceNumber()
    {
        $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }

    public function show($ids)
    {
        if (\Auth::user()->can('show invoice')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Invoice Not Found.'));
            }
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::with(['creditNote', 'payments.bankAccount', 'items.product.unit'])->find($id);

            if (!empty($invoice->created_by) == \Auth::user()->creatorId()) {
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();

                $customer = $invoice->customer;
                $iteams = $invoice->items;
                $user = \Auth::user();

                // start for storage limit note
                $invoice_user = User::find($invoice->created_by);
                $user_plan = Plan::getPlan($invoice_user->plan);
                // end for storage limit note

                $invoice->customField = CustomField::getData($invoice, 'invoice');
                $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();

                $creditnote = CreditNote::where('invoice', $invoice->id)->first();

                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'invoice_user', 'user_plan', 'creditnote'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Invoice $invoice, Request $request)
    {
        if (\Auth::user()->can('delete invoice')) {
            if ($invoice->created_by == \Auth::user()->creatorId()) {
                foreach ($invoice->payments as $invoices) {
                    Utility::bankAccountBalance($invoices->account_id, $invoices->amount * $invoice->exchange_rate, 'debit');

                    $invoicepayment = InvoicePayment::find($invoices->id);
                    $invoices->delete();
                    if ($invoicepayment->advance_id && $invoicepayment->advance_amount) {
                        $advance = Advance::find($invoicepayment->advance_id);
                        $advance->balance += $invoicepayment->advance_amount;
                        $advance->status = 0;
                        $advance->save();
                    }
                    $invoicepayment->delete();
                }

                if ($invoice->customer_id != 0 && $invoice->status != 0) {
                    // Log::info($invoice->customer_id." - ".$invoice->getDue()." - ".'credit');
                    Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getDue() * $invoice->exchange_rate, 'credit');
                }


                TransactionLines::where('reference_id', $invoice->id)->where('reference', 'Invoice')->delete();
                TransactionLines::where('reference_id', $invoice->id)->Where('reference', 'Invoice Payment')->delete();

                CreditNote::where('invoice', '=', $invoice->id)->delete();

                InvoiceProduct::where('invoice_id', '=', $invoice->id)->delete();
                $invoice->delete();
                return redirect()->route('invoice.index')->with('success', __('Invoice successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete invoice product')) {
            $invoiceProduct = InvoiceProduct::find($request->id);

            if ($invoiceProduct) {
                $invoice = Invoice::find($invoiceProduct->invoice_id);
                $productService = ProductService::find($invoiceProduct->product_id);

                if($invoice->customer_id != 0 && $invoice->status != 0)
                {
                    // Log::info($invoice->customer_id." - ".$invoice->getDue()." - ".'credit');
                    Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount * $invoice->exchange_rate, 'credit');
                    TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Invoice')->delete();
                }

                InvoiceProduct::where('id', '=', $request->id)->delete();
            }


            return redirect()->back()->with('success', __('Invoice product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerInvoice(Request $request)
    {
        if (\Auth::user()->can('manage customer invoice')) {

            $status = Invoice::$statues;
            $query = Invoice::where('customer_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->issue_date)) {
                $date_range = explode(' - ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function customerInvoiceShow($id)
    {

        $invoice = Invoice::with('payments.bankAccount')->find($id);

        $user = User::where('id', $invoice->created_by)->first();
        if ($invoice->created_by == $user->creatorId()) {
            $customer = $invoice->customer;
            $iteams = $invoice->items;

            if ($user->type == 'super admin') {
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'user'));
            } elseif ($user->type == 'company') {
                return view('invoice.customer_invoice', compact('invoice', 'customer', 'iteams', 'user'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {

        if (\Auth::user()->can('send invoice')) {
            // Send Email
            $setings = Utility::settings();

            if ($setings['customer_invoice_sent'] == 1) {
                $invoice = Invoice::where('id', $id)->first();
                $invoice->send_date = date('Y-m-d');
                $invoice->status = 1;
                $invoice->save();

                $customer = Customer::where('id', $invoice->customer_id)->first();
                $invoice->name = !empty($customer) ? $customer->name : '';
                $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

                $invoiceId = Crypt::encrypt($invoice->id);
                $invoice->url = route('invoice.pdf', $invoiceId);

                // Log::info($invoice->customer_id." - ".$invoice->getDue()." - ".'debit');
                Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal() * $invoice->exchange_rate, 'debit');

                $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
                foreach ($invoice_products as $invoice_product) {
                    $product = ProductService::find($invoice_product->product_id);
                    $totalTaxPrice = 0;
                    if ($invoice_product->tax != null) {
                        $taxes = \App\Models\Utility::tax($invoice_product->tax);
                        foreach ($taxes as $tax) {
                            $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                            $totalTaxPrice += $taxPrice;
                        }
                    }

                    $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount) + $totalTaxPrice;
                    $product_price = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount);

                    // Sales Income
                    $data = [
                        'account_id' => $product->sale_chartaccount_id,
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $product_price * $invoice->exchange_rate,
                        'reference' => 'Invoice',
                        'reference_id' => $invoice->id,
                        'reference_sub_id' => $product->id,
                        'date' => $invoice->issue_date,
                    ];
                    Utility::addTransactionLines($data, "new");

                    $chart_accounts = ChartOfAccount::where('code', 2150)->where('created_by', \Auth::user()->creatorId())->first();
                    $data = [
                        'account_id' => $chart_accounts->id,
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $totalTaxPrice * $invoice->exchange_rate,
                        'reference' => 'Invoice',
                        'reference_id' => $invoice->id,
                        'reference_sub_id' => $product->id,
                        'date' => $invoice->issue_date,
                    ];
                    Utility::addTransactionLines($data, "new");

                    // Account Recivable
                    $account_recivable = ChartOfAccount::where('code', 1200)->where('created_by', \Auth::user()->creatorId())->first();
                    $data2 = [
                        'account_id' => $account_recivable->id,
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $itemAmount * $invoice->exchange_rate,
                        'reference' => 'Invoice',
                        'reference_id' => $invoice->id,
                        'reference_sub_id' => $product->id,
                        'date' => $invoice->issue_date,
                    ];
                    // dd($data);
                    Utility::addTransactionLines($data2, "new");


                    $purchase_price = $product->purchase_price * $invoice_product->quantity;

                    // Cost of Sales on service
                    $cof_sale = ChartOfAccount::where('code', 5005)->where('created_by', \Auth::user()->creatorId())->first();
                    $data = [
                        'account_id' => $cof_sale->id,
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $purchase_price * $invoice->exchange_rate,
                        'reference' => 'Invoice',
                        'reference_id' => $invoice->id,
                        'reference_sub_id' => $product->id,
                        'date' => $invoice->issue_date,
                    ];
                    // dd($data);
                    Utility::addTransactionLines($data, "new");

                    // Inventory
                    $inventory = ChartOfAccount::where('code', 1510)->where('created_by', \Auth::user()->creatorId())->first();
                    $data = [
                        'account_id' => $inventory->id,
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $purchase_price * $invoice->exchange_rate,
                        'reference' => 'Invoice',
                        'reference_id' => $invoice->id,
                        'reference_sub_id' => $product->id,
                        'date' => $invoice->issue_date,
                    ];
                    // dd($data);
                    Utility::addTransactionLines($data, "new");
                }

                $customerArr = [

                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'invoice_name' => $customer->name,
                    'invoice_number' => $invoice->invoice,
                    'invoice_url' => $invoice->url,

                ];
                $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

                return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send invoice')) {
            $invoice = Invoice::where('id', $id)->first();

            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->name = !empty($customer) ? $customer->name : '';
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $invoiceId = Crypt::encrypt($invoice->id);
            $invoice->url = route('invoice.pdf', $invoiceId);
            $customerArr = [

                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'invoice_name' => $customer->name,
                'invoice_number' => $invoice->invoice,
                'invoice_url' => $invoice->url,

            ];
            $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $customerArr);

            return redirect()->back()->with('success', __('Invoice successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($invoice_id)
    {
        if (\Auth::user()->can('create payment invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $advance = Advance::select("advance_id", "id", "balance", "date", "account_id", "currency_symbol")->where("customer_id", $invoice->customer_id)->where('currency_code', $invoice->currency_code)->where("status", 0)->where('created_by', \Auth::user()->creatorId())->get();
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            return view('invoice.payment', compact('accounts', 'invoice', 'advance'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $invoice_id)
    {
        // dd($request->all());
        $invoice = Invoice::find($invoice_id);
        if ($invoice->getDue() < $request->amount) {
            return redirect()->back()->with('error', __('Invoice payment amount should not greater than subtotal.'));
        }

        if (\Auth::user()->can('create payment invoice')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $invoicePayment = new InvoicePayment();
            $invoicePayment->invoice_id = $invoice_id;
            $invoicePayment->date = $request->date;
            $invoicePayment->amount = $request->amount;
            $invoicePayment->account_id = $request->account_id;
            $invoicePayment->payment_method = 0;
            $invoicePayment->reference = $request->reference;
            $invoicePayment->description = $request->description;
            if (!empty($request->add_receipt)) {
                //storage limit
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                if ($result == 1) {
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $request->add_receipt->storeAs('uploads/payment', $fileName);
                    $invoicePayment->add_receipt = $fileName;
                }
            }

            $invoicePayment->save();

            $invoice = Invoice::where('id', $invoice_id)->first();
            $due = $invoice->getDue();
            $total = $invoice->getTotal();
            if ($invoice->status == 0) {
                $invoice->send_date = date('Y-m-d');
                $invoice->save();
            }

            if ($due <= 0) {
                $invoice->status = 4;
                $invoice->save();
            } else {
                $invoice->status = 3;
                $invoice->save();
            }
            $invoicePayment->user_id = $invoice->customer_id;
            $invoicePayment->user_type = 'Customer';
            $invoicePayment->type = 'Partial';
            $invoicePayment->created_by = \Auth::user()->id;
            $invoicePayment->payment_id = $invoicePayment->id;
            $invoicePayment->category = 'Invoice';
            $invoicePayment->account = $request->account_id;

            Transaction::addTransaction($invoicePayment);
            $customer = Customer::where('id', $invoice->customer_id)->first();

            $payment = new InvoicePayment();
            $payment->name = $customer['name'];
            $payment->date = \Auth::user()->dateFormat($request->date);
            $payment->amount = \Auth::user()->priceFormat($request->amount);
            $payment->invoice = 'invoice ' . \Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $payment->dueAmount = \Auth::user()->priceFormat($invoice->getDue());

            if (!($request->advance_id)) {
                
                // Log::info($invoice->customer_id." - ".$request->amount." - ".'credit');
                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount * $invoice->exchange_rate, 'credit');
                Utility::bankAccountBalance($request->account_id, $request->amount * $invoice->exchange_rate, 'credit');

                $accountId = BankAccount::find($request->account_id);
                $data = [
                    'account_id' => $accountId->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->amount * $invoice->exchange_rate,
                    'reference' => 'Invoice Payment',
                    'reference_id' => $invoice_id,
                    'reference_sub_id' => $invoicePayment->id,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");
            }
            // account_recivable

            $account_recivable = ChartOfAccount::where('code', 1200)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $account_recivable->id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $request->amount * $invoice->exchange_rate,
                'reference' => 'Invoice Payment',
                'reference_id' => $invoice_id,
                'reference_sub_id' => $invoicePayment->id,
                'date' => $request->date,
            ];
            Utility::addTransactionLines($data, "new");

            if ($request->advance_id) {
                $unearned_revenue = ChartOfAccount::where('code', 2040)->where('created_by', \Auth::user()->creatorId())->first();
                $data = [
                    'account_id' => $unearned_revenue->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->amount * $invoice->exchange_rate,
                    'reference' => 'Invoice Payment',
                    'reference_id' => $invoice_id,
                    'reference_sub_id' => $invoicePayment->id,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");

                $advance = Advance::find($request->advance_id);
                $advance->balance = $advance->balance - $request->amount;
                if ($advance->balance == 0) {
                    $advance->status = 1;
                }
                $advance->save();
                $invoicePayment = InvoicePayment::find($invoicePayment->id);
                $invoicePayment->advance_id = $request->advance_id;
                $invoicePayment->advance_amount = $request->amount;
                $invoicePayment->save();
            }


            // Send Email
            $setings = Utility::settings();
            if ($setings['new_invoice_payment'] == 1) {
                $customer = Customer::where('id', $invoice->customer_id)->first();
                $invoicePaymentArr = [
                    'invoice_payment_name' => $customer->name,
                    'invoice_payment_amount' => $payment->amount,
                    'invoice_payment_date' => $payment->date,
                    'payment_dueAmount' => $payment->dueAmount,

                ];

                $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $invoicePaymentArr);
            }

            //webhook
            $module = 'New Invoice Payment';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($invoice);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }
            return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
        }
    }

    public function paymentDestroy(Request $request, $invoice_id, $payment_id)
    {
        //        dd($invoice_id,$payment_id);

        if (\Auth::user()->can('delete payment invoice')) {
            $payment = InvoicePayment::find($payment_id);

            if ($payment->advance_id && $payment->advance_amount) {
                $advance = Advance::find($payment->advance_id);
                $advance->balance += $payment->advance_amount;
                $advance->status = 0;
                $advance->save();
            }
            InvoicePayment::where('id', '=', $payment_id)->delete();

            InvoiceBankTransfer::where('id', '=', $payment_id)->delete();

            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Invoice Payment')->delete();

            $invoice = Invoice::where('id', $invoice_id)->first();
            $due = $invoice->getDue();
            $total = $invoice->getTotal();

            if ($due > 0 && $total != $due) {
                $invoice->status = 3;
            } else {
                $invoice->status = 2;
            }

            if (!empty($payment->add_receipt)) {
                //storage limit
                $file_path = '/uploads/payment/' . $payment->add_receipt;
                $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
            }

            $invoice->save();
            $type = 'Partial';
            $user = 'Customer';
            Transaction::destroyTransaction($payment_id, $type, $user);

            // Log::info($invoice->customer_id." - ".$request->amount." - ".'debit');
            Utility::updateUserBalance('customer', $invoice->customer_id, $payment->amount * $invoice->exchange_rate, 'debit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount * $invoice->exchange_rate, 'debit');

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function paymentReminder($invoice_id)
    {

        //        dd($invoice_id);
        $invoice = Invoice::find($invoice_id);
        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
        $invoice->name = $customer['name'];
        $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        //For Notification
        $setting = Utility::settings(\Auth::user()->creatorId());
        $customer = Customer::find($invoice->customer_id);
        $reminderNotificationArr = [
            'invoice_number' => \Auth::user()->invoiceNumberFormat($invoice->invoice_id),
            'customer_name' => $customer->name,
            'user_name' => \Auth::user()->name,
        ];

        //Twilio Notification
        if (isset($setting['twilio_reminder_notification']) && $setting['twilio_reminder_notification'] == 1) {
            Utility::send_twilio_msg($customer->contact, 'invoice_payment_reminder', $reminderNotificationArr);
        }

        // Send Email
        $setings = Utility::settings();
        if ($setings['new_payment_reminder'] == 1) {
            $invoice = Invoice::find($invoice_id);
            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->dueAmount = \Auth::user()->priceFormat($invoice->getDue());
            $invoice->name = $customer['name'];
            $invoice->date = \Auth::user()->dateFormat($invoice->send_date);
            $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $reminderArr = [

                'payment_reminder_name' => $invoice->name,
                'invoice_payment_number' => $invoice->invoice,
                'invoice_payment_dueAmount' => $invoice->dueAmount,
                'payment_reminder_date' => $invoice->date,

            ];

            $resp = Utility::sendEmailTemplate('new_payment_reminder', [$customer->id => $customer->email], $reminderArr);
        }

        return redirect()->back()->with('success', __('Payment reminder successfully send.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
    }

    public function customerInvoiceSend($invoice_id)
    {
        return view('customer.invoice_send', compact('invoice_id'));
    }

    public function customerInvoiceSendMail(Request $request, $invoice_id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $invoice = Invoice::where('id', $invoice_id)->first();

        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->name = !empty($customer) ? $customer->name : '';
        $invoice->invoice = \Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        try {
            Mail::to($email)->send(new CustomerInvoiceSend($invoice));
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($request->is_display == 'true') {
            $invoice->shipping_display = 1;
        } else {
            $invoice->shipping_display = 0;
        }
        $invoice->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($invoice_id)
    {
        if (\Auth::user()->can('duplicate invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $duplicateInvoice = new Invoice();
            $duplicateInvoice->invoice_id = $this->invoiceNumber();
            $duplicateInvoice->actual_invoice_number = \Auth::user()->invoiceNumberFormat($this->invoiceNumber());
            $duplicateInvoice->customer_id = $invoice['customer_id'];
            $duplicateInvoice->issue_date = date('Y-m-d');
            $duplicateInvoice->due_date = $invoice['due_date'];
            $duplicateInvoice->send_date = null;
            $duplicateInvoice->category_id = $invoice['category_id'];
            $duplicateInvoice->currency_code    = $invoice['currency_code'];
            $duplicateInvoice->currency_symbol  = $invoice['currency_symbol'];
            $duplicateInvoice->exchange_rate    = $invoice['exchange_rate'];
            $duplicateInvoice->ref_number = $invoice['ref_number'];
            $duplicateInvoice->status = 0;
            $duplicateInvoice->shipping_display = $invoice['shipping_display'];
            $duplicateInvoice->created_by = $invoice['created_by'];
            $duplicateInvoice->created_user = \Auth::user()->id;
            $duplicateInvoice->save();

            if ($duplicateInvoice) {
                $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice_id)->get();
                foreach ($invoiceProduct as $product) {
                    $duplicateProduct = new InvoiceProduct();
                    $duplicateProduct->invoice_id = $duplicateInvoice->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity = $product->quantity;
                    $duplicateProduct->tax = $product->tax;
                    $duplicateProduct->discount = $product->discount;
                    $duplicateProduct->price = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('Invoice duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewInvoice($template, $color)
    {

        $objUser = \Auth::user();
        $settings = Utility::settings();
        $invoice = new Invoice();

        $customer = new \stdClass();
        $customer->email = '<Email>';
        $customer->shipping_name = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state = '<State>';
        $customer->shipping_city = '<City>';
        $customer->shipping_phone = '<Customer Phone Number>';
        $customer->shipping_zip = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name = '<Customer Name>';
        $customer->billing_country = '<Country>';
        $customer->billing_state = '<State>';
        $customer->billing_city = '<City>';
        $customer->billing_phone = '<Customer Phone Number>';
        $customer->billing_zip = '<Zip>';
        $customer->billing_address = '<Address>';

        $totalTaxPrice = 0;
        $taxesData = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass();
            $item->name = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax = 5;
            $item->discount = 50;
            $item->price = 100;
            $item->unit = 1;
            $item->description = 'XYZ';

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax ' . $k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $invoice->invoice_id = 1;
        $invoice->issue_date = date('Y-m-d H:i:s');
        $invoice->due_date = date('Y-m-d H:i:s');
        $invoice->itemData = $items;
        $invoice->status = 0;
        $invoice->totalTaxPrice = 60;
        $invoice->totalQuantity = 3;
        $invoice->totalRate = 300;
        $invoice->totalDiscount = 10;
        $invoice->taxesData = $taxesData;
        $invoice->created_by = $objUser->creatorId();

        $invoice->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $invoice_logo = Utility::getValByName('invoice_logo');
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        return view('invoice.templates.' . $template, compact('invoice', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function invoice($invoice_id)
    {
        $settings = Utility::settings();

        $invoiceId = Crypt::decrypt($invoice_id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $invoice->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $invoice->customer;
        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        foreach ($invoice->items as $product) {
            $item = new \stdClass();
            $item->name = !empty($product->product) ? $product->product->name : '';
            $item->quantity = $product->quantity;
            $item->tax = $product->tax;
            $item->unit = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount = $product->discount;
            $item->price = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);

            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;

                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }
                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $invoice->itemData = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;
        $invoice->customField = CustomField::getData($invoice, 'invoice');
        $customFields = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
        }

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);

            $data = [];

            $data['invoice'] = $invoice;
            $data['color'] = $color;
            $data['settings'] = $settings;
            $data['customer'] = $customer;
            $data['img'] = $img;
            $data['font_color'] = $font_color;
            $data['customFields'] = $customFields;

            $pdf = PDF::loadView('invoice.templates.' . $settings['invoice_template'], $data);
            return $pdf->download(Utility::customerInvoiceNumberFormat($invoice->invoice_id).'.pdf');

            return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function old_invoice($invoice_id)
    {
        $settings = Utility::settings();

        $invoiceId = Crypt::decrypt($invoice_id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $invoice->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $invoice->customer;
        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        foreach ($invoice->items as $product) {
            $item = new \stdClass();
            $item->name = !empty($product->product) ? $product->product->name : '';
            $item->quantity = $product->quantity;
            $item->tax = $product->tax;
            $item->unit = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount = $product->discount;
            $item->price = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);

            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;

                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }
                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $invoice->itemData = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;
        $invoice->customField = CustomField::getData($invoice, 'invoice');
        $customFields = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
        }

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && !empty($invoice_logo)) {
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);

            return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveTemplateSettings(Request $request)
    {

        $post = $request->all();
        unset($post['_token']);

        if (isset($post['invoice_template']) && (!isset($post['invoice_color']) || empty($post['invoice_color']))) {
            $post['invoice_color'] = "ffffff";
        }

        if ($request->invoice_logo) {
            $dir = 'invoice_logo/';
            $invoice_logo = \Auth::user()->id . '_invoice_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'invoice_logo', $invoice_logo, $dir, $validation);

            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['invoice_logo'] = $invoice_logo;
        }

        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Invoice Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = InvoiceProduct::where('invoice_id', $request->invoice_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function invoiceLink($invoiceId)
    {
        try {
            $id = Crypt::decrypt($invoiceId);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Invoice Not Found.'));
        }

        $id = Crypt::decrypt($invoiceId);
        $invoice = Invoice::with(['creditNote', 'payments.bankAccount', 'items.product.unit'])->find($id);

        $settings = Utility::settingsById($invoice->created_by);

        if (!empty($invoice)) {

            $user_id = $invoice->created_by;
            $user = User::find($user_id);
            $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->get();
            $customer = $invoice->customer;
            $iteams = $invoice->items;
            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('module', '=', 'invoice')->get();
            $company_payment_setting = Utility::getCompanyPaymentSetting($user_id);

            // start for storage limit note
            $user_plan = Plan::find($user->plan);
            // end for storage limit note

            return view('invoice.customer_invoice', compact('settings', 'invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'user', 'company_payment_setting', 'user_plan'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function export()
    {
        $name = 'invoice_' . date('Y-m-d i:h:s');
        $data = Excel::download(new InvoiceExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }

        public function checkInvoiceNumber(Request $request)
    {
    
        $exists = Invoice::where('actual_invoice_number', $request->invoice_number)->exists();
        
        return response()->json(['exists' => $exists]);
    }
}
