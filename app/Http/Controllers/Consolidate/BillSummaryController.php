<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vender;
use App\Models\Bill;
use App\Models\User;

class BillSummaryController extends Controller
{
    public function index(Request $request){
        
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        $filter['vender'] = __('All');
        $filter['status'] = __('All');

        $vender = Vender::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->pluck('name', 'id');
        $vender->prepend('Select Vendor', '');
        $status = Bill::$statues;

        $bills = Bill::selectRaw('bills.*,MONTH(send_date) as month,YEAR(send_date) as year');

        if ($request->status != '') {
            $bills->where('status', '=', $request->status);

            $filter['status'] = Bill::$statues[$request->status];
        } else {
            $bills->where('status', '!=', 0);
        }
        $bills->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        });
        // $bills->where('created_by', '=', \Auth::user()->creatorId());

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-01'));
            $end = strtotime(date('Y-12'));
        }

        $bills->where('send_date', '>=', date('Y-m-01', $start))->where('send_date', '<=', date('Y-m-t', $end));

        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);

        if (!empty($request->vender)) {
            $bills->where('vender_id', $request->vender);
            $vend = Vender::find($request->vender);

            $filter['vender'] = !empty($vend) ? $vend->name : '';
        }

        $bills = $bills->with(['vender', 'category'])->get();

        $totalBill = 0;
        $totalDueBill = 0;
        $billTotalArray = [];
        foreach ($bills as $bill) {
            $totalBill += $bill->getTotal();
            $totalDueBill += $bill->getDue();

            $billTotalArray[$bill->month][] = $bill->getTotal();
        }
        $totalPaidBill = $totalBill - $totalDueBill;

        for ($i = 1; $i <= 12; $i++) {
            $billTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;
        }

        $monthList = $month = $this->yearMonth();

        return view('consolidate.bill_report', compact('bills', 'vender', 'status', 'totalBill', 'totalDueBill', 'totalPaidBill', 'billTotal', 'monthList', 'filter', 'company'));
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
