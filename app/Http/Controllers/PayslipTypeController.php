<?php

namespace App\Http\Controllers;

use App\Models\PayslipType;
use Illuminate\Http\Request;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;
use File;

class PayslipTypeController extends Controller
{
    public function index()
    {
        if(\Auth::user()->can('manage payslip type'))
        {
            $paysliptypes = PayslipType::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('paysliptype.index', compact('paysliptypes'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(\Auth::user()->can('create payslip type'))
        {
            return view('paysliptype.create');
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {  
        if(\Auth::user()->can('create payslip type'))
        {
            $validator = \Validator::make(
                $request->all(), [
                    'name' => 'required|max:40',
                    'digital_signature' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
                    ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();
                    return redirect()->back()->with('error', $messages->first());
                }
               

    $paysliptype = new PayslipType();
    $paysliptype->name = $request->name;
    $paysliptype->created_by = \Auth::user()->creatorId();

    if($request->hasFile('digital_signature'))
    {
        $image_size = $request->file('digital_signature')->getSize();
        $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
        
        if($result == 1)
        {
            $filenameWithExt = $request->file('digital_signature')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('digital_signature')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;
            $dir = 'uploads/payslip/digital_signatures/';
            
            // Delete old file if exists
            if (\File::exists(public_path($dir . $fileNameToStore))) {
                \File::delete(public_path($dir . $fileNameToStore));
            }
            
            // Upload file
            $path = Utility::upload_file($request, 'digital_signature', $fileNameToStore, $dir, []);
            $paysliptype->digital_signature = $fileNameToStore;
        }
    }
          
            $paysliptype->save();

            return redirect()->route('paysliptype.index')->with('success', __('PayslipType successfully created.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

    }

    public function show(PayslipType $paysliptype)
    {
        return redirect()->route('paysliptype.index');
    }

    public function edit(PayslipType $paysliptype)
    {
        if(\Auth::user()->can('edit payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {

                return view('paysliptype.edit', compact('paysliptype'));
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

    public function update(Request $request, PayslipType $paysliptype)
    {   
       
        if(\Auth::user()->can('edit payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {
                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:40',
                                       'digital_signature' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
                                       
                                   ]
                );

                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $paysliptype->name = $request->name;
                
                if ($request->hasFile('digital_signature'))
                {
                    $image_size = $request->file('digital_signature')->getSize();
                    $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    
                    if($result == 1)
                    {
                        $filenameWithExt = $request->file('digital_signature')->getClientOriginalName();
                        $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                        $extension = $request->file('digital_signature')->getClientOriginalExtension();
                        $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                        $dir = 'uploads/payslip/digital_signatures/';
                        
                        // Delete old file if exists
                        if (\File::exists(public_path($dir . $paysliptype->digital_signature))) {
                            \File::delete(public_path($dir . $paysliptype->digital_signature));
                        }
                        
                        // Upload new file
                        $path = Utility::upload_file($request, 'digital_signature', $fileNameToStore, $dir, []);
                        $paysliptype->digital_signature = $fileNameToStore;
                    }
                }
                
                $paysliptype->save();

                return redirect()->route('paysliptype.index')->with('success', __('PayslipType successfully updated.'));
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

    public function destroy(PayslipType $paysliptype)
    {
        if(\Auth::user()->can('delete payslip type'))
        {
            if($paysliptype->created_by == \Auth::user()->creatorId())
            {
                $paysliptype->delete();

                return redirect()->route('paysliptype.index')->with('success', __('PayslipType successfully deleted.'));
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
