<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\InvoiceProduct;
use App\Models\Utility;
use App\Models\BillProduct;
use App\Models\User;

class TaxSummaryController extends Controller
{
    public function index(Request $request)
    {

        $user = \Auth::user();
        $company = User::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        $data['monthList'] = $month = $this->yearMonth();
        $data['yearList'] = $this->yearList();
        $data['taxList'] = $taxList = Tax::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get();

        if (isset($request->year)) {
            $year = $request->year;
        } else {
            $year = date('Y');
        }

        $data['currentYear'] = $year;

        $invoiceProducts = InvoiceProduct::selectRaw('invoice_products.* ,MONTH(invoice_products.created_at) as month,YEAR(invoice_products.created_at) as year')->leftjoin('product_services', 'invoice_products.product_id', '=', 'product_services.id')->whereRaw('YEAR(invoice_products.created_at) =?', [$year])->when(!empty($request->company), function ($query) use ($request) {
            $query->where('product_services.created_by', '=', $request->company);
        })->get();

        $incomeTaxesData = [];

        foreach ($invoiceProducts as $invoiceProduct) {
            $incomeTax = [];
            $getTaxData = Utility::getTaxData();

            if ($invoiceProduct->tax != null) {
                foreach (explode(',', $invoiceProduct->tax) as $tax) {
                    $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $invoiceProduct->price, $invoiceProduct->quantity);

                    $itemName = $getTaxData[$tax]['name'];
                    $itemTax['name'] = $itemName;
                    $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';
                    $itemTax['price'] = ($taxPrice);

                    if (!isset($incomeTax[$itemName])) {
                        $incomeTax[$itemName] = 0;
                    }

                    $incomeTax[$itemName] += $itemTax['price'];
                }
            }
            $incomeTaxesData[$invoiceProduct->month][] = $incomeTax;
        }

        $income = [];
        foreach ($incomeTaxesData as $month => $incomeTaxx) {
            $incomeTaxRecord = [];
            foreach ($incomeTaxx as $k => $record) {
                foreach ($record as $incomeTaxName => $incomeTaxAmount) {
                    if (array_key_exists($incomeTaxName, $incomeTaxRecord)) {
                        $incomeTaxRecord[$incomeTaxName] += $incomeTaxAmount;
                    } else {
                        $incomeTaxRecord[$incomeTaxName] = $incomeTaxAmount;
                    }
                }
                $income['data'][$month] = $incomeTaxRecord;
            }

        }

        foreach ($income as $incomeMonth => $incomeTaxData) {
            $incomeData = [];
            for ($i = 1; $i <= 12; $i++) {
                $incomeData[$i] = array_key_exists($i, $incomeTaxData) ? $incomeTaxData[$i] : 0;
            }

        }

        $incomes = [];
        if (isset($incomeData) && !empty($incomeData)) {
            foreach ($taxList as $taxArr) {
                foreach ($incomeData as $month => $tax) {
                    if ($tax != 0) {
                        if (isset($tax[$taxArr->name])) {
                            $incomes[$taxArr->name][$month] = $tax[$taxArr->name];
                        } else {
                            $incomes[$taxArr->name][$month] = 0;
                        }
                    } else {
                        $incomes[$taxArr->name][$month] = 0;
                    }
                }
            }
        }

        $billProducts = BillProduct::selectRaw('bill_products.* ,MONTH(bill_products.created_at) as month,YEAR(bill_products.created_at) as year')->leftjoin('product_services', 'bill_products.product_id', '=', 'product_services.id')->whereRaw('YEAR(bill_products.created_at) =?', [$year])->when(!empty($request->company), function ($query) use ($request) {
            $query->where('product_services.created_by', '=', $request->company);
        })->get();

        $expenseTaxesData = [];
        foreach ($billProducts as $billProduct) {
            $billTax = [];

            $getTaxData = Utility::getTaxData();
            $taxesData = [];

            if ($invoiceProduct->tax != null) {

                foreach (explode(',', $billProduct->tax) as $tax) {
                    $taxPrice = \Utility::taxRate($getTaxData[$tax]['rate'], $billProduct->price, $billProduct->quantity);
                    $itemName = $getTaxData[$tax]['name'];
                    $itemTax['name'] = $itemName;
                    $itemTax['rate'] = $getTaxData[$tax]['rate'] . '%';
                    $itemTax['price'] = ($taxPrice);

                    if (!isset($billTax[$itemName])) {
                        $billTax[$itemName] = 0;
                    }
                    $billTax[$itemName] += $itemTax['price'];
                }
            }
            $expenseTaxesData[$billProduct->month][] = $billTax;
        }

        $bill = [];
        foreach ($expenseTaxesData as $month => $billTaxx) {
            $billTaxRecord = [];
            foreach ($billTaxx as $k => $record) {
                foreach ($record as $billTaxName => $billTaxAmount) {
                    if (array_key_exists($billTaxName, $billTaxRecord)) {
                        $billTaxRecord[$billTaxName] += $billTaxAmount;
                    } else {
                        $billTaxRecord[$billTaxName] = $billTaxAmount;
                    }
                }
                $bill['data'][$month] = $billTaxRecord;
            }

        }

        foreach ($bill as $billMonth => $billTaxData) {
            $billData = [];
            for ($i = 1; $i <= 12; $i++) {
                $billData[$i] = array_key_exists($i, $billTaxData) ? $billTaxData[$i] : 0;
            }

        }
        $expenses = [];
        if (isset($billData) && !empty($billData)) {

            foreach ($taxList as $taxArr) {
                foreach ($billData as $month => $tax) {
                    if ($tax != 0) {
                        if (isset($tax[$taxArr->name])) {
                            $expenses[$taxArr->name][$month] = $tax[$taxArr->name];
                        } else {
                            $expenses[$taxArr->name][$month] = 0;
                        }
                    } else {
                        $expenses[$taxArr->name][$month] = 0;
                    }
                }

            }
        }

        $data['expenses'] = $expenses;
        $data['incomes'] = $incomes;

        $filter['startDateRange'] = 'Jan-' . $year;
        $filter['endDateRange'] = 'Dec-' . $year;

        return view('consolidate.tax_summary', compact('filter', 'company'), $data);
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
