<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Models\TransactionLines;
use App\Models\Utility;
use Illuminate\Http\Request;

class BankTransferController extends Controller
{

    public function index(Request $request)
    {

        if(\Auth::user()->can('manage bank transfer'))
        {
            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $query = BankTransfer::with('createdUser')->where('created_by', '=', \Auth::user()->creatorId());

            if(count(explode('to', $request->date)) > 1)
            {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            }
            elseif(!empty($request->date))
            {
                $date_range = [$request->date , $request->date];
                $query->whereBetween('date', $date_range);
            }


            if(!empty($request->f_account))
            {
                $query->where('from_account', '=', $request->f_account);
            }
            if(!empty($request->t_account))
            {
                $query->where('to_account', '=', $request->t_account);
            }
            $transfers = $query->with(['fromBankAccount','toBankAccount'])->get();

            return view('bank-transfer.index', compact('transfers', 'account'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create bank transfer'))
        {
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bank-transfer.create', compact('bankAccount'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if(\Auth::user()->can('create bank transfer'))
        {
            $validator = \Validator::make(
                $request->all(), [
                        'from_account' => 'required|numeric',
                        'to_account' => 'required|numeric',
                        'amount' => 'required|numeric',
                        'date' => 'required',
                    ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $transfer                 = new BankTransfer();
            $transfer->from_account   = $request->from_account;
            $transfer->to_account     = $request->to_account;
            $transfer->amount         = $request->amount;
            $transfer->date           = $request->date;
            $transfer->payment_method = 0;
            $transfer->reference      = $request->reference;
            $transfer->description    = $request->description;
            $transfer->created_by     = \Auth::user()->creatorId();
            $transfer->created_user     = \Auth::user()->id;
            $transfer->save();

            Utility::bankAccountBalance($request->from_account, $request->amount, 'debit');

            Utility::bankAccountBalance($request->to_account, $request->amount, 'credit');

            $from_account_coa = BankAccount::where("id", $request->from_account)->where('created_by', \Auth::user()->creatorId())->first();
            $to_account_coa = BankAccount::where("id", $request->to_account)->where('created_by', \Auth::user()->creatorId())->first();

            // if($from_account_coa->holder_name == "cash" || $from_account_coa->holder_name == "Cash" || $to_account_coa->holder_name == "cash" || $to_account_coa->holder_name == "Cash")
            // {
                $data = [
                    'account_id' => $from_account_coa->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $request->amount,
                    'reference' => 'Bank Transaction',
                    'reference_id' => $transfer->id,
                    'reference_sub_id' => 0,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");

                $data = [
                    'account_id' => $to_account_coa->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->amount,
                    'reference' => 'Bank Transaction',
                    'reference_id' => $transfer->id,
                    'reference_sub_id' => 0,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");
            // }

            return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show()
    {
        return redirect()->route('bank-transfer.index');
    }

    public function edit(BankTransfer $transfer,$id)
    {
        if(\Auth::user()->can('edit bank transfer'))
        {
            $transfer = BankTransfer::where('id',$id)->first();
            $bankAccount = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bank-transfer.edit', compact('bankAccount', 'transfer'));
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, BankTransfer $transfer,$id)
    {
        if(\Auth::user()->can('edit bank transfer'))
        {
            $transfer = BankTransfer::find($id);
            $validator = \Validator::make(
                $request->all(), [
                            'from_account' => 'required|numeric',
                            'to_account' => 'required|numeric',
                            'amount' => 'required|numeric',
                            'date' => 'required',
                        ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            Utility::bankAccountBalance($transfer->from_account, $transfer->amount, 'credit');
            Utility::bankAccountBalance($transfer->to_account, $transfer->amount, 'debit');

            $transfer->from_account   = $request->from_account;
            $transfer->to_account     = $request->to_account;
            $transfer->amount         = $request->amount;
            $transfer->date           = $request->date;
            $transfer->payment_method = 0;
            $transfer->reference      = $request->reference;
            $transfer->description    = $request->description;
            $transfer->created_user     = \Auth::user()->id;
            $transfer->save();

            TransactionLines::where("reference", "Bank Transaction")->where("reference_id", $transfer->id)->where('created_by', \Auth::user()->creatorId())->delete();

            $from_account_coa = BankAccount::where("id", $request->from_account)->where('created_by', \Auth::user()->creatorId())->first();
            $to_account_coa = BankAccount::where("id", $request->to_account)->where('created_by', \Auth::user()->creatorId())->first();

            Utility::bankAccountBalance($request->from_account, $request->amount, 'debit');
            Utility::bankAccountBalance($request->to_account, $request->amount, 'credit');

            // if($from_account_coa->holder_name == "cash" || $from_account_coa->holder_name == "Cash" || $to_account_coa->holder_name == "cash" || $to_account_coa->holder_name == "Cash")
            // {
                $data = [
                    'account_id' => $from_account_coa->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $request->amount,
                    'reference' => 'Bank Transaction',
                    'reference_id' => $transfer->id,
                    'reference_sub_id' => 0,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");

                $data = [
                    'account_id' => $to_account_coa->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->amount,
                    'reference' => 'Bank Transaction',
                    'reference_id' => $transfer->id,
                    'reference_sub_id' => 0,
                    'date' => $request->date,
                ];
                Utility::addTransactionLines($data, "new");
            // }

            return redirect()->route('bank-transfer.index')->with('success', __('Amount successfully transfer updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(BankTransfer $BankTransfer)
    {

        if(\Auth::user()->can('delete bank transfer'))
        {
            if($BankTransfer->created_by == \Auth::user()->creatorId())
            {
                TransactionLines::where("reference", "Bank Transaction")->where("reference_id", $BankTransfer->id)->where('created_by', \Auth::user()->creatorId())->delete();
                $BankTransfer->delete();

                Utility::bankAccountBalance($BankTransfer->from_account, $BankTransfer->amount, 'credit');
                Utility::bankAccountBalance($BankTransfer->to_account, $BankTransfer->amount, 'debit');

                return redirect()->route('bank-transfer.index')->with('success', __('Amount transfer successfully deleted.'));
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
