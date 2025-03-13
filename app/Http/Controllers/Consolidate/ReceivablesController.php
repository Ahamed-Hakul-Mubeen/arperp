<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;

class ReceivablesController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }

        // $receivableCustomers = Invoice::select('customers.name')
        //     ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
        //     ->selectRaw('sum((invoice_payments.amount)) as pay_price')
        //     ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
        //      LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
        //      WHERE invoice_products.invoice_id = invoices.id) as total_tax')
        //     ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
        //      WHERE credit_notes.invoice = invoices.id) as credit_price')
        //     ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
        //     ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
        //     ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
        //     ->where('invoices.created_by', \Auth::user()->creatorId())
        //     ->where('invoices.issue_date', '>=', $start)
        //     ->where('invoices.issue_date', '<=', $end)
        //     ->groupBy('invoices.invoice_id')
        //     ->get()
        //     ->toArray();
        $receivableCustomers = Invoice::select('customers.name')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity * invoices.exchange_rate) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM(invoice_payments.amount * invoices.exchange_rate) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                        LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                        WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
                        WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.id')  // Group by invoices.id instead of invoice_id
            ->get()
            ->toArray();
// dd($receivableCustomers);
        // $receivableSummariesInvoice = Invoice::select('customers.name')
        //     ->selectRaw('(invoices.invoice_id) as invoice')
        //     ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
        //     ->selectRaw('sum((invoice_payments.amount)) as pay_price')
        //     ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
        //      LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
        //      WHERE invoice_products.invoice_id = invoices.id) as total_tax')
        //     ->selectRaw('invoices.issue_date as issue_date')
        //     ->selectRaw('invoices.status as status')
        //     ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
        //     ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', 'invoices.id')
        //     ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
        //     ->where('invoices.created_by', \Auth::user()->creatorId())
        //     ->where('invoices.issue_date', '>=', $start)
        //     ->where('invoices.issue_date', '<=', $end)
        //     ->groupBy('invoices.invoice_id')
        //     ->get()
        //     ->toArray();
        $receivableSummariesInvoice = Invoice::select('customers.name')
            ->selectRaw('invoices.invoice_id as invoice')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity * invoices.exchange_rate) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM(invoice_payments.amount  * invoices.exchange_rate) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                        LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                        WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('invoices.issue_date as issue_date')
            ->selectRaw('invoices.status as status')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.id')  // Group by invoices.id to avoid issues with multiple joins
            ->get()
            ->toArray();


        $receivableSummariesCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('0 as pay_price')
            ->selectRaw('0 as total_tax')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->groupBy('credit_notes.id')
            ->get()
            ->toArray();

        $receivableSummaries = (array_merge($receivableSummariesCredit, $receivableSummariesInvoice));

        $receivableDetailsInvoice = Invoice::select('customers.name')
            ->selectRaw('(invoices.invoice_id) as invoice')
            ->selectRaw('sum(invoice_products.price * invoices.exchange_rate) as price')
            ->selectRaw('(invoice_products.quantity) as quantity')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('invoices.issue_date as issue_date')
            ->selectRaw('invoices.status as status')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->leftJoin('product_services', 'product_services.id', 'invoice_products.product_id')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id', 'product_services.name')
            ->get()
            ->toArray();

        $receivableDetailsCredit = CreditNote::select('customers.name')
            ->selectRaw('null as invoice')
            ->selectRaw('(credit_notes.id) as invoices')
            ->selectRaw('(credit_notes.amount) as price')
            ->selectRaw('(product_services.name) as product_name')
            ->selectRaw('credit_notes.date as issue_date')
            ->selectRaw('5 as status')
            ->leftJoin('customers', 'customers.id', 'credit_notes.customer')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'credit_notes.invoice')
            ->leftJoin('product_services', 'product_services.id', 'invoice_products.product_id')
            ->leftJoin('invoices', 'invoices.id', 'credit_notes.invoice')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('credit_notes.date', '>=', $start)
            ->where('credit_notes.date', '<=', $end)
            ->groupBy('credit_notes.id', 'product_services.name')
            ->get()
            ->toArray();

        $mergedArray = [];
        foreach ($receivableDetailsCredit as $item) {
            $invoices = $item["invoices"];

            if (!isset($mergedArray[$invoices])) {
                $mergedArray[$invoices] = [
                    "name" => $item["name"],
                    "invoice" => $item["invoice"],
                    "invoices" => $invoices,
                    "price" => $item["price"],
                    "quantity" => 0,
                    "product_name" => "",
                    "issue_date" => "",
                    "status" => 0,
                ];
            }

            if (!strstr($mergedArray[$invoices]["product_name"], $item["product_name"])) {
                if ($mergedArray[$invoices]["product_name"] !== "") {
                    $mergedArray[$invoices]["product_name"] .= ", ";
                }
                $mergedArray[$invoices]["product_name"] .= $item["product_name"];
            }

            $mergedArray[$invoices]["issue_date"] = $item["issue_date"];
            $mergedArray[$invoices]["status"] = $item["status"];
        }

        $receivableDetailsCredits = array_values($mergedArray);

        $receivableDetails = (array_merge($receivableDetailsInvoice, $receivableDetailsCredits));

        $agingSummary = Invoice::select('customers.name', 'invoices.due_date as due_date', 'invoices.status as status', 'invoices.invoice_id as invoice_id')
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity * invoices.exchange_rate) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM(invoice_payments.amount * invoices.exchange_rate) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) as pay_price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
                        LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
                        WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->selectRaw('(SELECT SUM(credit_notes.amount) FROM credit_notes
                        WHERE credit_notes.invoice = invoices.id) as credit_price')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.id')  // Group by invoices.id to avoid issues with multiple rows per invoice
            ->get()
            ->toArray();


        $agingSummaries = [];

        $today = date("Y-m-d");
        foreach ($agingSummary as $item) {
            $name = $item["name"];
            $price = floatval(($item["price"] + $item['total_tax']) - ($item['pay_price'] + $item['credit_price']));
            $dueDate = $item["due_date"];

            if (!isset($agingSummaries[$name])) {
                $agingSummaries[$name] = [
                    'current' => 0.0,
                    "1_15_days" => 0.0,
                    "16_30_days" => 0.0,
                    "31_45_days" => 0.0,
                    "greater_than_45_days" => 0.0,
                    "total_due" => 0.0,
                ];
            }

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");

            if ($daysDifference <= 0) {
                $agingSummaries[$name]["current"] += $price;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $agingSummaries[$name]["1_15_days"] += $price;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $agingSummaries[$name]["16_30_days"] += $price;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $agingSummaries[$name]["31_45_days"] += $price;
            } elseif ($daysDifference > 45) {
                $agingSummaries[$name]["greater_than_45_days"] += $price;
            }

            $agingSummaries[$name]["total_due"] += $price;
        }

        $currents = [];
        $days1to15 = [];
        $days16to30 = [];
        $days31to45 = [];
        $moreThan45 = [];

        foreach ($agingSummary as $item) {
            $dueDate = $item["due_date"];
            $price = floatval($item["price"]);
            $total_tax = floatval($item["total_tax"]);
            $credit_price = floatval($item["credit_price"]);
            $payPrice = $item["pay_price"] ? floatval($item["pay_price"]) : 0;

            $daysDifference = date_diff(date_create($dueDate), date_create($today));
            $daysDifference = $daysDifference->format("%R%a");
            $balanceDue = ($price + $total_tax) - ($payPrice + $credit_price);
            $totalPrice = $price + $total_tax;
            if ($daysDifference <= 0) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $currents[] = $item;
            } elseif ($daysDifference >= 1 && $daysDifference <= 15) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age'] = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days1to15[] = $item;
            } elseif ($daysDifference >= 16 && $daysDifference <= 30) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age'] = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days16to30[] = $item;
            } elseif ($daysDifference >= 31 && $daysDifference <= 45) {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age'] = intval(str_replace(array('+', '-'), '', $daysDifference));
                $days31to45[] = $item;
            } else {
                $item["total_price"] = $totalPrice;
                $item["balance_due"] = $balanceDue;
                $item['age'] = intval(str_replace(array('+', '-'), '', $daysDifference));
                $moreThan45[] = $item;
            }
        }

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;

        return view('consolidate.receivable_report', compact('filter', 'receivableCustomers', 'receivableSummaries', 'receivableDetails', 'agingSummaries', 'currents', 'days1to15', 'days16to30', 'days31to45', 'moreThan45', 'company'));
    }
}
