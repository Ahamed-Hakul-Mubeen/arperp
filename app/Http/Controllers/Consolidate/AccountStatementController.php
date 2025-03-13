<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use App\Models\Revenue;
use App\Models\Payment;
use App\Models\User;



class AccountStatementController extends Controller
{
    public function index(Request $request)
    {

        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();

        $filter['account'] = __('All');
        $filter['type'] = __('Revenue');
        $reportData['revenues'] = '';
        $reportData['payments'] = '';
        $reportData['revenueAccounts'] = '';
        $reportData['paymentAccounts'] = '';

        $account = BankAccount::when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get()->pluck('holder_name', 'id');
        $account->prepend('Select Account', '');

        $types = [
            'revenue' => __('Revenue'),
            'payment' => __('Payment'),
        ];

        if ($request->type == 'revenue' || !isset($request->type)) {

            $revenueAccounts = Revenue::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')->leftjoin('bank_accounts', 'revenues.account_id', '=', 'bank_accounts.id')->groupBy('revenues.account_id')->selectRaw('sum(amount) as total')->when(!empty($request->company), function ($query) use ($request) {
                $query->where('revenues.created_by', '=', $request->company);
            });

            $revenues = Revenue::when(!empty($request->company), function ($query) use ($request) {
                $query->where('revenues.created_by', '=', $request->company);
            })->orderBy('id', 'desc');
        }

        if ($request->type == 'payment') {
            $paymentAccounts = Payment::select('bank_accounts.id', 'bank_accounts.holder_name', 'bank_accounts.bank_name')->leftjoin('bank_accounts', 'payments.account_id', '=', 'bank_accounts.id')->groupBy('payments.account_id')->selectRaw('sum(amount) as total')->when(!empty($request->company), function ($query) use ($request) {
            $query->where('payments.created_by', '=', $request->company);
        });

            $payments = Payment::when(!empty($request->company), function ($query) use ($request) {
                $query->where('payments.created_by', '=', $request->company);
            })->orderBy('id', 'desc');
        }

        if (!empty($request->start_month) && !empty($request->end_month)) {
            $start = strtotime($request->start_month);
            $end = strtotime($request->end_month);
        } else {
            $start = strtotime(date('Y-m'));
            $end = strtotime(date('Y-m', strtotime("-5 month")));
        }

        $currentdate = $start;
        while ($currentdate <= $end) {
            $data['month'] = date('m', $currentdate);
            $data['year'] = date('Y', $currentdate);

            if ($request->type == 'revenue' || !isset($request->type)) {
                $revenues->Orwhere(
                    function ($query) use ($data, $request) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        // $query->where('revenues.created_by', '=', \Auth::user()->creatorId());
                        $query->when(!empty($request->company), function ($query1) use ($request) {
                            $query1->where('revenues.created_by', '=', $request->company);
                        });
                    }
                );

                $revenueAccounts->Orwhere(
                    function ($query) use ($data, $request) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        // $query->where('revenues.created_by', '=', \Auth::user()->creatorId());
                        $query->when(!empty($request->company), function ($query1) use ($request) {
                            $query1->where('revenues.created_by', '=', $request->company);
                        });
                    }
                );
            }

            if ($request->type == 'payment') {
                $paymentAccounts->Orwhere(
                    function ($query) use ($data, $request) {
                        $query->whereMonth('date', $data['month'])->whereYear('date', $data['year']);
                        // $query->where('payments.created_by', '=', \Auth::user()->creatorId());
                        $query->when(!empty($request->company), function ($query1) use ($request) {
                            $query1->where('payments.created_by', '=', $request->company);
                        });
                    }
                );
            }

            $currentdate = strtotime('+1 month', $currentdate);
        }

        if (!empty($request->account)) {
            if ($request->type == 'revenue' || !isset($request->type)) {
                $revenues->where('account_id', $request->account);
                // $revenues->where('revenues.created_by', '=', \Auth::user()->creatorId());
                $revenues->when(!empty($request->company), function ($query) use ($request) {
                    $query->where('revenues.created_by', '=', $request->company);
                });
                $revenueAccounts->where('account_id', $request->account);
                // $revenueAccounts->where('revenues.created_by', '=', \Auth::user()->creatorId());
                $revenueAccounts->when(!empty($request->company), function ($query) use ($request) {
                    $query->where('revenues.created_by', '=', $request->company);
                });
            }

            if ($request->type == 'payment') {
                $payments->where('account_id', $request->account);
                // $payments->where('payments.created_by', '=', \Auth::user()->creatorId());
                $payments->when(!empty($request->company), function ($query) use ($request) {
                    $query->where('payments.created_by', '=', $request->company);
                });

                $paymentAccounts->where('account_id', $request->account);
                // $paymentAccounts->where('payments.created_by', '=', \Auth::user()->creatorId());
                $paymentAccounts->when(!empty($request->company), function ($query) use ($request) {
                    $query->where('payments.created_by', '=', $request->company);
                });
            }

            $bankAccount = BankAccount::find($request->account);
            $filter['account'] = !empty($bankAccount) ? $bankAccount->holder_name . ' - ' . $bankAccount->bank_name : '';
            if ($bankAccount->holder_name == 'Cash') {
                $filter['account'] = 'Cash';
            }

        }

        if ($request->type == 'revenue' || !isset($request->type)) {
            $reportData['revenues'] = $revenues->get();

            // $revenueAccounts->where('revenues.created_by', '=', \Auth::user()->creatorId());
            $revenueAccounts->when(!empty($request->company), function ($query) use ($request) {
                $query->where('revenues.created_by', '=', $request->company);
            });
            $reportData['revenueAccounts'] = $revenueAccounts->get();

        }

        if ($request->type == 'payment') {
            $reportData['payments'] = $payments->get();

            // $paymentAccounts->where('payments.created_by', '=', \Auth::user()->creatorId());
            $paymentAccounts->when(!empty($request->company), function ($query) use ($request) {
                $query->where('payments.created_by', '=', $request->company);
            });
            $reportData['paymentAccounts'] = $paymentAccounts->get();
            $filter['type'] = __('Payment');
        }

        $filter['startDateRange'] = date('M-Y', $start);
        $filter['endDateRange'] = date('M-Y', $end);

        return view('consolidate.account_statement', compact('reportData', 'account', 'types', 'filter', 'company'));
    }
}
