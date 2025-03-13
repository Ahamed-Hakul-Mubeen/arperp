<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreOrder extends Model
{
    use HasFactory;
    
    public static $statues = [
        'Draft',
        'Open',
        'Accepted',
        'Declined',
        'Close',
        'created_user',
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }
    public function items()
    {
        return $this->hasMany('App\Models\PreOrderProduct', 'pre_order_id', 'id');
    }
    public function getTotalTax()
    {
        $taxData = Utility::getTaxData();
        $totalTax = 0;
        foreach($this->items as $product)
        {
            // $taxes = Utility::totalTaxRate($product->tax);

            $taxArr = explode(',', $product->tax);
            $taxes = 0;
            foreach ($taxArr as $tax) {
                // $tax = TaxRate::find($tax);
                $taxes += !empty($taxData[$tax]['rate']) ? $taxData[$tax]['rate'] : 0;
            }

            $totalTax += ($taxes / 100) * (($product->price * $product->quantity) - $product->discount);
        }

        return $totalTax;
    }

    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach($this->items as $product)
        {
            $totalDiscount += $product->discount;
        }

        return $totalDiscount;
    }

    public function getTotal()
    {
        return ($this->getSubTotal() -$this->getTotalDiscount()) + $this->getTotalTax();
    }

    public static function change_status($pre_order_id, $status)
    {
        $pre_order         = PreOrder::find($pre_order_id);
        $pre_order->status = $status;
        $pre_order->update();
    }
    public function getSubTotal()
    {
        $subTotal = 0;
        foreach($this->items as $product)
        {
            $subTotal += ($product->price * $product->quantity);
        }

        return $subTotal;
    }
    
    public function vender()
    {
        return $this->hasOne('App\Models\Vender', 'id', 'vender_id');
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }
}
