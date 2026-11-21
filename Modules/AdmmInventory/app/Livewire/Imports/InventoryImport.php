<?php

namespace Modules\AdmmInventory\Livewire\Imports;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\AdmmInventory\Services\InventoryCsvImporter;
use Modules\Core\Contracts\AuditLogInterface;

class InventoryImport extends Component
{
    use WithFileUploads;

    public string $type = 'devices';   // devices | sims
    public $file = null;
    public ?array $result = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
    }

    public function updatedType(): void
    {
        $this->result = null;
        $this->reset('file');
        $this->resetErrorBag();
    }

    public function import(InventoryCsvImporter $importer, AuditLogInterface $auditLog): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $this->validate([
            'type' => ['required', 'in:devices,sims'],
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:5120'],
        ], [
            'file.required'   => 'Choose a CSV file first.',
            'file.extensions' => 'The file must be a .csv file.',
            'file.max'        => 'The file is larger than 5 MB.',
        ]);

        $path = $this->file->getRealPath();

        $result = $this->type === 'devices'
            ? $importer->importDevices($path)
            : $importer->importSims($path);

        $result['type'] = $this->type;
        $this->result   = $result;

        $auditLog->record(
            event: "inventory.{$this->type}_imported",
            module: 'AdmmInventory',
            data: [
                'file'     => $this->file->getClientOriginalName(),
                'total'    => $result['total'],
                'imported' => $result['imported'],
                'failed'   => count($result['failed']),
                'fatal'    => $result['fatal'],
            ],
            entityType: 'Inventory',
            entityId: 0
        );

        $this->reset('file');
    }

    public function downloadTemplate()
    {
        $csv = $this->type === 'devices'
            ? "IMEI,Device_Type,Model\n"
            : "ICCID,Phone_Number\n";

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, "{$this->type}_import_template.csv", ['Content-Type' => 'text/csv']);
    }

    public function downloadErrors()
    {
        $failed = $this->result['failed'] ?? [];
        abort_if(empty($failed), 404);

        $name = "{$this->type}_import_errors_" . now()->format('Ymd_His') . '.csv';

        // Stop spreadsheet apps from executing cell values that start with = + - @
        $safe = fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : (string) $v;

        return response()->streamDownload(function () use ($failed, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Row', 'Field', 'Value', 'Reason'], ',', '"', '');
            foreach ($failed as $f) {
                fputcsv($out, [$f['row'], $f['field'], $safe($f['value']), $f['reason']], ',', '"', '');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        return view('admminventory::livewire.imports.index');
    }
}
