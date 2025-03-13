<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountParent;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\CustomField;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\Transaction;
use App\Models\TransactionLines;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{

    public function index()
    {
        if(\Auth::user()->can('create bank account'))
        {
            $accounts = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->with(['chartAccount'])->get();

            return view('bankAccount.index', compact('accounts'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create bank account'))
        {
            $eligble_code = array("1059");
            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
            ->where('parent', '=', 0)
            ->whereIn('code', $eligble_code)
            ->where('created_by', \Auth::user()->creatorId())->get()
            ->pluck('code_name', 'id');
            // $chartAccounts->prepend('Select Account', 0);

            $subAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id, chart_of_accounts.code, chart_of_accounts.name , chart_of_account_parents.account'));
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.code', 2620);
            $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();


            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();

            return view('bankAccount.create', compact('customFields','chartAccounts' , 'subAccounts'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create bank account'))
        {
            $rules = [
                'holder_name' => 'required',
                'bank_name' => 'required',
                'account_number' => 'required',
            ];
            
            if ($request->contact_number != null) {
                $rules['contact_number'] = ['regex:/^([0-9\s\-\+\(\)]*)$/'];
            }
            
            $validator = \Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }

            $bank_cash = ChartOfAccount::find($request->chart_account_id);

            $sub_type = ChartOfAccountSubType::where('name', 'Current Asset')->where('created_by', '=', \Auth::user()->creatorId())->first();
            $existingparentAccount = ChartOfAccountParent::where('account', $bank_cash->id)->where('created_by',\Auth::user()->creatorId())->first();
            if ($existingparentAccount) {
                $parentAccount = $existingparentAccount;
            } else {
                $parentAccount              = new ChartOfAccountParent();
                $parentAccount->name        = $bank_cash->name;
                $parentAccount->sub_type    = $sub_type->type;
                $parentAccount->type        = $sub_type->id;
                $parentAccount->account     = $bank_cash->id;
                $parentAccount->created_by  = \Auth::user()->creatorId();
                $parentAccount->save();
            }

            $chart_account              = new ChartOfAccount();
            $chart_account->name        = $request->holder_name." - ".$request->bank_name;
            $chart_account->code        = $request->code;
            $chart_account->type        = $sub_type->type;
            $chart_account->sub_type    = $sub_type->id;

            if($parentAccount)
            {
                $chart_account->parent      = $parentAccount->id;
            }
            $chart_account->description = "";
            $chart_account->is_enabled  = 1;
            $chart_account->created_by  = \Auth::user()->creatorId();
            $chart_account->save();

            $account                  = new BankAccount();
            $account->chart_account_id = $chart_account->id;
            $account->holder_name     = $request->holder_name;
            $account->bank_name       = $request->bank_name;
            $account->code            = $request->code;
            $account->account_number  = $request->account_number;
            $account->opening_balance = $request->opening_balance ? $request->opening_balance : 0;
            $account->contact_number  = $request->contact_number ? $request->contact_number : '-';
            $account->bank_address    = $request->bank_address ? $request->bank_address : '-';
            $account->created_by      = \Auth::user()->creatorId();
            $account->save();
            CustomField::saveData($account, $request->customField);

            $data = [
                'account_id' => $account->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $account->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $account->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];
            Utility::addTransactionLines($data, "new");

            $opening_balance = ChartOfAccount::where('code', 3020)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $opening_balance->id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $account->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $account->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];
            Utility::addTransactionLines($data, "new");

            return redirect()->route('bank-account.index')->with('success', __('Account successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('bank-account.index');
    }


    public function edit(BankAccount $bankAccount)
    {
        if(\Auth::user()->can('edit bank account'))
        {
            if($bankAccount->created_by == \Auth::user()->creatorId())
            {
                $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('parent', '=', 0)
                ->whereIn('code', [1059, 1058])
                    ->where('created_by', \Auth::user()->creatorId())->get()
                    ->pluck('code_name', 'id');
                $chartAccounts->prepend('Select Account', 0);

                $subAccounts = ChartOfAccount::select(\DB::raw('CONCAT(chart_of_accounts.code, " - ", chart_of_accounts.name) AS code_name, chart_of_accounts.id, chart_of_accounts.code, chart_of_accounts.name , chart_of_account_parents.account'));
                $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
                $subAccounts->where('chart_of_accounts.parent', '!=', 0);
                $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
                $subAccounts = $subAccounts->get()->toArray();

                $bankAccount->customField = CustomField::getData($bankAccount, 'account');
                $customFields             = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();

                return view('bankAccount.edit', compact('bankAccount', 'customFields','chartAccounts','subAccounts'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, BankAccount $bankAccount)
    {
        if(\Auth::user()->can('create bank account'))
        {

            $rules = [
                'holder_name' => 'required',
                'bank_name' => 'required',
                'account_number' => 'required',
            ];

            if ($request->contact_number != null) {
                $rules['contact_number'] = ['regex:/^([0-9\s\-\+\(\)]*)$/'];
            }

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }

            $chart_account              = ChartOfAccount::find($request->chart_account_id);
            $chart_account->name        = $request->holder_name." - ".$request->bank_name;
            $chart_account->code        = $request->code;
            $chart_account->save();

            $bankAccount->chart_account_id = $request->chart_account_id;
            $bankAccount->holder_name     = $request->holder_name;
            $bankAccount->bank_name       = $request->bank_name;
            $bankAccount->code            = $request->code;
            $bankAccount->account_number  = $request->account_number;
            $bankAccount->opening_balance = $request->opening_balance ? $request->opening_balance : 0;
            $bankAccount->contact_number  = $request->contact_number ? $request->contact_number : '-';
            $bankAccount->bank_address    = $request->bank_address ? $request->bank_address : '-';
            $bankAccount->created_by      = \Auth::user()->creatorId();
            $bankAccount->save();
            CustomField::saveData($bankAccount, $request->customField);

            TransactionLines::where('reference', "Bank Account")->where("reference_id", $bankAccount->id)->where('created_by', \Auth::user()->creatorId())->delete();

            $data = [
                'account_id' => $bankAccount->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $bankAccount->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $bankAccount->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];
            Utility::addTransactionLines($data, "new");

            $opening_balance = ChartOfAccount::where('code', 3020)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $opening_balance->id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $bankAccount->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $bankAccount->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];
            Utility::addTransactionLines($data, "new");

            return redirect()->route('bank-account.index')->with('success', __('Account successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(BankAccount $bankAccount)
    {
        if(\Auth::user()->can('delete bank account'))
        {
            if($bankAccount->created_by == \Auth::user()->creatorId())
            {
                $revenue        = Revenue::where('account_id', $bankAccount->id)->first();
                $invoicePayment = InvoicePayment::where('account_id', $bankAccount->id)->first();
                $transaction    = Transaction::where('account', $bankAccount->id)->first();
                $payment        = Payment::where('account_id', $bankAccount->id)->first();
                $billPayment    = BillPayment::first();

            TransactionLines::where('reference_id', $bankAccount->id)->where('reference', 'Bank Account')->delete();

                if(!empty($revenue) && !empty($invoicePayment) && !empty($transaction) && !empty($payment) && !empty($billPayment))
                {
                    return redirect()->route('bank-account.index')->with('error', __('Please delete related record of this account.'));
                }
                else
                {
                    $bankAccount->delete();

                    return redirect()->route('bank-account.index')->with('success', __('Account successfully deleted.'));
                }

            }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
