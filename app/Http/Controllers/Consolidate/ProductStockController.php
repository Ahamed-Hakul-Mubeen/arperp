<?php

namespace App\Http\Controllers\Consolidate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockReport;
use App\Models\User;

class ProductStockController extends Controller
{
    public function index(Request $request){
        $user = \Auth::user();
        $company = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->with(['currentPlan'])->get()->pluck('name','id')->toArray();
        $stocks = StockReport::with(['product'])->when(!empty($request->company), function ($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get();
        return view('consolidate.product_stock_report', compact('stocks', 'company'));
    }
}
