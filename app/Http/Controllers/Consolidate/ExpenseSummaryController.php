<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\User;
use App\Models\Vender;
use App\Models\Payment;

class ExpenseSummaryController extends Controller
{
    public function index(Request $request)
    {

        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        $account = BankAccount::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('holder_name', 'id');
        $account->prepend('Select Account', '');
        $vender = Vender::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('name', 'id');
        $vender->prepend('Select Vendor', '');
        $category = ProductServiceCategory::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('type', '=', 'expense')->get()->pluck('name', 'id');
        $category->prepend('Select Category', '');

        if ($request->period === 'quarterly') {
            $month = [
                'January-March',
                'April-June',
                'July-September',
                'Octomber-December',
            ];
        } elseif ($request->period === 'half-yearly') {
            $month = [
                'January-June',
                'July-December',
            ];
        } elseif ($request->period === 'yearly') {
            $month = array_values(array_reverse($this->yearList()));
        } else {
            $month = $this->yearMonth();
        }
        $periods = Budget::$period;

        $data['monthList'] = $month;
        $data['yearList'] = $this->yearList();
        $data['periods'] = $periods;
        $filter['category'] = __('All');
        $filter['vender'] = __('All');

        if ($request->period === 'yearly') {
            $year = array_reverse($this->yearList());
            $yearList = [];
            foreach ($year as $value) {
                $yearList[$value] = $value;
            }
            }
            else
            {
            $yearList[($request->year) ? $request->year : date('Y')] = ($request->year) ? $request->year : date('Y');
            }

        if (isset($request->year)) {
            $year = $request->year;
        } else {
            $year = date('Y');
        }
        $data['currentYear'] = $year;

        //   -----------------------------------------PAYMENT EXPENSE ------------------------------------------------------------
        $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,product_service_categories.name as category_id')->leftjoin('product_service_categories', 'payments.category_id', '=', 'product_service_categories.id');
        // $expenses->where('payments.created_by', '=', \Auth::user()->creatorId());
        $expenses->when(!empty($request->company), function ($query) use ($request) {
            $query->where('payments.created_by', '=', $request->company);
        });
        if ($request->period != 'yearly') {
            $expenses->whereRAW('YEAR(date) =?', [$year]);
        }

        if (!empty($request->category)) {
            $expenses->where('category_id', '=', $request->category);
            $cat = ProductServiceCategory::find($request->category);
            $filter['category'] = !empty($cat) ? $cat->name : '';
        }
        if (!empty($request->vender)) {
            $expenses->where('vender_id', '=', $request->vender);

            $vend = Vender::find($request->vender);
            $filter['vender'] = !empty($vend) ? $vend->name : '';
        }

        $expenses->groupBy('month', 'year', 'category_id');
        $expenses = $expenses->get();

        $tmpArray = [];
        foreach ($expenses as $expense) {
            $tmpArray[$expense->category_id][$expense->year][$expense->month] = $expense->amount;
        }
        $array = [];

        foreach ($tmpArray as $key => $yearData) {
            $array[$key] = [];

            foreach ($yearList as $targetYear) {
                $array[$key][$targetYear] = [];

                for ($i = 1; $i <= 12; $i++) {
                    $array[$key][$targetYear][$i] = 0;
                }

                if (isset($yearData[$targetYear])) {
                    foreach ($yearData[$targetYear] as $month => $value) {
                        $array[$key][$targetYear][$month] = (float) $value; // Convert the value to float if needed
                    }
                }
            }
        }

        //     ------------------------------------BILL EXPENSE----------------------------------------------------

        $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,product_service_categories.name as category_id,bill_id, bills.id')
            ->leftjoin('product_service_categories', 'bills.category_id', '=', 'product_service_categories.id')
            // ->where('bills.created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            ->when(!empty($request->company), function ($query) use ($request) {
                $query->where('bills.created_by', '=', $request->company);
            })->where('status', '!=', 0);
        $bills->whereRAW('YEAR(send_date) =?', [$year]);

        if ($request->period != 'yearly') {
            $bills->whereRAW('YEAR(send_date) =?', [$year]);
                }

        if (!empty($request->vender)) {
            $bills->where('vender_id', '=', $request->vender);
        }

        if (!empty($request->category)) {
            $bills->where('category_id', '=', $request->category);
        }
        $bills = $bills->get();

        $billTmpArray = [];
        foreach ($bills as $bill) {
            $billTmpArray[$bill->category_id][$bill->year][$bill->month][] = $bill->getTotal();
        }

        $billArray = [];

        foreach ($billTmpArray as $key => $yearData) {
            $billArray[$key] = [];

            foreach ($yearList as $targetYear) {
                $billArray[$key][$targetYear] = [];

                for ($i = 1; $i <= 12; $i++) {
                    $billArray[$key][$targetYear][$i] = 0;
                }

                if (isset($yearData[$targetYear])) {
                    foreach ($yearData[$targetYear] as $month => $values) {
                        if (is_array($values)) {
                            $sum = array_sum($values);
                            $billArray[$key][$targetYear][$month] = $sum;
                        } else {
                            $billArray[$key][$targetYear][$month] = (float) $values;
                        }
                    }
                }
            }
        }

        $billsum = Utility::billInvoiceData($billArray, $request , $yearList);

        $billTotalArray = [];
        foreach ($bills as $bill) {
            $billTotalArray[$bill->year][$bill->month][] = $bill->getTotal();
        }

        $expenseArr = [];
        $billArr = [];
        $expensesum = [];

        foreach ($yearList as $year) {
            $billArr[$year] = [];

            for ($i = 1; $i <= 12; $i++) {
                $billArr[$year][$i] = 0;
            }

            if (isset($billTotalArray[$year])) {
                foreach ($billTotalArray[$year] as $month => $values) {
                    $billArr[$year][$month] = array_sum($values);
                }
            }
        }


        foreach ($array as $key => $categoryData) {

            $expensesum[] = Utility::revenuePaymentData($key , $categoryData, $request ,$yearList);
        }

        $paymentTotalArray = [];

        foreach ($expenses as $expense) {
            $paymentTotalArray[$expense->year][$expense->month][] = $expense->amount;
        }

        foreach ($yearList as $year) {
            $expenseArr[$year] = [];

            for ($i = 1; $i <= 12; $i++) {
                $expenseArr[$year][$i] = 0;
            }

            if (isset($paymentTotalArray[$year])) {
                foreach ($paymentTotalArray[$year] as $month => $values) {
                    $expenseArr[$year][$month] = array_sum($values);
                }
            }
        }

        $chartExpenseArr = Utility::totalData($billArr, $expenseArr, $request , $yearList);

        $data['chartExpenseArr'] = $chartExpenseArr;
        $data['expenseArr'] = $expensesum;
        $data['billArray'] = $billsum;
        $data['account'] = $account;
        $data['vender'] = $vender;
        $data['category'] = $category;

        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;

        return view('consolidate.expense_summary', compact('filter','company'), $data);
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
    public function yearList()
    {
        $starting_year = date('Y', strtotime('-5 year'));
        $ending_year = date('Y');

        foreach (range($ending_year, $starting_year) as $year) {
            $years[$year] = $year;
        }

        return $years;
    }
}
