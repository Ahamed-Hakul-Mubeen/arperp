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
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\User;
use App\Models\Vender;

class IncomeVsExpenseController extends Controller
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
        $customer = Customer::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('name', 'id');
        $customer->prepend('Select Customer', '');

        $category = ProductServiceCategory::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->whereIn(
            'type', [
                'income',
                'expense',
            ]
        )->get()->pluck('name', 'id');
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

        // ------------------------------TOTAL PAYMENT EXPENSE-----------------------------------------------------------
        $expensesData = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year');
        // $expensesData->where('payments.created_by', '=', \Auth::user()->creatorId());
        $expensesData->when(!empty($request->company), function ($query) use ($request) {
            $query->where('payments.created_by', '=', $request->company);
        });
        if ($request->period != 'yearly') {
            $expensesData->whereRAW('YEAR(date) =?', [$year]);
        }

        if (!empty($request->category)) {
            $expensesData->where('category_id', '=', $request->category);
            $cat = ProductServiceCategory::find($request->category);
            $filter['category'] = !empty($cat) ? $cat->name : '';

        }
        if (!empty($request->vender)) {
            $expensesData->where('vender_id', '=', $request->vender);

            $vend = Vender::find($request->vender);
            $filter['vender'] = !empty($vend) ? $vend->name : '';
        }
        $expensesData->groupBy('month', 'year');
        $expensesData = $expensesData->get();

        // ------------------------------TOTAL BILL EXPENSE-----------------------------------------------------------

        $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('status', '!=', 0);
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

        $paymentTotalArray = [];
        foreach ($expensesData as $expense) {
            $paymentTotalArray[$expense->year][$expense->month][] = $expense->amount;
        }
        $expenseArr = [];

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

        $billTotalArray = [];
        foreach ($bills as $bill) {
            $billTotalArray[$bill->year][$bill->month][] = $bill->getTotal();
        }

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


        $billsum = Utility::totalSum($billArr, $request , $yearList);

        $expensesum = Utility::totalSum($expenseArr, $request , $yearList);

        $chartExpenseArr = Utility::totalData($billArr, $expenseArr, $request , $yearList);

        // ------------------------------TOTAL REVENUE INCOME-----------------------------------------------------------

        $incomesData = Revenue::selectRaw('sum(revenues.amount * revenues.exchange_rate) as amount,MONTH(date) as month,YEAR(date) as year');
        // $incomesData->where('revenues.created_by', '=', \Auth::user()->creatorId());
        $incomesData->when(!empty($request->company), function ($query) use ($request) {
            $query->where('revenues.created_by', '=', $request->company);
        });
        if ($request->period != 'yearly') {
            $incomesData->whereRAW('YEAR(date) =?', [$year]);
        }

        if (!empty($request->category)) {
            $incomesData->where('category_id', '=', $request->category);
        }
        if (!empty($request->customer)) {
            $incomesData->where('customer_id', '=', $request->customer);
            $cust = Customer::find($request->customer);
            $filter['customer'] = !empty($cust) ? $cust->name : '';
        }
        $incomesData->groupBy('month', 'year');
        $incomesData = $incomesData->get();

        // ------------------------------TOTAL INVOICE INCOME-----------------------------------------------------------
        $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')
            ->when(!empty($request->company), function ($query) use ($request) {
                $query->where('created_by', '=', $request->company);
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

        $revenueTotalArray = [];
        foreach ($incomesData as $income) {
            $revenueTotalArray[$income->year][$income->month][] = $income->amount;
        }

        $incomeArr = [];

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

        $invoiceTotalArray = [];
        foreach ($invoices as $invoice) {
            $invoiceTotalArray[$invoice->year][$invoice->month][] = $invoice->getTotal(true);
        }

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

        $invoicesum = Utility::totalSum($invoiceArr, $request , $yearList);

        $incomesum = Utility::totalSum($incomeArr, $request , $yearList);

        $chartIncomeArr = Utility::totalData($invoiceArr, $incomeArr, $request , $yearList);



        $profit = [];

            if (count($chartIncomeArr) === count($chartExpenseArr) && count($chartIncomeArr[0]) === count($chartExpenseArr[0])) {
                foreach ($chartIncomeArr as $i => $values1) {
                    foreach ($values1 as $j => $value1) {
                        $profit[$i][$j] = $value1 - $chartExpenseArr[$i][$j];
                    }
                }
            }


        $data['paymentExpenseTotal'] = $expensesum;
        $data['billExpenseTotal'] = $billsum;
        $data['revenueIncomeTotal'] = $incomesum;
        $data['invoiceIncomeTotal'] = $invoicesum;
        $data['profit'] = $profit;
        $data['account'] = $account;
        $data['vender'] = $vender;
        $data['customer'] = $customer;
        $data['category'] = $category;

        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;

        return view('consolidate.income_vs_expense_summary', compact('filter','company'), $data);
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
