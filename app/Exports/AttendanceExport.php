<?php

namespace App\Exports;
use App\Models\AttendanceEmployee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $attendanceData;

    public function __construct($attendanceData)
    {
        $this->attendanceData = $attendanceData;
    }

    public function collection()
    {
       

        $data = $this->attendanceData->map(function ($attendance) {
            return [
                'Employee' => $attendance->employee->name, // Assuming you have a relationship to fetch employee name
                'Date' => $attendance->date,
                'Status' => $attendance->status,
                'Clock In' => $attendance->clock_in,
                'Clock Out' => $attendance->clock_out,
                'Late' => $attendance->late,
                'Early Leaving' => $attendance->early_leaving,
                'Overtime' => $attendance->overtime,
                'Break Time' => $attendance->total_break_duration,
            ];
        });

        return $data;
    }

    public function headings(): array
    {   
        
        return [
            'Employee',
            'Date',
            'Status',
            'Clock In',
            'Clock Out',
            'Late',
            'Early Leaving',
            'Overtime',
            'Break Time'
        ];
    }
    
}
