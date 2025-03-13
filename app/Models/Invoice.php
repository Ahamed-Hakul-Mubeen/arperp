<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_id',
        'customer_id',
        'issue_date',
        'due_date',
        'ref_number',
        'status',
        'category_id',
        'created_by',
        'created_user'
    ];

    public static $statues = [
        'Draft',
        'Sent',
        'Unpaid',
        'Partialy Paid',
        'Paid',
    ];


    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\InvoiceProduct', 'invoice_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\InvoicePayment', 'invoice_id', 'id');
    }
    public function bankPayments()
    {
        return $this->hasMany('App\Models\InvoiceBankTransfer', 'invoice_id', 'id')->where('status','!=','Approved');
    }
    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }




    // private static $getTotal = NULL;
    // public static function getTotal(){
    //     if(self::$getTotal == null){
    //         $Invoice = new Invoice();
    //         self::$getTotal = $Invoice->invoiceTotal();
    //     }
    //     return self::$getTotal;
    // }

    public function getTotal($conversion = false)
    {
        // dd($this->getSubTotal($conversion), $this->getTotalTax($conversion))
        return ($this->getSubTotal($conversion) -$this->getTotalDiscount()) + $this->getTotalTax($conversion);
    }



    public function getSubTotal($conversion = false)
    {
        $subTotal = 0;
        foreach($this->items as $product)
        {
            $exchange_rate = $this->getConversionRate($product->invoice_id);
            if($conversion)
            {
                $subTotal += ($product->price * $product->quantity * $exchange_rate);
            }else{
                $subTotal += ($product->price * $product->quantity);
            }
            
        }

        return $subTotal;
    }

    public function getConversionRate($invoice_id)
    {
        $exchange_rate = Invoice::where('id', $invoice_id)->value('exchange_rate');
        return $exchange_rate ? $exchange_rate : 1;
    }

    // public function getTotalTax()
    // {
    //     $totalTax = 0;
    //     foreach($this->items as $product)
    //     {
    //         $taxes = Utility::totalTaxRate($product->tax);


    //         $totalTax += ($taxes / 100) * ($product->price * $product->quantity - $product->discount) ;
    //     }

    //     return $totalTax;
    // }

    public function getTotalTax($conversion = false)
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
            $exchange_rate = $this->getConversionRate($product->invoice_id);
            if($conversion)
            {
                $totalTax += ($taxes / 100) * (($product->price * $product->quantity * $exchange_rate) - $product->discount);
            }else{
                $totalTax += ($taxes / 100) * (($product->price * $product->quantity) - $product->discount);
            }
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

    public function getDue($conversion = false)
    {
        $due = 0;
        foreach($this->payments as $payment)
        {
            $exchange_rate = $this->getConversionRate($payment->invoice_id);
            if($conversion)
            {
                $due += $payment->amount * $exchange_rate;
            }else{
                $due += $payment->amount;
            }
        }

        return ($this->getTotal($conversion) - $due) - $this->invoiceTotalCreditNote();
    }

    public static function change_status($invoice_id, $status)
    {

        $invoice         = Invoice::find($invoice_id);
        $invoice->status = $status;
        $invoice->update();
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function creditNote()
    {

        return $this->hasMany('App\Models\CreditNote', 'invoice', 'id');
    }

    public function invoiceTotalCreditNote()
    {
        return $this->creditNote->sum('amount');
    }

    public function lastPayments()
    {
        return $this->hasOne('App\Models\InvoicePayment', 'id', 'invoice_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public function products()
    {
        return $this->hasMany(InvoiceProduct::class);
    }
    public function createdUser()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_user');
    }

}
