<?php

namespace Modules\AdmmDocuments\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AdmmDocuments\Imports\AssetRegisterImport;

class AssetImportWizard extends Component
{
    use WithFileUploads;

    public $file     = null;
    public bool  $done     = false;
    public int   $imported = 0;
    public int   $skipped  = 0;
    public array $errors   = [];
    public string $error   = '';

    public function import(): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);
        $this->error = '';

        try {
            $importer = new AssetRegisterImport();
            Excel::import($importer, $this->file->getRealPath());

            $this->imported = $importer->imported;
            $this->skipped  = $importer->skipped;
            $this->errors   = $importer->errors;
            $this->done     = true;

        } catch (\Throwable $e) {
            $this->error = 'Import failed: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('admmdocuments::livewire.asset-import');
    }
}