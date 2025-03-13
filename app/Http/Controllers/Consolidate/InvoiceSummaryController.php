<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;


use Illuminate\Http\Request;

class InvoiceSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        $filter['customer'] = __('All');
        $filter['status'] = __('All');

        $customer = Customer::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->pluck('name', 'id');
        $customer->prepend('Select Customer', '');
        $status = Invoice::$statues;

        $invoices = Invoice::selectRaw('invoices.*,MONTH(send_date) as month,YEAR(send_date) as year');

        if ($request->status != '') {
            $invoices->where('status', $request->status);

            $filter['status'] = Invoice::$statues[$request->status];
        } else {
            $invoices->where('status', '!=', 0);
        }
        // if(!empty($request->company)){
        //     $invoices->where('created_by', '=', $request->company);
        // }
        $invoices->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        });

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-01'));
            $end = strtotime(date('Y-12'));
        }

        $invoices->where('send_date', '>=', date('Y-m-01', $start))->where('send_date', '<=', date('Y-m-t', $end));

        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);

        if (!empty($request->customer)) {
            $invoices->where('customer_id', $request->customer);
            $cust = Customer::find($request->customer);

            $filter['customer'] = !empty($cust) ? $cust->name : '';
        }

        $invoices = $invoices->with(['customer', 'category'])->get();

        $totalInvoice = 0;
        $totalDueInvoice = 0;
        $invoiceTotalArray = [];
        foreach ($invoices as $invoice) {
            $totalInvoice += $invoice->getTotal(true);
            $totalDueInvoice += $invoice->getDue(true);

            $invoiceTotalArray[$invoice->month][] = $invoice->getTotal(true);
        }
        $totalPaidInvoice = $totalInvoice - $totalDueInvoice;

        for ($i = 1; $i <= 12; $i++) {
            $invoiceTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
        }

        $monthList = $month = $this->yearMonth();
        return view('consolidate.invoice_summary', compact('invoices', 'customer', 'status', 'totalInvoice', 'totalDueInvoice', 'totalPaidInvoice', 'invoiceTotal', 'monthList', 'filter', 'company'));
    }

    public function yearMonth()
    {

        $month[] = __('January');
        $month[] = __('February');
        $month[] = __('March');
        $month[] = __('April');
        $month[] = __('May');
        $month[] = __('June');
        $month[] = __('July');
        $month[] = __('August');
        $month[] = __('September');
        $month[] = __('October');
        $month[] = __('November');
        $month[] = __('December');

        return $month;
    }
}
