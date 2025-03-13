<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\User;
use App\Models\Vender;
use App\Models\Bill;

class CashflowController extends Controller
{
    public function monthly(Request $request){
        $data['monthList'] = $month = $this->yearMonth();
        $data['yearList'] = $this->yearList();

        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        if (isset($request->year)) {
            $year = $request->year;
        } else {
            $year = date('Y');
        }
        $data['currentYear'] = $year;

        // -------------------------------REVENUE INCOME-------------------------------------------------

        // ------------------------------REVENUE INCOME-----------------------------------
        $incomes = Revenue::selectRaw('sum(revenues.amount * revenues.exchange_rate) as amount,MONTH(date) as month,YEAR(date) as year,category_id')
            ->leftjoin('product_service_categories', 'revenues.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 1);
        // $incomes->where('revenues.created_by', '=', \Auth::user()->creatorId());
        $incomes->when(!empty($request->company), function ($query) use ($request) {
            $query->where('revenues.created_by', '=', $request->company);
        });
        $incomes->whereRAW('YEAR(date) =?', [$year]);

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
            $tmpArray[$income->category_id][$income->month] = $income->amount;
        }
        $array = [];
        foreach ($tmpArray as $cat_id => $record) {
            $tmp = [];
            $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $tmp['data'] = [];
            for ($i = 1; $i <= 12; $i++) {
                $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
            }
            $array[] = $tmp;
        }

        $incomesData = Revenue::selectRaw('sum(revenues.amount * revenues.exchange_rate) as amount,MONTH(date) as month,YEAR(date) as year');
        // $incomesData->where('revenues.created_by', '=', \Auth::user()->creatorId());
        $incomesData->when(!empty($request->company), function ($query) use ($request) {
            $query->where('revenues.created_by', '=', $request->company);
        });
        $incomesData->whereRAW('YEAR(date) =?', [$year]);

        if (!empty($request->category)) {
            $incomesData->where('category_id', '=', $request->category);
        }
        if (!empty($request->customer)) {
            $incomesData->where('customer_id', '=', $request->customer);
        }
        $incomesData->groupBy('month', 'year');
        $incomesData = $incomesData->get();
        $incomeArr = [];
        foreach ($incomesData as $k => $incomeData) {
            $incomeArr[$incomeData->month] = $incomeData->amount;
        }
        for ($i = 1; $i <= 12; $i++) {
            $incomeTotal[] = array_key_exists($i, $incomeArr) ? $incomeArr[$i] : 0;
        }

        //---------------------------INVOICE INCOME-----------------------------------------------

        $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')
            // ->where('created_by', \Auth::user()->creatorId())
            ->when(!empty($request->company), function ($query) use ($request) {
                $query->where('created_by', '=', $request->company);
            })
            ->where('status', '!=', 0);

        $invoices->whereRAW('YEAR(send_date) =?', [$year]);

        if (!empty($request->customer)) {
            $invoices->where('customer_id', '=', $request->customer);
        }

        if (!empty($request->category)) {
            $invoices->where('category_id', '=', $request->category);
        }

        $invoices = $invoices->get();
        $invoiceTmpArray = [];
        foreach ($invoices as $invoice) {
            $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $invoice->getTotal(true);
        }

        $invoiceArray = [];
        foreach ($invoiceTmpArray as $cat_id => $record) {

            $invoice = [];
            $productCtegory = ProductServiceCategory::where('id', '=', $cat_id)->first();
            $invoice['category'] = !empty($productCtegory) ? $productCtegory->name : '';
            $invoice['data'] = [];
            for ($i = 1; $i <= 12; $i++) {

                $invoice['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
            }
            $invoiceArray[] = $invoice;
        }

        $invoiceTotalArray = [];
        foreach ($invoices as $invoice) {
            $invoiceTotalArray[$invoice->month][] = $invoice->getTotal(true);
        }
        for ($i = 1; $i <= 12; $i++) {
            $invoiceTotal[] = array_key_exists($i, $invoiceTotalArray) ? array_sum($invoiceTotalArray[$i]) : 0;
        }

        $chartIncomeArr = array_map(
            function () {
                return array_sum(func_get_args());
            }, $incomeTotal, $invoiceTotal
        );

        $data['chartIncomeArr'] = $chartIncomeArr;
        $data['incomeArr'] = $array;
        $data['invoiceArray'] = $invoiceArray;

        //   -----------------------------------------PAYMENT EXPENSE ------------------------------------------------------------
        $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id')->leftjoin('product_service_categories', 'payments.category_id', '=', 'product_service_categories.id')->where('product_service_categories.type', '=', 2);
        // $expenses->where('payments.created_by', '=', \Auth::user()->creatorId());
        $expenses->when(!empty($request->company), function ($query) use ($request) {
            $query->where('payments.created_by', '=', $request->company);
        });
        $expenses->whereRAW('YEAR(date) =?', [$year]);

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
            $tmpArray[$expense->category_id][$expense->month] = $expense->amount;
        }
        $array = [];
        foreach ($tmpArray as $cat_id => $record) {
            $tmp = [];
            $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $tmp['data'] = [];
            for ($i = 1; $i <= 12; $i++) {
                $tmp['data'][$i] = array_key_exists($i, $record) ? $record[$i] : 0;
            }
            $array[] = $tmp;
        }
        $expensesData = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year');
        // $expensesData->where('payments.created_by', '=', \Auth::user()->creatorId());
        $expensesData->when(!empty($request->company), function ($query) use ($request) {
            $query->where('payments.created_by', '=', $request->company);
        });
        $expensesData->whereRAW('YEAR(date) =?', [$year]);

        if (!empty($request->category)) {
            $expensesData->where('category_id', '=', $request->category);
        }
        if (!empty($request->vender)) {
            $expensesData->where('vender_id', '=', $request->vender);
        }
        $expensesData->groupBy('month', 'year');
        $expensesData = $expensesData->get();

        $expenseArr = [];
        foreach ($expensesData as $k => $expenseData) {
            $expenseArr[$expenseData->month] = $expenseData->amount;
        }
        for ($i = 1; $i <= 12; $i++) {
            $expenseTotal[] = array_key_exists($i, $expenseArr) ? $expenseArr[$i] : 0;
        }

        //     ------------------------------------BILL EXPENSE----------------------------------------------------

        $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('status', '!=', 0);
        $bills->whereRAW('YEAR(send_date) =?', [$year]);

        if (!empty($request->vender)) {
            $bills->where('vender_id', '=', $request->vender);
        }

        if (!empty($request->category)) {
            $bills->where('category_id', '=', $request->category);
        }
        $bills = $bills->get();
        $billTmpArray = [];
        foreach ($bills as $bill) {
            $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
        }

        $billArray = [];
        foreach ($billTmpArray as $cat_id => $record) {

            $bill = [];
            $productCategory = ProductServiceCategory::where('id', '=', $cat_id)->first();
            $bill['category'] = !empty($productCategory) ? $productCategory->name : '';
            $bill['data'] = [];
            for ($i = 1; $i <= 12; $i++) {

                $bill['data'][$i] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
            }
            $billArray[] = $bill;
        }

        $billTotalArray = [];
        foreach ($bills as $bill) {
            $billTotalArray[$bill->month][] = $bill->getTotal();
        }
        for ($i = 1; $i <= 12; $i++) {
            $billTotal[] = array_key_exists($i, $billTotalArray) ? array_sum($billTotalArray[$i]) : 0;
        }

        $chartExpenseArr = array_map(
            function () {
                return array_sum(func_get_args());
            }, $expenseTotal, $billTotal
        );

        $netProfit = [];
        $keys = array_keys($chartIncomeArr + $chartExpenseArr);
        foreach ($keys as $v) {
            $netProfit[$v] = (empty($chartIncomeArr[$v]) ? 0 : $chartIncomeArr[$v]) - (empty($chartExpenseArr[$v]) ? 0 : $chartExpenseArr[$v]);
        }

        $data['chartExpenseArr'] = $chartExpenseArr;
        $data['expenseArr'] = $array;
        $data['billArray'] = $billArray;

        $data['netProfitArray'] = $netProfit;
        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;

        return view('consolidate.monthly_cashflow', compact('filter', 'company'), $data);
    }
    public function quarterly(Request $request)
    {
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        $data['month'] = [
            'Jan-Mar',
            'Apr-Jun',
            'Jul-Sep',
            'Oct-Dec',
            'Total',
        ];
        $data['monthList'] = $month = $this->yearMonth();
        $data['yearList'] = $this->yearList();

        if (isset($request->year)) {
            $year = $request->year;
        } else {
            $year = date('Y');
        }
        $data['currentYear'] = $year;

        // -------------------------------REVENUE INCOME-------------------------------------------------

        $incomes = Revenue::selectRaw('sum(revenues.amount * revenues.exchange_rate) as amount,MONTH(date) as month,YEAR(date) as year,category_id');
        // $incomes->where('created_by', '=', \Auth::user()->creatorId());
        $incomes->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        });
        $incomes->whereRAW('YEAR(date) =?', [$year]);
        $incomes->groupBy('month', 'year', 'category_id');
        $incomes = $incomes->get();
        $tmpIncomeArray = [];
        foreach ($incomes as $income) {
            $tmpIncomeArray[$income->category_id][$income->month] = $income->amount;
        }

        $incomeCatAmount_1 = $incomeCatAmount_2 = $incomeCatAmount_3 = $incomeCatAmount_4 = 0;
        $revenueIncomeArray = array();
        foreach ($tmpIncomeArray as $cat_id => $record) {

            $tmp = [];
            $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $sumData = [];
            for ($i = 1; $i <= 12; $i++) {
                $sumData[] = array_key_exists($i, $record) ? $record[$i] : 0;
            }

            $month_1 = array_slice($sumData, 0, 3);
            $month_2 = array_slice($sumData, 3, 3);
            $month_3 = array_slice($sumData, 6, 3);
            $month_4 = array_slice($sumData, 9, 3);

            $incomeData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
            $incomeData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
            $incomeData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
            $incomeData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
            $incomeData[__('Total')] = array_sum(
                array(
                    $sum_1,
                    $sum_2,
                    $sum_3,
                    $sum_4,
                )
            );

            $incomeCatAmount_1 += $sum_1;
            $incomeCatAmount_2 += $sum_2;
            $incomeCatAmount_3 += $sum_3;
            $incomeCatAmount_4 += $sum_4;

            $data['month'] = array_keys($incomeData);
            $tmp['amount'] = array_values($incomeData);

            $revenueIncomeArray[] = $tmp;

        }

        $data['incomeCatAmount'] = $incomeCatAmount = [
            $incomeCatAmount_1,
            $incomeCatAmount_2,
            $incomeCatAmount_3,
            $incomeCatAmount_4,
            array_sum(
                array(
                    $incomeCatAmount_1,
                    $incomeCatAmount_2,
                    $incomeCatAmount_3,
                    $incomeCatAmount_4,
                )
            ),
        ];

        $data['revenueIncomeArray'] = $revenueIncomeArray;

        //-----------------------INVOICE INCOME---------------------------------------------

        $invoices = Invoice::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,invoice_id,id')->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('status', '!=', 0);
        $invoices->whereRAW('YEAR(send_date) =?', [$year]);
        if (!empty($request->customer)) {
            $invoices->where('customer_id', '=', $request->customer);
        }
        $invoices = $invoices->get();

        $invoiceTmpArray = [];
        foreach ($invoices as $invoice) {
            $invoiceTmpArray[$invoice->category_id][$invoice->month][] = $invoice->getTotal(true);
        }

        $invoiceCatAmount_1 = $invoiceCatAmount_2 = $invoiceCatAmount_3 = $invoiceCatAmount_4 = 0;

        $invoiceIncomeArray = array();
        foreach ($invoiceTmpArray as $cat_id => $record) {

            $invoiceTmp = [];
            $invoiceTmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $invoiceSumData = [];
            for ($i = 1; $i <= 12; $i++) {
                $invoiceSumData[] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;

            }

            $month_1 = array_slice($invoiceSumData, 0, 3);
            $month_2 = array_slice($invoiceSumData, 3, 3);
            $month_3 = array_slice($invoiceSumData, 6, 3);
            $month_4 = array_slice($invoiceSumData, 9, 3);
            $invoiceIncomeData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
            $invoiceIncomeData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
            $invoiceIncomeData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
            $invoiceIncomeData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
            $invoiceIncomeData[__('Total')] = array_sum(
                array(
                    $sum_1,
                    $sum_2,
                    $sum_3,
                    $sum_4,
                )
            );
            $invoiceCatAmount_1 += $sum_1;
            $invoiceCatAmount_2 += $sum_2;
            $invoiceCatAmount_3 += $sum_3;
            $invoiceCatAmount_4 += $sum_4;

            $invoiceTmp['amount'] = array_values($invoiceIncomeData);

            $invoiceIncomeArray[] = $invoiceTmp;

        }

        $data['invoiceIncomeCatAmount'] = $invoiceIncomeCatAmount = [
            $invoiceCatAmount_1,
            $invoiceCatAmount_2,
            $invoiceCatAmount_3,
            $invoiceCatAmount_4,
            array_sum(
                array(
                    $invoiceCatAmount_1,
                    $invoiceCatAmount_2,
                    $invoiceCatAmount_3,
                    $invoiceCatAmount_4,
                )
            ),
        ];

        $data['invoiceIncomeArray'] = $invoiceIncomeArray;

        $data['totalIncome'] = $totalIncome = array_map(
            function () {
                return array_sum(func_get_args());
            }, $invoiceIncomeCatAmount, $incomeCatAmount
        );

        //---------------------------------PAYMENT EXPENSE-----------------------------------

        $expenses = Payment::selectRaw('sum(payments.amount) as amount,MONTH(date) as month,YEAR(date) as year,category_id');
        // $expenses->where('created_by', '=', \Auth::user()->creatorId());
        $expenses->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        });
        $expenses->whereRAW('YEAR(date) =?', [$year]);
        $expenses->groupBy('month', 'year', 'category_id');
        $expenses = $expenses->get();

        $tmpExpenseArray = [];
        foreach ($expenses as $expense) {
            $tmpExpenseArray[$expense->category_id][$expense->month] = $expense->amount;
        }

        $expenseArray = [];
        $expenseCatAmount_1 = $expenseCatAmount_2 = $expenseCatAmount_3 = $expenseCatAmount_4 = 0;
        foreach ($tmpExpenseArray as $cat_id => $record) {
            $tmp = [];
            $tmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $expenseSumData = [];
            for ($i = 1; $i <= 12; $i++) {
                $expenseSumData[] = array_key_exists($i, $record) ? $record[$i] : 0;

            }

            $month_1 = array_slice($expenseSumData, 0, 3);
            $month_2 = array_slice($expenseSumData, 3, 3);
            $month_3 = array_slice($expenseSumData, 6, 3);
            $month_4 = array_slice($expenseSumData, 9, 3);

            $expenseData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
            $expenseData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
            $expenseData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
            $expenseData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
            $expenseData[__('Total')] = array_sum(
                array(
                    $sum_1,
                    $sum_2,
                    $sum_3,
                    $sum_4,
                )
            );

            $expenseCatAmount_1 += $sum_1;
            $expenseCatAmount_2 += $sum_2;
            $expenseCatAmount_3 += $sum_3;
            $expenseCatAmount_4 += $sum_4;

            $data['month'] = array_keys($expenseData);
            $tmp['amount'] = array_values($expenseData);

            $expenseArray[] = $tmp;

        }

        $data['expenseCatAmount'] = $expenseCatAmount = [
            $expenseCatAmount_1,
            $expenseCatAmount_2,
            $expenseCatAmount_3,
            $expenseCatAmount_4,
            array_sum(
                array(
                    $expenseCatAmount_1,
                    $expenseCatAmount_2,
                    $expenseCatAmount_3,
                    $expenseCatAmount_4,
                )
            ),
        ];
        $data['expenseArray'] = $expenseArray;

        //    ----------------------------EXPENSE BILL-----------------------------------------------------------------------

        $bills = Bill::selectRaw('MONTH(send_date) as month,YEAR(send_date) as year,category_id,bill_id,id')->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('status', '!=', 0);
        $bills->whereRAW('YEAR(send_date) =?', [$year]);
        if (!empty($request->customer)) {
            $bills->where('vender_id', '=', $request->vender);
        }
        $bills = $bills->get();
        $billTmpArray = [];
        foreach ($bills as $bill) {
            $billTmpArray[$bill->category_id][$bill->month][] = $bill->getTotal();
        }

        $billExpenseArray = [];
        $billExpenseCatAmount_1 = $billExpenseCatAmount_2 = $billExpenseCatAmount_3 = $billExpenseCatAmount_4 = 0;
        foreach ($billTmpArray as $cat_id => $record) {
            $billTmp = [];
            $billTmp['category'] = !empty(ProductServiceCategory::where('id', '=', $cat_id)->first()) ? ProductServiceCategory::where('id', '=', $cat_id)->first()->name : '';
            $billExpensSumData = [];
            for ($i = 1; $i <= 12; $i++) {
                $billExpensSumData[] = array_key_exists($i, $record) ? array_sum($record[$i]) : 0;
            }

            $month_1 = array_slice($billExpensSumData, 0, 3);
            $month_2 = array_slice($billExpensSumData, 3, 3);
            $month_3 = array_slice($billExpensSumData, 6, 3);
            $month_4 = array_slice($billExpensSumData, 9, 3);

            $billExpenseData[__('Jan-Mar')] = $sum_1 = array_sum($month_1);
            $billExpenseData[__('Apr-Jun')] = $sum_2 = array_sum($month_2);
            $billExpenseData[__('Jul-Sep')] = $sum_3 = array_sum($month_3);
            $billExpenseData[__('Oct-Dec')] = $sum_4 = array_sum($month_4);
            $billExpenseData[__('Total')] = array_sum(
                array(
                    $sum_1,
                    $sum_2,
                    $sum_3,
                    $sum_4,
                )
            );

            $billExpenseCatAmount_1 += $sum_1;
            $billExpenseCatAmount_2 += $sum_2;
            $billExpenseCatAmount_3 += $sum_3;
            $billExpenseCatAmount_4 += $sum_4;

            $data['month'] = array_keys($billExpenseData);
            $billTmp['amount'] = array_values($billExpenseData);

            $billExpenseArray[] = $billTmp;

        }

        $data['billExpenseCatAmount'] = $billExpenseCatAmount = [
            $billExpenseCatAmount_1,
            $billExpenseCatAmount_2,
            $billExpenseCatAmount_3,
            $billExpenseCatAmount_4,
            array_sum(
                array(
                    $billExpenseCatAmount_1,
                    $billExpenseCatAmount_2,
                    $billExpenseCatAmount_3,
                    $billExpenseCatAmount_4,
                )
            ),
        ];

        $data['billExpenseArray'] = $billExpenseArray;

        $data['totalExpense'] = $totalExpense = array_map(
            function () {
                return array_sum(func_get_args());
            }, $billExpenseCatAmount, $expenseCatAmount
        );

        foreach ($totalIncome as $k => $income) {
            $netProfit[] = $income - $totalExpense[$k];
        }
        $data['netProfitArray'] = $netProfit;

        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;

        return view('consolidate.quarterly_cashflow', compact('filter', 'company'), $data);
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
