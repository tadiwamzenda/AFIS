<?php

namespace Modules\AfisPortal\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfflineReportPdfBuilder
{
    public function build(array $viewData, string $titleBase): string
    {
        // offline-report.blade.php's own @media print rules already hide
        // the .no-print toolbar (Print/Close buttons) — DomPDF renders in
        // a print context by default, so this reuses the exact same
        // template used for the live browser view with no changes needed.
        $pdf = Pdf::loadView('afisportal::reports.offline-report', $viewData)
            ->setPaper('a4', 'landscape');

        Storage::disk('local')->makeDirectory('offline-reports');
        $filename     = 'offline_report_' . Str::slug($titleBase) . '_' . now()->format('Ymd_His') . '.pdf';
        $relativePath = "offline-reports/{$filename}";

        Storage::disk('local')->put($relativePath, $pdf->output());

        return $relativePath;
    }
}