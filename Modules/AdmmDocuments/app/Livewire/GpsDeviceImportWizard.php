<?php

namespace Modules\AdmmDocuments\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdmmDocuments\Imports\GpsDeviceImport;

class GpsDeviceImportWizard extends Component
{
    use WithFileUploads;

    public $file      = null;
    public bool  $done      = false;
    public int   $imported  = 0;
    public int   $skipped   = 0;
    public array $errors    = [];
    public string $error    = '';

    public function import(): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
        $this->error = '';

        try {
            $importer = new GpsDeviceImport();
            Excel::import($importer, $this->file->getRealPath());

            $this->imported = $importer->imported;
            $this->skipped  = $importer->skipped;
            $this->errors   = $importer->errors;
            $this->done     = true;

        } catch (\Throwable $e) {
            $this->error = 'Import failed: ' . $e->getMessage();
        }
    }

    public function downloadTemplate()
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

    public function render()
    {
        return view('admmdocuments::livewire.gps-device-import');
    }
}