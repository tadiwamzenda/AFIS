<?php

namespace Modules\AdmmDocuments\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AdmmDocuments\Services\SimCardImportService;
use Modules\AdmmDocuments\Models\SimImport;

class SimCardImport extends Component
{
    use WithFileUploads;

    public ?object $file = null;
    public bool    $previewing = false;
    public bool    $imported   = false;
    public array   $previewRows    = [];
    public ?array  $result     = null;
    public string  $error      = '';
    
    // Debug property (keep for troubleshooting)
    public string $debugInfo = 'Awaiting action...';



public function previewImport(): void  
{
    $this->debugInfo = 'PREVIEW CALLED at ' . now()->toTimeString();
    $this->error     = '';

    if (!$this->file) {
        $this->error = 'Please select a file first.';
        return;
    }

    try {
        $service = app(\Modules\AdmmDocuments\Services\SimCardImportService::class);
        $path    = $this->file->getRealPath();

        if (!$path || !file_exists($path)) {
            $this->error = 'Could not read the uploaded file. Please try again.';
            return;
        }

        $rows = $service->parse($path);

        if ($rows->isEmpty()) {
            $this->error = 'No valid rows found. Check your file has the 4 required headers.';
            return;
        }

        $this->previewRows    = $rows->take(10)->toArray();
        $this->previewing     = true;
        $this->debugInfo      = 'Parsed ' . $rows->count() . ' rows successfully';

    } catch (\Throwable $e) {
        $this->error     = $e->getMessage();
        $this->debugInfo = 'Error: ' . $e->getMessage();
    }
}

public function import(): void
{
    $this->error = '';

    try {
        $service = app(\Modules\AdmmDocuments\Services\SimCardImportService::class);
        $rows    = $service->parse($this->file->getRealPath());
        $result  = $service->import($rows, $this->file->getClientOriginalName());

        $this->result     = $result->toArray();
        $this->imported   = true;
        $this->previewing = false;

    } catch (\Throwable $e) {
        $this->error = 'Import failed: ' . $e->getMessage();
    }
}

public function resetForm(): void
{
    $this->file        = null;
    $this->previewing  = false;
    $this->imported    = false;
    $this->previewRows = [];  // was: $preview
    $this->result      = null;
    $this->error       = '';
    $this->debugInfo   = 'Form reset';
}

    public function render()
    {
        $history = SimImport::with('importedBy')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('admmdocuments::livewire.sim-import', compact('history'));
    }
}