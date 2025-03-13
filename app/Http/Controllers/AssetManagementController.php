<?php

namespace App\Http\Controllers;
use App\Models\AssetManagement;
use App\Models\AssetAssignment;
use App\Models\ProductType;
use App\Models\Employee;
use App\Models\AssetTransfer;
use App\Models\AssetHistory;
use Illuminate\Http\Request;


class AssetManagementController extends Controller
{
    //
    public function index(Request $request)
{
    if (\Auth::user()->can('manage assets management')) {
        // Fetch all assets created by the authenticated user
        $query = AssetManagement::where('created_by', \Auth::user()->id);

        // Filter assets based on the status
        if ($request->has('asset_status') && $request->asset_status !== '') {
            if ($request->asset_status == 'available') {
                $query->where('status', 0); // Assuming 0 means available
            } elseif ($request->asset_status == 'unavailable') {
                $query->where('status', 1); // Assuming 1 means unavailable
            }
        }

        // Get the filtered or full asset list
        $assets = $query->get();

        return view('asset_management.index', compact('assets'));
    } else {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
}

    public function create()
    {   
        if(\Auth::user()->can('create assets management'))
        {
            $productTypes = ProductType::where('created_by', \Auth::user()->creatorId())->pluck('name', 'id');
            return view('asset_management.create', compact('productTypes'));
        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

  
    public function store(Request $request)
{
    if(\Auth::user()->can('create assets management'))
    {
        // Validate the incoming request data
        $validator = \Validator::make($request->all(), [
            'product_name' => 'required',
            'product_description' => 'required',
            'product_configuration' => 'required',
            'asset_property_values' => 'nullable|array', 
        ]);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        // Create a new instance of AssetManagement
        $assetManagement = new AssetManagement();
        $assetManagement->product_type_id = $request->product_name;
        $assetManagement->product_description = $request->product_description;
        $assetManagement->product_configuration = $request->product_configuration;
        $assetManagement->created_by = \Auth::user()->id;

        // Store asset properties values as JSON
        if ($request->has('asset_property_values')) {
            $assetManagement->asset_properties_values = json_encode($request->asset_property_values);
        }

        $assetManagement->save();

        return redirect()->route('asset_management.index')->with('success', __('Asset added successfully.'));
    } else {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
    public function edit($id)
    {  
        if(\Auth::user()->can('edit assets management'))
        {
            $asset = AssetManagement::find($id);
            $productTypes = ProductType::pluck('name', 'id');
            return view('asset_management.edit', compact('asset','productTypes'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
      
    }
    
    public function update(Request $request,$id)
{    

    if (\Auth::user()->can('edit assets management')) {
        // Validate the request inputs
        $validator = \Validator::make($request->all(), [
            'product_description' => 'required|string|max:255',
            'product_configuration' => 'required|string|max:255',
            'asset_property_values' => 'nullable|array', // Validate asset property values
        ]);

        // If validation fails, redirect back with the first error message
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        // Find the asset by ID
        $asset = AssetManagement::find($id);
        if (!$asset) {
            return redirect()->route('asset_management.index')->with('error', __('Asset not found.'));
        }

        // Update asset details
        $asset->product_type_id = $request->product_name;
        $asset->product_description = $request->product_description;
        $asset->product_configuration = $request->product_configuration;

        // Update asset property values as JSON
        if ($request->has('asset_property_values')) {
            $asset->asset_properties_values = json_encode($request->asset_property_values);
        }

        $asset->save();

        return redirect()->route('asset_management.index')->with('success', __('Asset successfully updated.'));
    } else {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
}

    
public function destroy($id)
{
    if(\Auth::user()->can('delete assets management'))
    {
        $asset = AssetManagement::find($id);
        if($asset->created_by == \Auth::user()->creatorId())
        {
            $asset->delete();

            return redirect()->route('asset_management.index')->with('success', __('Assets successfully deleted.'));
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

public function assignAsset(Request $request, $id) 
{  
    if(\Auth::user()->can('assign assets management'))
    {
        $asset = AssetManagement::find($id);
        if (!$asset) {
            return redirect()->back()->with('error', __('Asset not found.'));
        }

        if ($asset->status == 1) {
            return redirect()->back()->with('error', __('This asset is already assigned to another employee. Please unassign it first.'));
        }

        $asset->status = 1;
        $asset->save();
        
        $history = new AssetHistory();
        $history->asset_id = $asset->id;    
        $history->employee_id = $request->employee_id;
        $history->action = 'assigned';
        $history->description = $request->assign_description;
        $history->action_date = $request->assigned_date;
        $history->created_by = auth()->user()->id;
        $history->save();

        return redirect()->back()->with('success', __('Asset assigned successfully.'));
    }else
    {
        return redirect()->back()->with('error', __('Permission denied.'));
    }
   
 }
    public function showAssignForm($id)
    {   
        if(\Auth::user()->can('assign assets management'))
        {
            $asset = AssetManagement::find($id);
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->whereHas('user', function ($query) {
                $query->where('type', 'Employee');
            })
            ->get();
            $latestHistory = AssetHistory::where('asset_id', $id)
                                        ->whereIn('action', ['assigned', 'transferred'])
                                        ->latest()->first();
                
            return view('asset_management.assign', compact('asset', 'employees', 'latestHistory'));
        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    public function showTransfer($id)
    {
        if(\Auth::user()->can('transfer assets management'))
        {
        $latestHistory = AssetHistory::where('asset_id', $id)
                                      ->whereIn('action', ['assigned', 'transferred'])
                                      ->latest()->first();

        
        // if (!$latestHistory) {
        // return redirect()->back()->with('error', __('No assignment history found for this asset.'));
        // }                             
        $employees = Employee::where('created_by', \Auth::user()->creatorId())
        ->whereHas('user', function ($query) {
            $query->where('type', 'Employee');
        })
        ->get();
    
        return view('asset_management.transfer', compact('latestHistory', 'employees'));
    }else
    {
        return redirect()->back()->with('error', __('Permission denied.'));
    }     
    }
    
    public function transfer(Request $request, $id)
    {
        if(\Auth::user()->can('transfer assets management'))
        {
        // Validate the input
        $validator = \Validator::make($request->all(), [
            'from_employee_id' => 'required',
            'to_employee_id' => 'required|different:from_employee_id',
            'transfer_description' => 'nullable|string',
        ],[
            'to_employee_id.different' => __('The "To Employee" must be different from the "From Employee".'), // Custom error message
        ]);
    
       
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }
    
       
        $latestHistory = AssetHistory::where('asset_id', $id)
                                     ->where(function($query) use ($request) {
                                         $query->where('employee_id', $request->from_employee_id)
                                     ->orWhere('to_employee_id', $request->from_employee_id);
                                     })
                                     ->latest()
                                     ->first();
    
       
        if (!$latestHistory || ($latestHistory->action !== 'assigned' && $latestHistory->action !== 'transferred')) {
            return redirect()->back()->with('error', __('This asset is not assigned to the selected employee.'));
        }
    
        // Log the transfer in the AssetHistory table
        $history = new AssetHistory();
        $history->asset_id = $id;
        $history->employee_id = $request->to_employee_id; 
        $history->from_employee_id = $request->from_employee_id;
        $history->to_employee_id = $request->to_employee_id;
        $history->action = 'transferred';
        $history->description = $request->transfer_description;
        $history->action_date = $request->transfer_date;
        $history->created_by = \Auth::user()->id;
        $history->save();
    
        return redirect()->route('asset_management.index')->with('success', __('Asset transferred successfully.'));
        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


        public function showUnassignForm($id)
        {   
            if(\Auth::user()->can('unassign assets management'))
            {
            $asset = AssetManagement::find($id);
            $latestHistory = AssetHistory::where('asset_id', $id)
                                        ->whereIn('action', ['assigned', 'transferred'])
                                        ->latest()->first();

            // if (!$latestHistory) {
            //     return redirect()->back()->with('error', __('No assigned asset found to unassign.'));
            // }
            $employees = Employee::where('created_by', \Auth::user()->creatorId())
            ->whereHas('user', function ($query) {
                $query->where('type', 'Employee');
            })
            ->get();
            return view('asset_management.unassign', compact('asset', 'latestHistory','employees'));
            }else
            {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        }
        public function unassignAsset(Request $request, $id)
        { 
            if(\Auth::user()->can('unassign assets management'))
            {
            $asset = AssetManagement::find($id);
            if (!$asset) {
                return redirect()->back()->with('error', __('Asset not found.'));
            }

            $asset->status = 0;
            $asset->save();

            
            $history = new AssetHistory();
            $history->asset_id = $asset->id;    
            $history->employee_id = $request->employee_id;
            $history->action = 'unassigned';
            $history->description = $request->unassign_description;
            $history->action_date = $request->assigned_date;
            $history->created_by = auth()->user()->id;
            $history->save();

            return redirect()->back()->with('success', __('Asset Unassigned successfully.'));
        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        }
        public function showHistory($id)
        {
            if(\Auth::user()->can('history assets management'))
            {
            // Fetch asset assignment history
            $historyRecords = AssetHistory::with(['employee', 'fromEmployee', 'toEmployee', 'createdBy'])
            ->where('asset_id', $id)
            ->get();
        
            return view('asset_management.history', compact('historyRecords'));
        }else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        }

        public function getAssetProperties(Request $request)
        {
            // Assuming you store asset properties in a JSON field in your ProductType model
            $productType = ProductType::find($request->product_type_id);
            $assetProperties = json_decode($productType->asset_properties, true); // Decode JSON asset properties
            return response()->json($assetProperties);
        }
        public function showProperties($id)
        {
            // Fetch the asset based on its ID
            $asset = AssetManagement::findOrFail($id);
            // Fetch the asset properties stored as JSON in the ProductType model
            $properties = $asset->asset_properties_values;  // Adjust based on your actual data structure
            
            // Return the properties view with the asset and its properties
            return view('asset_management.properties', compact('asset', 'properties'));
        }
        public function getAssetPropertiesForEdit($assetId = null, Request $request)
        {
            // Find the asset if editing
            $asset = $assetId ? AssetManagement::find($assetId) : null;
        
            // Get the product type from the request or asset
            $productType = ProductType::find($request->product_type_id ?? $asset->product_type_id);
        
            // Decode the asset properties stored in the ProductType
            $assetProperties = json_decode($productType->asset_properties, true);
        
            // Get the existing values from the asset, only if the asset exists
            $existingValues = $asset ? json_decode($asset->asset_properties_values, true) : [];
        
            // Return both properties and existing values
            return response()->json([
                'properties' => $assetProperties,
                'existingValues' => $existingValues,
            ]);
        }
}
