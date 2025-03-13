<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ExchangeHistory;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage exchange')) {
            $exchange_rate = ExchangeRate::where('created_by', '=', \Auth::user()->creatorId())->get();
            return view('exchange_rate.index')->with('exchange_rate', $exchange_rate);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create exchange')) {
            $currency = Currency::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('currency_code');
            $currency->prepend('Select Currency', '');
            return view('exchange_rate.create', compact('currency'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create exchange')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'from_currency' => 'required|max:5',
                    'to_currency' => 'required|max:5',
                    'exchange_rate' => 'required|numeric',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            if ($request->from_currency == $request->to_currency) {
                return redirect()->back()->with('error', "Choose different currency");
            }

            $check_exist = ExchangeRate::where("from_currency", $request->from_currency)->where("to_currency", $request->to_currency)->first();
            if ($check_exist) {
                return redirect()->back()->with('error', "Exchange Rate already exists");
            }
            $exchange_rate                   = new ExchangeRate();
            $exchange_rate->from_currency    = $request->from_currency;
            $exchange_rate->to_currency      = $request->to_currency;
            $exchange_rate->exchange_rate    = $request->exchange_rate;
            $exchange_rate->action_by        = \Auth::user()->id;
            $exchange_rate->created_by       = \Auth::user()->creatorId();
            $exchange_rate->save();

            return redirect()->route('exchange_rate.index')->with('success', __('Exchange Rate successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(ExchangeRate $exchange_rate)
    {
        if (\Auth::user()->can('edit exchange')) {
            if ($exchange_rate->created_by == \Auth::user()->creatorId()) {
                $currency = Currency::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('currency_code');
                return view('exchange_rate.edit', compact('currency', 'exchange_rate'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, ExchangeRate $exchange_rate)
    {
        if (\Auth::user()->can('edit exchange')) {
            if ($exchange_rate->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'from_currency' => 'required|max:5',
                        'to_currency' => 'required|max:5',
                        'exchange_rate' => 'required|numeric',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                if ($request->from_currency == $request->to_currency) {
                    return redirect()->back()->with('error', "Choose different currency");
                }

                $check_exist = ExchangeRate::where("from_currency", $request->from_currency)->where("to_currency", $request->to_currency)->where('id', '!=', $exchange_rate->id)->first();
                if ($check_exist) {
                    return redirect()->back()->with('error', "Exchange Rate already exists");
                }

                $exchange_history = new ExchangeHistory();
                $exchange_history->from_currency = $request->from_currency;
                $exchange_history->to_currency = $request->to_currency;
                $exchange_history->old_rate = $exchange_rate->exchange_rate;
                $exchange_history->new_rate = $request->exchange_rate;
                $exchange_history->action_by = \Auth::user()->id;
                $exchange_history->save();

                $exchange_rate->from_currency    = $request->from_currency;
                $exchange_rate->to_currency      = $request->to_currency;
                $exchange_rate->exchange_rate    = $request->exchange_rate;
                $exchange_rate->action_by        = \Auth::user()->id;
                $exchange_rate->save();

                return redirect()->route('exchange_rate.index')->with('success', __('Exchange Rate successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(ExchangeRate $exchange_rate)
    {
        if (\Auth::user()->can('delete exchange')) {
            if ($exchange_rate->created_by == \Auth::user()->creatorId()) {
                // $proposalData = ProposalProduct::whereRaw("find_in_set('$currency->id',tax)")->first();
                // $billData     = BillProduct::whereRaw("find_in_set('$currency->id',tax)")->first();
                // $invoiceData  = InvoiceProduct::whereRaw("find_in_set('$currency->id',tax)")->first();

                // if (!empty($proposalData) || !empty($billData) || !empty($invoiceData)) {
                //     return redirect()->back()->with('error', __('this currency already assign to proposal or bill or invoice so please move or remove this currency data.'));
                // }

                $exchange_history = new ExchangeHistory();
                $exchange_history->from_currency = $exchange_rate->from_currency;
                $exchange_history->to_currency = $exchange_rate->to_currency;
                $exchange_history->old_rate = $exchange_rate->exchange_rate;
                $exchange_history->new_rate = -100;
                $exchange_history->action_by = \Auth::user()->id;
                $exchange_history->save();

                $exchange_rate->delete();

                return redirect()->route('exchange_rate.index')->with('success', __('Exchange Rate successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function fetch_exchange_rate(Request $request)
    {
        $currency_code = $request->currency_code;
        $exchange_rate = 1;
        if ($currency_code != \Auth::user()->currencyCode()) {
            $exchange_rate = ExchangeRate::where("from_currency", $currency_code)->where("to_currency", \Auth::user()->currencyCode())->first();
            if (!$exchange_rate) {
                return array("status" => 0, "message" => __("No conversion rate found"));
            }
            $exchange_rate = $exchange_rate->exchange_rate;
        }
        return array("status" => 1, "exchange_rate" => $exchange_rate);
    }
}
