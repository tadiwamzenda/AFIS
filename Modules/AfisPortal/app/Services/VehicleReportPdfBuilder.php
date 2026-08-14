<?php

namespace Modules\AfisPortal\Services;

use Illuminate\Support\Facades\Storage;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;

class VehicleReportPdfBuilder
{
    public function build(AfisAiReport $report, AfisTracker $tracker, int $days): string
    {
        $html = (new GithubFlavoredMarkdownConverter())->convert($report->response)->getContent();

        $pdf = Pdf::loadView('afisportal::reports.vehicle-behaviour', [
            'tracker'      => $tracker,
            'client'       => $tracker->client,
            'days'         => $days,
            'content_html' => $html,
            'generated_at' => now(),
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'      => false,
        ]);

        Storage::disk('local')->makeDirectory('vehicle-reports');
        $filename     = "vehicle_{$tracker->id}_" . now()->format('Ymd_His') . '.pdf';
        $relativePath = "vehicle-reports/{$filename}";

        Storage::disk('local')->put($relativePath, $pdf->output());

        return $relativePath;
    }
}