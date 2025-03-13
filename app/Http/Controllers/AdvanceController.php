<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    public function index(Request $request) {
        if(\Auth::user()->can('manage advance'))
        {
            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $query = Advance::with('createdUser')->where('created_by', '=', \Auth::user()->creatorId());

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

            if(!empty($request->customer))
            {
                $query->where('customer_id', '=', $request->customer);
            }
            if(!empty($request->account))
            {
                $query->where('account_id', '=', $request->account);
            }

            if(!empty($request->payment))
            {
                $query->where('payment_method', '=', $request->payment);
            }

            $advances = $query->with(['bankAccount','customer'])->get();

            return view('advance.index', compact('advances', 'customer', 'account'));

         }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function create()
    {

        if(\Auth::user()->can('create advance'))
        {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = Currency::select('currency_code', 'currency_symbol')->where('created_by', \Auth::user()->creatorId())->get();
            return view('advance.create', compact('customers', 'accounts', 'currency'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($id) {

        $query = Advance::where("id", $id)->where('created_by', '=', \Auth::user()->creatorId());
        $advance = $query->with(['bankAccount','customer'])->first();
        return view('advance.show', compact('advance'));
    }

    public function store(Request $request) {
        if(\Auth::user()->can('create advance'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $advance                 = new Advance();
            $advance->advance_id     = $this->advanceNumber();
            $advance->date           = $request->date;
            $advance->amount         = $request->amount;
            $advance->balance        = $request->amount;
            $advance->account_id     = $request->account_id;
            $advance->customer_id    = $request->customer_id;
            $advance->currency_code  = $request->currency_code;
            $advance->currency_symbol = $request->currency_symbol;
            $advance->exchange_rate  = $request->exchange_rate;
            $advance->payment_method = 0;
            $advance->reference      = $request->reference;
            $advance->description    = $request->description;
            if(!empty($request->add_receipt))
            {
                //storage limit
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if($result==1)
                {
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $advance->add_receipt = $fileName;
                    $dir = 'uploads/advance';
                    $url = '';
                    $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                    if ($path['flag'] == 0) {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }

            $advance->created_by     = \Auth::user()->creatorId();
            $advance->created_user     = \Auth::user()->id;
            $advance->save();

            $customer         = Customer::where('id', $request->customer_id)->first();
            if(!empty($customer))
            {
                Utility::userBalance('customer', $customer->id, $advance->amount * $request->exchange_rate, 'debit');
            }
            Utility::bankAccountBalance($request->account_id, $advance->amount * $request->exchange_rate, 'credit');

            $accountId = BankAccount::find($advance->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $advance->amount * $request->exchange_rate,
                'reference' => 'Advance',
                'reference_id' => $advance->id,
                'reference_sub_id' => 0,
                'date' => $advance->date,
            ];
            Utility::addTransactionLines($data, "new");

            // Unearned Advance
            $unearned = ChartOfAccount::where('code', 2040)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $unearned->id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $advance->amount * $request->exchange_rate,
                'reference' => 'Advance',
                'reference_id' => $advance->id,
                'reference_sub_id' => 0,
                'date' => $advance->date,
            ];
            Utility::addTransactionLines($data, "new");

            return redirect()->route('advance.index')->with('success', __('Advance successfully created'). ((isset($result) && $result!=1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));

        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Advance $advance)
    {
        if(\Auth::user()->can('edit advance'))
        {
            $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('--', 0);
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $currency = Currency::select('currency_code', 'currency_symbol')->where('created_by', \Auth::user()->creatorId())->get();
            return view('advance.edit', compact('customers', 'accounts', 'advance', 'currency'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function update(Request $request, Advance $advance)
    {
        if(\Auth::user()->can('edit advance'))
        {
            $validator = \Validator::make(
                $request->all(), [
                        'date' => 'required',
                        'amount' => 'required',
                        'account_id' => 'required',
                        ]
                );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $customer = Customer::where('id', $request->customer_id)->first();
            if(!empty($customer))
            {
                Utility::userBalance('customer', $advance->customer_id, $advance->amount * $request->exchange_rate, 'credit');
            }

            Utility::bankAccountBalance($advance->account_id, $advance->amount * $request->exchange_rate, 'debit');


            if(!empty($customer))
            {
                Utility::userBalance('customer', $customer->id, $request->amount * $request->exchange_rate, 'debit');
            }

            Utility::bankAccountBalance($request->account_id, $request->amount * $request->exchange_rate, 'credit');

            $new_balance = ($request->amount - ($advance->amount - $advance->balance));

            $advance->date           = $request->date;
            $advance->amount         = $request->amount;
            $advance->balance        = $new_balance;
            $advance->account_id     = $request->account_id;
            $advance->customer_id    = $request->customer_id;
            $advance->currency_code  = $request->currency_code;
            $advance->currency_symbol = $request->currency_symbol;
            $advance->exchange_rate  = $request->exchange_rate;
            $advance->payment_method = 0;
            $advance->reference      = $request->reference;
            $advance->description    = $request->description;

            if(!empty($request->add_receipt))
            {
                //storage limit
                $file_path = '/uploads/advance/'.$advance->add_receipt;
                $image_size = $request->file('add_receipt')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if($result==1)
                {
                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    $path = storage_path('uploads/advance/' . $advance->add_receipt);

                    if(file_exists($path))
                    {
                        \File::delete($path);
                    }
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    $advance->add_receipt = $fileName;
                    $dir        = 'uploads/advance';
                    $url = '';
                    $path = Utility::upload_file($request,'add_receipt',$fileName,$dir,[]);
                    if($path['flag']==0){
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                }
            }
            $advance->created_user     = \Auth::user()->id;
            $advance->save();

            TransactionLines::where("reference", "Advance")->where("reference_id", $advance->id)->where('created_by', \Auth::user()->creatorId())->delete();

            $accountId = BankAccount::find($advance->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $advance->amount * $request->exchange_rate,
                'reference' => 'Advance',
                'reference_id' => $advance->id,
                'reference_sub_id' => 0,
                'date' => $advance->date,
            ];
            Utility::addTransactionLines($data, "new");

            // Unearned Advance
            $unearned = ChartOfAccount::where('code', 2040)->where('created_by', \Auth::user()->creatorId())->first();
            $data = [
                'account_id' => $unearned->id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $advance->amount * $request->exchange_rate,
                'reference' => 'Advance',
                'reference_id' => $advance->id,
                'reference_sub_id' => 0,
                'date' => $advance->date,
            ];
            Utility::addTransactionLines($data, "new");

            return redirect()->route('advance.index')->with('success', __('Advance Updated Successfully'). ((isset($result) && $result!=1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));

        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function destroy(Advance $advance)
    {
        if(\Auth::user()->can('delete advance'))
        {
            if($advance->created_by == \Auth::user()->creatorId())
            {
                if(!empty($advance->add_receipt))
                {
                    //storage limit
                    $file_path = '/uploads/advance/'.$advance->add_receipt;
                    $result = Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);

                }
                TransactionLines::where('reference_id',$advance->id)->where('reference','Advance')->delete();
                $advance->delete();

                if($advance->customer_id != 0)
                {
                    Utility::userBalance('customer', $advance->customer_id, $advance->amount * $advance->exchange_rate, 'credit');
                }

                Utility::bankAccountBalance($advance->account_id, $advance->amount * $advance->exchange_rate, 'debit');

                return redirect()->route('advance.index')->with('success', __('Advance successfully deleted.'));
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
    function advanceNumber()
    {
        $latest = Advance::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if(!$latest)
        {
            return 1;
        }

        return $latest->advance_id + 1;
    }
}
