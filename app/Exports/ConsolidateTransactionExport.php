<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConsolidateTransactionExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $company;

    public function __construct($company)
    {
        $this->company = $company;
    }

    public function collection()
    {
        $data = [];
        $data = Transaction::when(!empty($request->company), function($query) use ($request) {
            $query->where('created_by', '=', $request->company);
        })->get();
        // dd($data);
        if (!empty($data)) {
            foreach ($data as $k => $Transaction) {
                $account  = Transaction::accounts($Transaction->account);
                unset($Transaction->created_by, $Transaction->updated_at, $Transaction->created_at,$Transaction->user_type, $Transaction->user_id,$Transaction->payment_id);
                $data[$k]["account"]        = $account;
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            "Transaction Id",
            "Account",
            "Type",
            "Amount",
            "Description",
            "Date",
            "Category",
        ];
    }
}
