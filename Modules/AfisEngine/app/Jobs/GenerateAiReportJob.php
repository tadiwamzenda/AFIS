<?php

namespace Modules\AfisEngine\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\AfisEngine\Services\AfisEngineService;
use Modules\AfisEngine\Services\Prompts\PromptBuilder;
use Modules\AfisPipeline\Models\AfisTracker;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisIncidents\Services\IncidentReportDocxBuilder;
class GenerateAiReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(
        public string $reportType,
        public int    $clientId,
        public ?int   $trackerId = null,
        public array  $options   = []
    ) {}

        public function handle(AfisEngineService $engine, PromptBuilder $promptBuilder): void
    {
        $client  = Client::findOrFail($this->clientId);
        $tracker = $this->trackerId ? AfisTracker::find($this->trackerId) : null;

        // Prompt building and generateReport() were previously uncaught here.
        // AfisEngineService::generateReport() already correctly marks its own
        // AfisAiReport row as 'failed' before re-throwing — but nothing
        // downstream ever ran to reflect that on a DIFFERENT model this job
        // also owns: AfisIncident.status stayed stuck at 'analysing' forever
        // on any AI failure, even though the UI already has a 'failed' state
        // ready for it. Also covers a TypeError risk: vehicleBehaviourProfile()
        // requires a non-nullable AfisTracker — if the tracker was deleted
        // between dispatch and execution, $tracker is null and this throws
        // immediately, previously with the same "stuck forever" result.
        try {
            $prompt = match($this->reportType) {
                'vehicle_behaviour' => $promptBuilder->vehicleBehaviourProfile(
                    $tracker,
                    $this->options['days'] ?? 30
                ),
                'fleet_intelligence' => $promptBuilder->fleetIntelligence(
                    $client,
                    $this->options['days'] ?? 30
                ),
                'incident_analysis' => $promptBuilder->incidentAnalysis(
                    $tracker,
                    $this->options['incident_description'] ?? '',
                    $this->options['incident_date'] ?? now()->toDateString(),
                    $this->options['trip_report_text'] ?? null,
                    $this->options['speed_report_text'] ?? null,
                    $this->options['events_report_text'] ?? null,
                ),
                'predictive_intelligence' => $promptBuilder->predictiveIntelligence(
                    $client,
                    $this->options['days'] ?? 90
                ),
                default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
            };

            $aiReport = $engine->generateReport(
                reportType: $this->reportType,
                prompt:     $prompt,
                clientId:   $this->clientId,
                trackerId:  $this->trackerId,
                useCache:   $this->options['use_cache'] ?? true,
            );

        } catch (\Throwable $e) {
            Log::error('GenerateAiReportJob: failed before/during AI generation', [
                'report_type' => $this->reportType,
                'client_id'   => $this->clientId,
                'tracker_id'  => $this->trackerId,
                'error'       => $e->getMessage(),
            ]);

            if ($this->reportType === 'incident_analysis' && !empty($this->options['incident_id'])) {
                \Modules\AfisIncidents\Models\AfisIncident::where('id', $this->options['incident_id'])
                    ->update(['status' => 'failed']);
            }

            throw $e; // preserve normal queue retry/failed-job behaviour ($tries = 2)
        }

        // Link AI report back to incident, build the docx, and mark completed
        if ($this->reportType === 'incident_analysis' && !empty($this->options['incident_id'])) {
            $incident = \Modules\AfisIncidents\Models\AfisIncident::find($this->options['incident_id']);

            if ($incident) {
                try {
                    $reportPath = app(IncidentReportDocxBuilder::class)
                        ->build($incident, $aiReport->response, $tracker);

                    $incident->update([
                        'status'       => 'completed',
                        'ai_report_id' => $aiReport->id,
                        'report_path'  => $reportPath,
                    ]);
                } catch (\Throwable $e) {
                    // AI analysis still succeeded even if the docx export failed —
                    // don't mark the incident as failed for a formatting problem.
                    Log::error('GenerateAiReportJob: docx build failed', [
                        'incident_id' => $incident->id,
                        'error'       => $e->getMessage(),
                    ]);
                    $incident->update([
                        'status'       => 'completed',
                        'ai_report_id' => $aiReport->id,
                    ]);
                }
            }
        }

        // Build the vehicle behaviour PDF and link it back to the ai_report row.
        // AI analysis still counts as a success even if the PDF build fails —
        // don't lose the (expensive) AI response over a formatting problem.
        if ($this->reportType === 'vehicle_behaviour' && $tracker) {
            try {
                $reportPath = app(\Modules\AfisPortal\Services\VehicleReportPdfBuilder::class)
                    ->build($aiReport, $tracker, $this->options['days'] ?? 30);

                $aiReport->update(['report_path' => $reportPath]);
            } catch (\Throwable $e) {
                Log::error('GenerateAiReportJob: vehicle report PDF build failed', [
                    'tracker_id' => $tracker->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info("GenerateAiReportJob: completed {$this->reportType} for client {$this->clientId}");
            }
}