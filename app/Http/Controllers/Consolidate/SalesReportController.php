<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Exports\SalesReportExport;
use App\Models\User;

class SalesReportController extends Controller
{
    public function index(Request $request){
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $start = $request->start_date;
            $end = $request->end_date;
        } else {
            $start = date('Y-01-01');
            $end = date('Y-m-d', strtotime('+1 day'));
        }
        $invoiceItems = InvoiceProduct::select('product_services.name', \DB::raw('sum(invoice_products.quantity) as quantity'), \DB::raw('sum(invoice_products.price * invoice_products.quantity * invoices.exchange_rate) as price'), \DB::raw('sum(invoice_products.price * invoice_products.quantity * invoices.exchange_rate)/sum(invoice_products.quantity) as avg_price'));
        $invoiceItems->leftjoin('product_services', 'product_services.id', 'invoice_products.product_id');
        $invoiceItems->leftjoin('invoices', 'invoices.id', 'invoice_products.invoice_id');
        $invoiceItems->when(!empty($request->company), function($query) use ($request) {
            $query->where('product_services.created_by', '=', $request->company);
        });
        // $invoiceItems->where('product_services.created_by', \Auth::user()->creatorId());
        $invoiceItems->where('invoices.issue_date', '>=', $start);
        $invoiceItems->where('invoices.issue_date', '<=', $end);
        $invoiceItems->groupBy('invoice_products.product_id');
        $invoiceItems = $invoiceItems->get()->toArray();

        $invoiceCustomeres = Invoice::select('customers.name', \DB::raw('count(DISTINCT invoices.customer_id, invoice_products.invoice_id) as invoice_count'))
            ->selectRaw('sum((invoice_products.price * invoice_products.quantity * invoices.exchange_rate) - invoice_products.discount) as price')
            ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
            ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
            ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
            ->when(!empty($request->company), function($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })
            // ->where('invoices.created_by', \Auth::user()->creatorId())
            ->where('invoices.issue_date', '>=', $start)
            ->where('invoices.issue_date', '<=', $end)
            ->groupBy('invoices.invoice_id')
            ->get()
            ->toArray();
        $mergedArray = [];
        foreach ($invoiceCustomeres as $item) {
            $name = $item["name"];

            if (!isset($mergedArray[$name])) {
                $mergedArray[$name] = [
                    "name" => $name,
                    "invoice_count" => 0,
                    "price" => 0.0,
                    "total_tax" => 0.0,
                ];
            }

            $mergedArray[$name]["invoice_count"] += $item["invoice_count"];
            $mergedArray[$name]["price"] += $item["price"];
            $mergedArray[$name]["total_tax"] += $item["total_tax"];
        }
        $invoiceCustomers = array_values($mergedArray);

        $filter['startDateRange'] = $start;
        $filter['endDateRange'] = $end;
        return view('consolidate.sales_report', compact('filter', 'invoiceItems', 'invoiceCustomers','company'));
    }
    public function export(Request $request)
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
        if ($request->report == '#item') {
            $invoiceItems = InvoiceProduct::select('product_services.name', \DB::raw('sum(invoice_products.quantity) as quantity'), \DB::raw('sum(invoice_products.price * invoice_products.quantity) as price'), \DB::raw('sum(invoice_products.price)/sum(invoice_products.quantity) as avg_price'));
            $invoiceItems->leftjoin('product_services', 'product_services.id', 'invoice_products.product_id');
            $invoiceItems->leftjoin('invoices', 'invoices.id', 'invoice_products.invoice_id');
            $invoiceItems->when(!empty($request->company), function($query) use ($request) {
                $query->where('product_services.created_by', '=', $request->company);
            });
            // $invoiceItems->where('product_services.created_by', \Auth::user()->creatorId());
            $invoiceItems->where('invoices.issue_date', '>=', $start);
            $invoiceItems->where('invoices.issue_date', '<=', $end);
            $invoiceItems->groupBy('invoice_products.product_id');
            $invoiceItems = $invoiceItems->get()->toArray();

            $reportName = 'Item';
        } else {
            $invoiceCustomeres = Invoice::select('customers.name', \DB::raw('count(DISTINCT invoices.customer_id, invoice_products.invoice_id) as invoice_count'))
                ->selectRaw('sum((invoice_products.price * invoice_products.quantity) - invoice_products.discount) as price')
                ->selectRaw('(SELECT SUM((price * quantity - discount) * (taxes.rate / 100)) FROM invoice_products
             LEFT JOIN taxes ON FIND_IN_SET(taxes.id, invoice_products.tax) > 0
             WHERE invoice_products.invoice_id = invoices.id) as total_tax')
                ->leftJoin('customers', 'customers.id', 'invoices.customer_id')
                ->leftJoin('invoice_products', 'invoice_products.invoice_id', 'invoices.id')
                // ->where('invoices.created_by', \Auth::user()->creatorId())
                ->when(!empty($request->company), function($query) use ($request) {
                    $query->where('invoices.created_by', '=', $request->company);
                })
                ->where('invoices.issue_date', '>=', $start)
                ->where('invoices.issue_date', '<=', $end)
                ->groupBy('invoices.invoice_id')
                ->get()
                ->toArray();
            $mergedArray = [];
            foreach ($invoiceCustomeres as $item) {
                $name = $item["name"];

                if (!isset($mergedArray[$name])) {
                    $mergedArray[$name] = [
                        "name" => $name,
                        "invoice_count" => 0,
                        "price" => 0.0,
                        "total_tax" => 0.0,
                    ];
                }

                $mergedArray[$name]["invoice_count"] += $item["invoice_count"];
                $mergedArray[$name]["price"] += $item["price"];
                $mergedArray[$name]["total_tax"] += $item["total_tax"];
            }
            $invoiceItems = array_values($mergedArray);

            $reportName = 'Customer';
        }
        // where('id', \Auth::user()->creatorId())
        $companyName = User::when(!empty($request->company), function($query) use ($request){
            $query->where('id', $request->company);
        })->first();
        $companyName = $companyName ? $companyName->name : "Consolidated";

        $name = 'Sales By ' . $reportName . '_ ' . date('Y-m-d i:h:s');
        $data = Excel::download(new SalesReportExport($invoiceItems, $start, $end, $companyName, $reportName), $name . '.xlsx');
        ob_end_clean();

        return $data;

    }
}
