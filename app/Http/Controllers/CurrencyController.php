<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index()
    {
        if (\Auth::user()->can('manage currency')) {
            $currency = Currency::where('created_by', '=', \Auth::user()->creatorId())->get();
            return view('currency.index')->with('currency', $currency);
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create currency')) {
            return view('currency.create');
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create currency')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'currency_code' => 'required|max:5',
                    'currency_symbol' => 'required|max:5',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            $currency                   = new Currency();
            $currency->currency_code    = $request->currency_code;
            $currency->currency_symbol  = $request->currency_symbol;
            $currency->created_by       = \Auth::user()->creatorId();
            $currency->action_by        = \Auth::user()->id;
            $currency->save();

            return redirect()->route('currency.index')->with('success', __('Currency successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Currency $currency)
    {
        if (\Auth::user()->can('edit currency')) {
            if ($currency->created_by == \Auth::user()->creatorId()) {
                return view('currency.edit', compact('currency'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, Currency $currency)
    {
        if (\Auth::user()->can('edit currency')) {
            if ($currency->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'currency_code' => 'required|max:5',
                        'currency_symbol' => 'required|max:5',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }

                $currency->currency_code    = $request->currency_code;
                $currency->currency_symbol  = $request->currency_symbol;
                $currency->action_by        = \Auth::user()->id;
                $currency->save();

                return redirect()->route('currency.index')->with('success', __('Currency successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Currency $currency)
    {
        if (\Auth::user()->can('delete currency')) {
            if ($currency->created_by == \Auth::user()->creatorId()) {
                // $proposalData = ProposalProduct::whereRaw("find_in_set('$currency->id',tax)")->first();
                // $billData     = BillProduct::whereRaw("find_in_set('$currency->id',tax)")->first();
                // $invoiceData  = InvoiceProduct::whereRaw("find_in_set('$currency->id',tax)")->first();

                // if (!empty($proposalData) || !empty($billData) || !empty($invoiceData)) {
                //     return redirect()->back()->with('error', __('this currency already assign to proposal or bill or invoice so please move or remove this currency data.'));
                // }

                $currency->delete();

                return redirect()->route('currency.index')->with('success', __('Currency successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
