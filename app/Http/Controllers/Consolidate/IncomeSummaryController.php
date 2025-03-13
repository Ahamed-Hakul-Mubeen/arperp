<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\User;


class IncomeSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        $account = BankAccount::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('holder_name', 'id');
        $account->prepend('All', '');

        $customer = Customer::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('name', 'id');
        $customer->prepend('Select Customer', '');

        $category = ProductServiceCategory::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('type', '=', 'income')->get()->pluck('name', 'id');
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
        $filter['customer'] = __('All');

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
        if (isset($request->period)) {
            $period = $request->period;
        } else {
            $period = 'monthly';
        }
        $data['currentYear'] = $year;

        // ------------------------------REVENUE INCOME-----------------------------------

            $incomes = Revenue::selectRaw('sum(revenues.amount * revenues.exchange_rate) as amount,MONTH(date) as month,YEAR(date) as year, product_service_categories.name as category_id')->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 'income');
            // $incomes->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $incomes->when(!empty($request->company), function ($query) use ($request) {
                $query->where('revenues.created_by', '=', $request->company);
            });
        if ($request->period != 'yearly') {
            $incomes->whereRAW('YEAR(date) =?', [$year]);
        }

        if (!empty($request->category)) {
            $incomes->where('category_id', '=', $request->category);
            $cat = ProductServiceCategory::find($request->category);
            $filter['category'] = !empty($cat) ? $cat->name : '';
        }

        if (!empty($request->customer)) {
            $incomes->where('customer_id', '=', $request->customer);
            $cust = Customer::find($request->customer);
            $filter['customer'] = !empty($cust) ? $cust->name : '';
        }

        $incomes->groupBy('month', 'year', 'category_id');
        $incomes = $incomes->get();

        $tmpArray = [];
        foreach ($incomes as $income) {
            $tmpArray[$income->category_id][$income->year][$income->month] = $income->amount;
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

        //---------------------------INVOICE INCOME-----------------------------------------------

        $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,product_service_categories.name as category_id,invoice_id,invoices.id')
            ->leftjoin('product_service_categories', 'invoices.category_id', '=', 'product_service_categories.id')
            // ->where('invoices.created_by', \Auth::user()->creatorId())->where('status', '!=', 0);
            ->when(!empty($request->company), function ($query) use ($request) {
                $query->where('invoices.created_by', '=', $request->company);
            })->where('status', '!=', 0);

            if ($request->period != 'yearly') {
        $invoices->whereRAW('YEAR(send_date) =?', [$year]);
            }

        if (!empty($request->customer)) {
            $invoices->where('customer_id', '=', $request->customer);
        }

        if (!empty($request->category)) {
            $invoices->where('category_id', '=', $request->category);
        }

        $invoices = $invoices->get();

        // ------------------------------------------ invoice ------------------------------------------

        $invoiceTmpArray = [];

        foreach ($invoices as $invoice) {
            $invoiceTmpArray[$invoice->category_id][$invoice->year][$invoice->month][] = $invoice->getTotal(true);
        }

        $invoiceArray = [];

        foreach ($invoiceTmpArray as $key => $yearData) {
            $invoiceArray[$key] = [];

            foreach ($yearList as $targetYear) {
                $invoiceArray[$key][$targetYear] = [];

                for ($i = 1; $i <= 12; $i++) {
                    $invoiceArray[$key][$targetYear][$i] = 0;
                }

                if (isset($yearData[$targetYear])) {
                    foreach ($yearData[$targetYear] as $month => $values) {
                        if (is_array($values)) {
                            $sum = array_sum($values);
                            $invoiceArray[$key][$targetYear][$month] = $sum;
                        } else {
                            $invoiceArray[$key][$targetYear][$month] = (float) $values;
                        }
                    }
                }
            }
        }

        $invoicesum = Utility::billInvoiceData($invoiceArray, $request , $yearList);

        $invoiceTotalArray = [];

        foreach ($invoices as $invoice) {
            $invoiceTotalArray[$invoice->year][$invoice->month][] = $invoice->getTotal(true);
        }
        // ------------------------------------------ income ------------------------------------------

        $incomeArr = [];
        $invoiceArr = [];
        $incomesum = [];

        foreach ($yearList as $year) {
            $invoiceArr[$year] = [];

            for ($i = 1; $i <= 12; $i++) {
                $invoiceArr[$year][$i] = 0;
            }

            if (isset($invoiceTotalArray[$year])) {
                foreach ($invoiceTotalArray[$year] as $month => $values) {
                    $invoiceArr[$year][$month] = array_sum($values);
                }
            }
        }


        foreach ($array as $key => $categoryData) {

            $incomesum[] = Utility::revenuePaymentData($key , $categoryData, $request ,$yearList);

        }

        $revenueTotalArray = [];

        foreach ($incomes as $income) {
            $revenueTotalArray[$income->year][$income->month][] = $income->amount;
        }

        foreach ($yearList as $year) {
            $incomeArr[$year] = [];

            for ($i = 1; $i <= 12; $i++) {
                $incomeArr[$year][$i] = 0;
            }

            if (isset($revenueTotalArray[$year])) {
                foreach ($revenueTotalArray[$year] as $month => $values) {
                    $incomeArr[$year][$month] = array_sum($values);
                }
            }
        }


        $chartIncomeArr = Utility::totalData($invoiceArr, $incomeArr, $request ,$yearList);


        $data['chartIncomeArr'] = $chartIncomeArr;
        $data['incomeArr'] = $incomesum;
        $data['invoiceArray'] = $invoicesum;
        $data['account'] = $account;
        $data['customer'] = $customer;
        $data['category'] = $category;
        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;
        return view('consolidate.income_summary', compact('filter', 'category','company'), $data);
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
