<?php

namespace Modules\AdmmDocuments\Http\Controllers;

use App\Http\Controllers\Controller;

class SimImportController extends Controller
{
    public function index()
    {
        return view('admmdocuments::sim-import.index');
    }

        public function gpsImportIndex()
    {
        return view('admmdocuments::gps-device-import');
    }

    public function gpsTemplate()
    {
        $headers = [
            'Date of Installation', 'Vehicle ID', 'Client', 'Fleet No.',
            'Vehicle Make', 'GPS Device IMEI', 'GPS Device Type',
            'SIM MSISDN', 'Bantu Technician', 'Notes'
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(
            fn() => print($content),
            'gps-device-import-template.csv',
            ['Content-Type' => 'text/csv']
        );
    }
}

