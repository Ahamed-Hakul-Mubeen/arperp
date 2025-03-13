<?php

namespace App\Exports;

use App\Models\PreOrder;
use App\Models\ProductServiceCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;


class PreOrderExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = PreOrder::where('created_by', \Auth::user()->creatorId())->get();

        foreach($data as $k => $proposal )
        {
            unset( $proposal->created_by,$proposal->vender_id,$proposal->converted_bill_id,$proposal->is_convert,$proposal->discount_apply,$proposal->created_at,$proposal->updated_at);
            $data[$k]["pre_order_id"] = \Auth::user()->preOrderNumberFormat($proposal->pre_order_id);
            $data[$k]["category_id"] = ProductServiceCategory::where('type', 'income')->first()->name;
            $data[$k]["status"]       = PreOrder::$statues[$proposal->status];
        }

        return $data;
    }
    public function headings(): array
    {
        return [
            "ID",
            "Pre Order No",
            "Issue Date",
            "Send Date",
            "Category",
            "Status",

        ];
    }
}
