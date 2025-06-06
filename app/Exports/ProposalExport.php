<?php

namespace App\Exports;

use App\Models\Proposal;
use App\Models\ProductServiceCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProposalExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = Proposal::where('created_by', \Auth::user()->creatorId())->get();

        foreach($data as $k => $proposal )
        {
            unset( $proposal->created_by,$proposal->customer_id,$proposal->converted_invoice_id,$proposal->is_convert,$proposal->discount_apply,$proposal->created_at,$proposal->updated_at);
            $data[$k]["proposal_id"] = \Auth::user()->proposalNumberFormat($proposal->proposal_id);
//            $data[$k]["customer_id"] = \Auth::user()->customerNumberFormat($proposal->customer_id);
            $data[$k]["category_id"] = ProductServiceCategory::where('type', 'income')->first()->name;
            $data[$k]["status"]       = Proposal::$statues[$proposal->status];

        }

        return $data;
    }

    public function headings(): array
    {
        return [
            "ID",
            "Proposal No",
            "Issue Date",
            "Send Date",
            "Category",
            "Status",

        ];
    }
}
