<?php

namespace App\Http\Controllers;

use App\Exports\CadExport;
use App\Exports\NdtExport;
use App\Models\Workorder;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    public function cadExport($id)
    {

        $wo = Workorder::with('unit')->find($id);

        $data = [
            'wo_number' => $wo->number,
            'unit_number' => $wo->unit->partnumber,
            'unit_name' => $wo->unit->description,
            'serial_number' => $wo->serial_number,
            'vendor' => 'Micro Custom',
        ];

        $file_name = 'cad_w' . $data['wo_number'] . '.xlsx';

        return Excel::download(new CadExport($data), $file_name);

    }

    public function ndtExport($id)
    {

        $wo = Workorder::with('unit')->find($id);

        $data = [
            'wo_number' => $wo->number,
            'unit_number' => $wo->unit->partnumber,
            'unit_name' => $wo->unit->description,
            'serial_number' => $wo->serial_number,
            'vendor' => 'Skyservice',
        ];

        $file_name = 'ndt_w' . $data['wo_number'] . '.xlsx';


        return Excel::download(new NdtExport($data), $file_name);

    }
}
