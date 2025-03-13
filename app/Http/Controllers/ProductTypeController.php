<?php

namespace App\Http\Controllers;
use App\Models\ProductType;

use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    //
    public function index()
    {
       
        if(\Auth::user()->can('manage assets type'))
        {
            $productTypes = ProductType::where('created_by', '=', \Auth::user()->creatorId())->get();
            return view('product_type.index', compact('productTypes'));
        }
            else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
       
    }

    public function create()
    {
        
        
       
        if(\Auth::user()->can('create assets type'))
        {
            return view('product_type.create');
        }
        else
        {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
        
       
        
        
    }
    public function store(Request $request)
    {
        if(\Auth::user()->can('create assets type')) {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'asset_property.*' => 'nullable|string', // Validation for dynamic text areas
            ]);
    
            if($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }
    
            $productType = new ProductType();
            $productType->name = $request->name;
            $productType->asset_properties = json_encode($request->asset_property); // Encode asset_properties to store in DB
            $productType->created_by = \Auth::user()->creatorId();
            $productType->save();
    
            return redirect()->route('product_type.index')->with('success', __('Assets Type successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function edit(ProductType $productType)
    {   
        if(\Auth::user()->can('edit assets type'))
        {
      
            if($productType->created_by == \Auth::user()->creatorId())
            {
                return view('product_type.edit', compact('productType'));
            }
            else
            {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        }
        else
        {
            return redirect()->back()->with(['error' => __('Permission denied.')], 401);
        }
      
    }
    public function update(Request $request, ProductType $productType)
{
    if(\Auth::user()->can('edit assets type'))
    {
        if($productType->created_by == \Auth::user()->creatorId())
        {
            $validator = \Validator::make($request->all(), [
                'name' => 'required',
                'asset_property.*' => 'nullable|string', // Add validation for asset properties
            ]);

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();
                return redirect()->back()->with('error', $messages->first());
            }

            // Update asset properties
            $productType->name = $request->name;
            $productType->asset_properties = json_encode($request->asset_property); // Encode updated asset properties as JSON
            $productType->save();

            return redirect()->route('product_type.index')->with('success', __('Assets Type successfully updated.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    else
    {
        return redirect()->back()->with(['error' => __('Permission denied.')], 401);
    }
}

    public function destroy(ProductType $productType)
    {
        if (\Auth::user()->can('delete assets type')) {
            if ($productType->created_by == \Auth::user()->creatorId()) {
                
               
                $assignedAssets = \DB::table('asset_management')
                    ->where('product_type_id', $productType->id)
                    ->where('status', 1) 
                    ->exists();
    
                if ($assignedAssets) {
                    
                    return redirect()->back()->with('error', __('This product is assigned to an employee and cannot be deleted.'));
                }
    
               
                \DB::table('asset_management')->where('product_type_id', $productType->id)->delete();
                $productType->delete();
    
                return redirect()->route('product_type.index')->with('success', __('Assets Type successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'), 401);
        }
       
    }
}
