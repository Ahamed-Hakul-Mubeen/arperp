<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Announcement;
use App\Models\AttendanceEmployee;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Bug;
use App\Models\BugStatus;
use App\Models\Contract;
use App\Models\Deal;
use App\Models\DealTask;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Goal;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Meeting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Pos;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Purchase;
use App\Models\Revenue;
use App\Models\Stage;
use App\Models\Tax;
use App\Models\Timesheet;
use App\Models\TimeTracker;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OverviewController extends Controller
{
    public function index(Request $request)
    {

        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();


        $data['latestIncome'] = Revenue::with(['customer'])->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->orderBy('id', 'desc')->limit(5)->get();
        $data['latestExpense'] = Payment::with(['vender'])->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->orderBy('id', 'desc')->limit(5)->get();
        $currentYer = date('Y');

        $incomeCategory = ProductServiceCategory::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('type', '=', 'income')->get();
        // dd($incomeCategory);
        $inColor = array();
        $inCategory = array();
        $inAmount = array();
        for ($i = 0; $i < count($incomeCategory); $i++) {
            $inColor[] = '#' . $incomeCategory[$i]->color;
            $inCategory[] = $incomeCategory[$i]->name;
            $inAmount[] = $incomeCategory[$i]->incomeCategoryRevenueAmount();
        }

        $data['incomeCategoryColor'] = $inColor;
        $data['incomeCategory'] = $inCategory;
        $data['incomeCatAmount'] = $inAmount;


        $expenseCategory = ProductServiceCategory::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('type', '=', 'expense')->get();
        $exColor = array();
        $exCategory = array();
        $exAmount = array();
        for ($i = 0; $i < count($expenseCategory); $i++) {
            $exColor[] = '#' . $expenseCategory[$i]->color;
            $exCategory[] = $expenseCategory[$i]->name;
            $exAmount[] = $expenseCategory[$i]->expenseCategoryAmount();
        }

        $data['expenseCategoryColor'] = $exColor;
        $data['expenseCategory'] = $exCategory;
        $data['expenseCatAmount'] = $exAmount;

        $data['incExpBarChartData'] = \Auth::user()->getConsolidatedIncExpBarChartData();
                    //    dd( $data['incExpBarChartData']);
        $data['incExpLineChartData'] = \Auth::user()->getConsolidatedIncExpLineChartDate();
        // dd($data['incExpLineChartData']);
        $data['currentYear'] = date('Y');
        $data['currentMonth'] = date('M');

        // $constant['taxes'] = Tax::where('created_by', \Auth::user()->creatorId())->count();
        // $constant['category'] = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->count();
        // $constant['units'] = ProductServiceUnit::where('created_by', \Auth::user()->creatorId())->count();
        // $constant['bankAccount'] = BankAccount::where('created_by', \Auth::user()->creatorId())->count();
        $constant['taxes'] = Tax::count();
        $constant['category'] = ProductServiceCategory::count();
        $constant['units'] = ProductServiceUnit::count();
        $constant['bankAccount'] = BankAccount::count();
        $data['constant'] = $constant;
        $data['bankAccountDetail'] = BankAccount::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get();
        $data['recentInvoice'] = Invoice::join('customers', 'invoices.customer_id', '=', 'customers.id')
            // ->where('invoices.created_by', '=', \Auth::user()->creatorId())
            ->orderBy('invoices.id', 'desc')
            ->limit(5)
            ->select('invoices.*', 'customers.name as customer_name')
            ->get();

        $data['weeklyInvoice'] = \Auth::user()->weeklyInvoice();
        $data['monthlyInvoice'] = \Auth::user()->monthlyInvoice();
        $data['recentBill'] = Bill::join('venders', 'bills.vender_id', '=', 'venders.id')
        // ->where('bills.created_by', '=', \Auth::user()->creatorId())
        ->orderBy('bills.id', 'desc')
        ->limit(5)
        ->select('bills.*', 'venders.name as vender_name')
        ->get();

        $data['weeklyBill'] = \Auth::user()->weeklyBill();
        $data['monthlyBill'] = \Auth::user()->monthlyBill();
        $data['goals'] = Goal::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->where('is_display', 1)->get();

        //Storage limit
        // $data['users'] = User::find(\Auth::user()->creatorId());
        $data['users'] = User::find(\Auth::user()->creatorId());
        $data['plan'] = Plan::getPlan(\Auth::user()->show_dashboard());
        // if ($data['plan']->storage_limit > 0) {
        //     $data['storage_limit'] = ($data['users']->storage_limit / $data['plan']->storage_limit) * 100;
        // } else {
        //     $data['storage_limit'] = 0;
        // }
        // dd($data);
        $data['storage_limit'] = 0;
        return view('consolidate.overview', $data);
    }
}
