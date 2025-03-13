<?php

namespace App\Http\Controllers;

use App\Exports\PreOrderExport;
use App\Models\Bill;
use App\Models\BillProduct;
use App\Models\PreOrder;
use App\Models\Vender;
use Illuminate\Http\Request;
use App\Models\CustomField;
use App\Models\PreOrderProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PreOrderController extends Controller
{

    public function __construct() {}

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage preorder')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('All', '');

            $status = PreOrder::$statues;

            $query = PreOrder::with('createdUser')->where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (!empty($request->issue_date)) {
                $date_range = explode('to', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            if(!empty($request->category))
            {
                $query->where('category_id', '=', $request->category);
            }
            $preOrders = $query->with(['category'])->get();
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');

            return view('pre_order.index', compact('preOrders', 'vender', 'status','category'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($venderId)
    {
        if (\Auth::user()->can('create preorder')) {
            $customFields    = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'pre_order')->get();
            $pre_order_number = \Auth::user()->preOrderNumberFormat($this->preOrderNumber());
            $venders       = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vender', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('--', '');

            return view('pre_order.create', compact('venders', 'pre_order_number', 'product_services', 'category', 'customFields', 'venderId'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('pre_order.vender_detail', compact('vender'));
    }

    public function product(Request $request)
    {

        $data['product'] = $product = ProductService::find($request->product_id);

        $data['unit']    = (!empty($product->unit)) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;

        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;

        $purchasePrice       = $product->purchase_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($purchasePrice * $quantity);
        $data['totalAmount'] = ($purchasePrice * $quantity);

        return json_encode($data);
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create preorder')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'vender_id' => 'required',
                    'issue_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }
            $status = PreOrder::$statues;

            $pre_order                  = new PreOrder();
            $pre_order->pre_order_id    = $this->preOrderNumber();
            $pre_order->vender_id       = $request->vender_id;
            $pre_order->status          = 0;
            $pre_order->issue_date      = $request->issue_date;
            $pre_order->category_id     = $request->category_id;
            // $pre_order->discount_apply = isset($request->discount_apply) ? 1 : 0;
            $pre_order->created_by      = \Auth::user()->creatorId();
            $pre_order->created_user      = \Auth::user()->id;
            $pre_order->save();
            CustomField::saveData($pre_order, $request->customField);
            $products = $request->items;

            for ($i = 0; $i < count($products); $i++) {
                $preOrderProduct              = new PreOrderProduct();
                $preOrderProduct->pre_order_id = $pre_order->id;
                $preOrderProduct->product_id  = $products[$i]['item'];
                $preOrderProduct->quantity    = $products[$i]['quantity'];
                $preOrderProduct->tax         = $products[$i]['tax'];
                //                $preOrderProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                $preOrderProduct->discount    = $products[$i]['discount'];
                $preOrderProduct->price       = $products[$i]['price'];
                $preOrderProduct->description = $products[$i]['description'];
                $preOrderProduct->save();
            }



            //For Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $vender = Vender::find($pre_order->vender_id);
            $preOrderNotificationArr = [
                'pre_order_number' => \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id),
                'user_name' => \Auth::user()->name,
                'vender_name' => $vender->name,
                'pre_order_issue_date' => $pre_order->issue_date,
            ];
            //Twilio Notification
            if (isset($setting['pre_order_notification']) && $setting['pre_order_notification'] == 1) {
                Utility::send_twilio_msg($vender->contact, 'new_pre_order', $preOrderNotificationArr);
            }



            return redirect()->route('pre_order.index', $pre_order->id)->with('success', __('Pre Order successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {

        if (\Auth::user()->can('edit preorder')) {
            try {
                $id              = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('PreOrder Not Found.'));
            }

            $id              = Crypt::decrypt($ids);
            $pre_order        = PreOrder::find($id);
            $pre_order_number = \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id);
            $venders       = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category        = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $pre_order->customField = CustomField::getData($pre_order, 'pre_order');
            $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'pre_order')->get();

            $items = [];
            foreach ($pre_order->items as $preOrderItem) {
                $itemAmount               = $preOrderItem->quantity * $preOrderItem->price;
                $preOrderItem->itemAmount = $itemAmount;
                $preOrderItem->taxes      = Utility::tax($preOrderItem->tax);
                $items[]                  = $preOrderItem;
            }

            return view('pre_order.edit', compact('venders', 'product_services', 'pre_order', 'pre_order_number', 'category', 'customFields', 'items'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, PreOrder $pre_order)
    {
        if (\Auth::user()->can('edit preorder')) {
            if ($pre_order->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'vender_id' => 'required',
                        'issue_date' => 'required',
                        'category_id' => 'required',
                        'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('pre_order.index')->with('error', $messages->first());
                }
                $pre_order->vender_id    = $request->vender_id;
                $pre_order->issue_date     = $request->issue_date;
                $pre_order->category_id    = $request->category_id;
                $pre_order->created_user      = \Auth::user()->id;
                //                $pre_order->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $pre_order->save();
                CustomField::saveData($pre_order, $request->customField);
                $products = $request->items;

                for ($i = 0; $i < count($products); $i++) {
                    $preOrderProduct = PreOrderProduct::find($products[$i]['id']);
                    if ($preOrderProduct == null) {
                        $preOrderProduct              = new PreOrderProduct();
                        $preOrderProduct->pre_order_id = $pre_order->id;
                    }

                    if (isset($products[$i]['item'])) {
                        $preOrderProduct->product_id = $products[$i]['item'];
                    }

                    $preOrderProduct->quantity    = $products[$i]['quantity'];
                    $preOrderProduct->tax         = $products[$i]['tax'];
                    //                    $preOrderProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $preOrderProduct->discount    = $products[$i]['discount'];
                    $preOrderProduct->price       = $products[$i]['price'];
                    $preOrderProduct->description = $products[$i]['description'];
                    $preOrderProduct->save();
                }

                return redirect()->route('pre_order.index', $pre_order->id)->with('success', __('PreOrder successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function preOrderNumber()
    {
        $latest = PreOrder::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->pre_order_id + 1;
    }

    public function show($ids)
    {
        if (\Auth::user()->can('show preorder')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('PreOrder Not Found.'));
            }
            $id       = Crypt::decrypt($ids);
            $pre_order = PreOrder::with(['items.product.unit'])->find($id);

            if ($pre_order->created_by == \Auth::user()->creatorId()) {
                $vender = $pre_order->vender;
                $iteams   = $pre_order->items;
                $status   = PreOrder::$statues;

                $pre_order->customField = CustomField::getData($pre_order, 'pre_order');
                $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'pre_order')->get();

                return view('pre_order.view', compact('pre_order', 'vender', 'iteams', 'status', 'customFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(PreOrder $pre_order)
    {
        if (\Auth::user()->can('delete preorder')) {
            if ($pre_order->created_by == \Auth::user()->creatorId()) {
                $pre_order->delete();
                PreOrderProduct::where('pre_order_id', '=', $pre_order->id)->delete();

                return redirect()->route('pre_order.index')->with('success', __('PreOrder successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete preorder product')) {
            PreOrderProduct::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('PreOrder product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function venderPreOrder(Request $request)
    {
        if (\Auth::user()->can('manage customer proposal')) {

            $status = PreOrder::$statues;

            $query = PreOrder::where('vender_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->issue_date)) {
                $date_range = explode(' - ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $preOrders = $query->get();

            return view('pre_order.index', compact('pre_orders', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function venderPreOrderShow($ids)
    {
        if (\Auth::user()->can('show preorder')) {
            $pre_order_id = \Crypt::decrypt($ids);
            $pre_order    = PreOrder::where('id', $pre_order_id)->first();
            if ($pre_order->created_by == \Auth::user()->creatorId()) {
                $vender = $pre_order->vender;
                $iteams   = $pre_order->items;

                return view('pre_order.view', compact('pre_order', 'vender', 'iteams'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {
        if (\Auth::user()->can('send preorder')) {
            $pre_order            = PreOrder::where('id', $id)->first();
            $pre_order->send_date = date('Y-m-d');
            $pre_order->status    = 1;
            $pre_order->save();

            $vender           = Vender::where('id', $pre_order->vender_id)->first();
            $pre_order->name     = !empty($vender) ? $vender->name : '';
            $pre_order->pre_order = \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id);

            $preOrderId    = Crypt::encrypt($pre_order->id);
            $pre_order->url = route('pre_order.pdf', $preOrderId);

            // Send Email
            $setings = Utility::settings();
            if ($setings['pre_order_sent'] == 1 && !empty($vender->id)) {
                $vender           = Vender::where('id', $pre_order->vender_id)->first();
                $pre_order->name     = !empty($vender) ? $vender->name : '';
                $pre_order->pre_order = \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id);

                $preOrderId    = Crypt::encrypt($pre_order->id);
                $pre_order->url = route('pre_order.pdf', $preOrderId);

                $preOrderArr = [
                    'pre_order_name' => $pre_order->name,
                    'pre_order_number' => $pre_order->pre_order,
                    'pre_order_url' => $pre_order->url,

                ];
                //                dd($preOrderArr);
                $resp = \App\Models\Utility::sendEmailTemplate('pre_order_sent', [$vender->id => $vender->email], $preOrderArr);
                return redirect()->back()->with('success', __('PreOrder successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->back()->with('success', __('PreOrder successfully sent.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send preorder')) {
            $pre_order = PreOrder::where('id', $id)->first();

            $vender           = Vender::where('id', $pre_order->vender_id)->first();
            $pre_order->name     = !empty($vender) ? $vender->name : '';
            $pre_order->pre_order = \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id);

            $preOrderId    = Crypt::encrypt($pre_order->id);
            $pre_order->url = route('pre_order.pdf', $preOrderId);

            // Send Email
            $setings = Utility::settings();
            if ($setings['pre_order_sent'] == 1) {
                $vender           = Vender::where('id', $pre_order->vender_id)->first();
                $pre_order->name     = !empty($vender) ? $vender->name : '';
                $pre_order->pre_order = \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id);

                $preOrderId    = Crypt::encrypt($pre_order->id);
                $pre_order->url = route('pre_order.pdf', $preOrderId);

                $preOrderArr = [
                    'pre_order_name' => $pre_order->name,
                    'pre_order_number' => $pre_order->pre_order,
                    'pre_order_url' => $pre_order->url,

                ];
                //                dd($preOrderArr);
                $resp = \App\Models\Utility::sendEmailTemplate('pre_order_sent', [$vender->id => $vender->email], $preOrderArr);
                return redirect()->back()->with('success', __('PreOrder successfully sent.') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            }

            return redirect()->back()->with('success', __('PreOrder successfully sent.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function shippingDisplay(Request $request, $id)
    {
        $pre_order = PreOrder::find($id);

        if ($request->is_display == 'true') {
            $pre_order->shipping_display = 1;
        } else {
            $pre_order->shipping_display = 0;
        }
        $pre_order->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($pre_order_id)
    {
        if (\Auth::user()->can('duplicate preorder')) {
            $pre_order                       = PreOrder::where('id', $pre_order_id)->first();
            $duplicatePreOrder              = new PreOrder();
            $duplicatePreOrder->pre_order_id = $this->preOrderNumber();
            $duplicatePreOrder->vender_id = $pre_order['vender_id'];
            $duplicatePreOrder->issue_date  = date('Y-m-d');
            $duplicatePreOrder->send_date   = null;
            $duplicatePreOrder->category_id = $pre_order['category_id'];
            $duplicatePreOrder->status      = 0;
            $duplicatePreOrder->created_by  = $pre_order['created_by'];
            $duplicatePreOrder->created_user  = \Auth::user()->id;
            $duplicatePreOrder->save();

            if ($duplicatePreOrder) {
                $preOrderProduct = PreOrderProduct::where('pre_order_id', $pre_order_id)->get();
                foreach ($preOrderProduct as $product) {
                    $duplicateProduct              = new PreOrderProduct();
                    $duplicateProduct->pre_order_id = $duplicatePreOrder->id;
                    $duplicateProduct->product_id  = $product->product_id;
                    $duplicateProduct->quantity    = $product->quantity;
                    $duplicateProduct->tax         = $product->tax;
                    $duplicateProduct->discount    = $product->discount;
                    $duplicateProduct->price       = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('PreOrder duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function convert($pre_order_id)
    {
        if (\Auth::user()->can('convert invoice')) {
            $pre_order             = PreOrder::where('id', $pre_order_id)->first();
            $pre_order->is_convert = 1;
            $pre_order->save();

            $convertBill                = new Bill();
            $convertBill->bill_id       = $this->billNumber();
            $convertBill->vender_id     = $pre_order['vender_id'];
            $convertBill->bill_date    = date('Y-m-d');
            $convertBill->due_date      = date('Y-m-d');
            $convertBill->send_date     = null;
            $convertBill->category_id   = $pre_order['category_id'];
            $convertBill->status        = 0;
            $convertBill->created_by    = $pre_order['created_by'];
            $convertBill->created_user    = $pre_order['created_user'];
            $convertBill->type          = "Bill";
            $convertBill->user_type     = "vendor";
            $convertBill->save();

            $pre_order->converted_bill_id = $convertBill->id;
            $pre_order->save();

            if ($convertBill) {

                $preOrderProduct = PreOrderProduct::where('pre_order_id', $pre_order_id)->get();
                foreach ($preOrderProduct as $product) {
                    $duplicateProduct             = new BillProduct();
                    $duplicateProduct->bill_id = $convertBill->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity   = $product->quantity;
                    $duplicateProduct->tax        = $product->tax;
                    $duplicateProduct->discount   = $product->discount;
                    $duplicateProduct->price      = $product->price;

                    $duplicateProduct->save();

                    //inventory management (Quantity)
                    Utility::total_quantity('minus', $duplicateProduct->quantity, $duplicateProduct->product_id);

                    //Product Stock Report
                    $type = 'bill';
                    $type_id = $convertBill->id;
                    StockReport::where('type', '=', 'bill')->where('type_id', '=', $convertBill->id)->delete();
                    $description = $duplicateProduct->quantity . '' . __(' quantity sold in') . ' ' . \Auth::user()->preOrderNumberFormat($pre_order->pre_order_id) . ' ' . __('PreOrder convert to Bill') . ' ' . \Auth::user()->billNumberFormat($convertBill->bill_id);
                    Utility::addProductStock($duplicateProduct->product_id, $duplicateProduct->quantity, $type, $description, $type_id);
                }
            }

            return redirect()->back()->with('success', __('PreOrder to Bill convert successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function statusChange(Request $request, $id)
    {
        $status           = $request->status;
        $pre_order         = PreOrder::find($id);
        $pre_order->status = $status;
        $pre_order->created_user = \Auth::user()->id;
        $pre_order->save();

        return redirect()->back()->with('success', __('PreOrder status changed successfully.'));
    }

    public function previewPreOrder($template, $color)
    {
        $objUser  = \Auth::user();
        $settings = Utility::settings();
        $pre_order = new PreOrder();

        $vender                   = new \stdClass();
        $vender->email            = '<Email>';
        $vender->shipping_name    = '<Vender Name>';
        $vender->shipping_country = '<Country>';
        $vender->shipping_state   = '<State>';
        $vender->shipping_city    = '<City>';
        $vender->shipping_phone   = '<Vender Phone Number>';
        $vender->shipping_zip     = '<Zip>';
        $vender->shipping_address = '<Address>';
        $vender->billing_name     = '<Vender Name>';
        $vender->billing_country  = '<Country>';
        $vender->billing_state    = '<State>';
        $vender->billing_city     = '<City>';
        $vender->billing_phone    = '<Vender Phone Number>';
        $vender->billing_zip      = '<Zip>';
        $vender->billing_address  = '<Address>';

        $totalTaxPrice = 0;
        $taxesData     = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;
            $item->unit    = 1;


            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $pre_order->pre_order_id = 1;
        $pre_order->issue_date  = date('Y-m-d H:i:s');
        $pre_order->due_date    = date('Y-m-d H:i:s');
        $pre_order->itemData    = $items;

        $pre_order->totalTaxPrice = 60;
        $pre_order->totalQuantity = 3;
        $pre_order->totalRate     = 300;
        $pre_order->totalDiscount = 10;
        $pre_order->taxesData     = $taxesData;
        $pre_order->created_by     = $objUser->creatorId();

        $pre_order->customField = [];
        $customFields          = [];

        $preview    = 1;
        $color      = '#' . $color;
        $font_color = Utility::getFontColor($color);

        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $pre_order_logo = Utility::getValByName('pre_order_logo');
        //        $company_logo = \App\Models\Utility::GetLogo();
        //        if(isset($pre_order_logo) && !empty($pre_order_logo))
        //        {
        //            $img          = asset(\Storage::url('pre_order_logo').'/'. $pre_order_logo);
        //        }
        //        else
        //        {
        //            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        //        }


        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $pre_order_logo = Utility::getValByName('pre_order_logo');
        if (isset($pre_order_logo) && !empty($pre_order_logo)) {
            $img = Utility::get_file('pre_order_logo/') . $pre_order_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('pre_order.templates.' . $template, compact('pre_order', 'preview', 'color', 'img', 'settings', 'vender', 'font_color', 'customFields'));
    }

    public function pre_order($pre_order_id)
    {
        $settings   = Utility::settings();
        $preOrderId = Crypt::decrypt($pre_order_id);
        $pre_order   = PreOrder::where('id', $preOrderId)->first();

        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $pre_order->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vender = $pre_order->vender;
        $items         = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        foreach ($pre_order->items as $product) {
            $item              = new \stdClass();
            $item->name        = !empty($product->product) ? $product->product->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $product->tax;
            $item->unit        = !empty($product->product) ? $product->product->unit_id : '';
            $item->discount    = $product->discount;
            $item->price       = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);

            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[]      = $itemTax;


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

        $pre_order->itemData      = $items;
        $pre_order->totalTaxPrice = $totalTaxPrice;
        $pre_order->totalQuantity = $totalQuantity;
        $pre_order->totalRate     = $totalRate;
        $pre_order->totalDiscount = $totalDiscount;
        $pre_order->taxesData     = $taxesData;
        $pre_order->customField   = CustomField::getData($pre_order, 'pre_order');
        $pre_order->created_by     = $pre_order->created_by;

        $customFields            = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'pre_order')->get();
        }

        //Set your logo
        //        $logo         = asset(Storage::url('uploads/logo/'));
        //        $company_logo = Utility::getValByName('company_logo_dark');
        //        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($pre_order->created_by);
        $pre_order_logo = $settings_data['pre_order_logo'];
        if (isset($pre_order_logo) && !empty($pre_order_logo)) {
            $img = Utility::get_file('pre_order_logo/') . $pre_order_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($pre_order) {
            $color      = '#' . $settings['pre_order_color'];
            $font_color = Utility::getFontColor($color);

            return view('pre_order.templates.' . $settings['pre_order_template'], compact('pre_order', 'color', 'settings', 'vender', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function savePreOrderTemplateSettings(Request $request)
    {
        //        dd($request);
        $post = $request->all();
        unset($post['_token']);

        if (isset($post['pre_order_template']) && (!isset($post['pre_order_color']) || empty($post['pre_order_color']))) {
            $post['pre_order_color'] = "ffffff";
        }
        //        if($request->pre_order_logo)
        //        {
        //            $validator = \Validator::make($request->all(), ['pre_order_logo' => 'image|mimes:png|max:20480',]);
        //            if($validator->fails())
        //            {
        //                $messages = $validator->getMessageBag();
        //                return redirect()->back()->with('error', $messages->first());
        //            }
        //            $pre_order_logo = \Auth::user()->id . 'pre_order_logo.png';
        //            $path = $request->file('pre_order_logo')->storeAs('pre_order_logo', $pre_order_logo);
        //            $post['pre_order_logo'] = $pre_order_logo;
        //        }

        if ($request->pre_order_logo) {
            $dir = 'pre_order_logo/';
            $pre_order_logo = \Auth::user()->id . 'pre_order_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];
            $path = Utility::upload_file($request, 'pre_order_logo', $pre_order_logo, $dir, $validation);

            if ($path['flag'] == 0) {
                return redirect()->back()->with('error', __($path['msg']));
            }
            $post['pre_order_logo'] = $pre_order_logo;
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

        return redirect()->back()->with('success', __('PreOrder Setting updated successfully'));
    }

    function billNumber()
    {
        $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
    }

    public function items(Request $request)
    {
        $items = PreOrderProduct::where('pre_order_id', $request->pre_order_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function billLink($preOrderID)
    {
        try {
            $id       = Crypt::decrypt($preOrderID);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', __('Pre Order Not Found.'));
        }

        $id                 = Crypt::decrypt($preOrderID);
        $pre_order          = PreOrder::find($id);
        if (!empty($pre_order)) {
            $user_id                = $pre_order->created_by;
            $user                   = User::find($user_id);
            $vender                 = $pre_order->vender;
            $iteams                 = $pre_order->items;
            $pre_order->customField = CustomField::getData($pre_order, 'pre_order');
            $status                 = PreOrder::$statues;
            $customFields           = CustomField::where('module', '=', 'pre_order')->get();

            return view('pre_order.vender_pre_order', compact('pre_order', 'vender', 'iteams', 'customFields', 'status', 'user'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function export()
    {
        $name = 'pre_order_' . date('Y-m-d i:h:s');
        $data = Excel::download(new PreOrderExport(), $name . '.xlsx');
        ob_end_clean();

        return $data;
    }
}
