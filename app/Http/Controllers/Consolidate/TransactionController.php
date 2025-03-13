<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Exports\ConsolidateTransactionExport;
use App\Models\BankAccount;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{

    public function index(Request $request)
    {
        $filter['account']  = __('All');
        $filter['category'] = __('All');

        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        $account = BankAccount::when(!empty($request->company), function($query) use ($request) {
                $query->where('created_by', '=', $request->company);
            })->pluck('holder_name', 'id');
        $account->prepend(__('Stripe / Paypal'), 'strip-paypal');
        $account->prepend('Select Account', '');

        $accounts = Transaction::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')
                                ->leftjoin('bank_accounts', 'transactions.account', '=', 'bank_accounts.id')
                                ->groupBy('transactions.account')->selectRaw('sum(amount) as total');

        $category = ProductServiceCategory::when(!empty($request->company), function($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->whereIn(
            'type', [
                        1,
                        2,
                    ]
        )->get()->pluck('name', 'name');

        $category->prepend('Invoice', 'Invoice');
        $category->prepend('Bill', 'Bill');
        $category->prepend('Select Category', '');

        $transactions = Transaction::orderBy('id', 'desc');

        if(!empty($request->start_month) && !empty($request->end_month))
        {
            $start = strtotime($request->start_month);
            $end   = strtotime($request->end_month);
        }
        else
        {
            $start = strtotime(date('Y-m'));
            $end   = strtotime(date('Y-m', strtotime("-5 month")));
        }

        $currentdate = $start;

        while($currentdate <= $end)
        {
            $data['month'] = date('m', $currentdate);
            $data['year']  = date('Y', $currentdate);

            $transactions->Orwhere(
                function ($query) use ($data, $request){
                    $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                    // $query->where('transactions.created_by', '=', \Auth::user()->creatorId());
                    $query->when(!empty($request->company), function($query1) use ($request) {
                        $query1->where('transactions.created_by', '=', $request->company);
                    });
                }
            );

            $accounts->Orwhere(
                function ($query) use ($data, $request){
                    $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                    // $query->where('transactions.created_by', '=', \Auth::user()->creatorId());
                    $query->when(!empty($request->company), function($query) use ($request) {
                        $query->where('transactions.created_by', '=', $request->company);
                    });

                }
            );

            $currentdate = strtotime('+1 month', $currentdate);
        }

        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange']   = date('M-Y', $end);


        if(!empty($request->account))
        {
            $transactions->where('account', $request->account);

            if($request->account == 'strip-paypal')
            {
                $accounts->where('account', 0);
                $filter['account'] = __('Stripe / Paypal');
            }
            else
            {
                $accounts->where('account', $request->account);
                $bankAccount       = BankAccount::find($request->account);
                $filter['account'] = !empty($bankAccount) ? $bankAccount->holder_name . ' - ' . $bankAccount->bank_name : '';
                if($bankAccount->holder_name == 'Cash')
                {
                    $filter['account'] = 'Cash';
                }
            }

        }
        if(!empty($request->category))
        {
            $transactions->where('category', $request->category);
            $accounts->where('category', $request->category);

            $filter['category'] = $request->category;
        }

        // $transactions->where('created_by', '=', \Auth::user()->creatorId());
        $transactions->when(!empty($request->company), function($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        });
        // $accounts->where('transactions.created_by', '=', \Auth::user()->creatorId());
        $accounts->when(!empty($request->company), function($query) use ($request) {
            $query->where('transactions.created_by', '=', $request->company);
        });
        $transactions = $transactions->with(['bankAccount'])->get();
        $accounts     = $accounts->get();

        return view('consolidate.transaction', compact('transactions', 'account', 'category', 'filter', 'accounts', 'company'));
    }

    public function export(Request $request)
    {
        $name = 'transaction_' . date('Y-m-d i:h:s');
        $data = Excel::download(new ConsolidateTransactionExport($request->company), $name . '.xlsx');

        return $data;
    }
}
