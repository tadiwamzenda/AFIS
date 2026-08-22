<?php

namespace Modules\AfisIncidents\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisIncidents\Models\AfisIncident;
use Modules\AfisPipeline\Models\AfisTracker;
use Smalot\PdfParser\Parser as PdfParser;
use Illuminate\Support\Facades\Http;

class IncidentForm extends Component
{
    use WithFileUploads;

    public int    $clientId;
    public ?int   $trackerId     = null;
    public string $incidentDate  = '';
    public string $incidentTime  = '';
    public string $description   = '';
    public bool   $submitted     = false;
    public ?int   $incidentId    = null;
    public string $error         = '';

    // Optional Navixy PDF uploads
    public $tripReportPdf   = null;
    public $speedReportPdf  = null;
    public $eventsReportPdf = null;

    // Report attribution — Prepared By is editable, Reviewed By is fixed (see submit()).
    public string $preparedByName  = '';
    public string $preparedByTitle = '';

    protected function rules(): array
    {
        return [
            'trackerId'       => ['required', 'exists:afis_trackers,id'],
            'incidentDate'    => ['required', 'date'],
            'incidentTime'    => ['required'],
            'description'     => ['required', 'string', 'min:20', 'max:2000'],
            'tripReportPdf'   => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'speedReportPdf'  => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'eventsReportPdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'preparedByName'  => ['required', 'string', 'max:255'],
            'preparedByTitle' => ['required', 'string', 'max:255'],
        ];
    }

    public function mount(int $clientId): void
    {
        $this->clientId     = $clientId;
        $this->incidentDate = now()->format('Y-m-d');
        $this->incidentTime = now()->format('H:i');

        $this->preparedByName = Auth::user()?->name ?? '';
        // NOTE: no "title"/"role" column confirmed on the User model yet —
        // this defaults to a placeholder the user can edit per-incident.
        // Swap for a real column (e.g. Auth::user()->job_title) if one exists.
        $this->preparedByTitle = 'Fleet Operations';
    }

    public function submit(): void
    {
        $this->validate();
        $this->error = '';

        try {
            $tracker          = AfisTracker::findOrFail($this->trackerId);
            $incidentDateTime = Carbon::parse("{$this->incidentDate} {$this->incidentTime}");

            $incident = AfisIncident::create([
                'client_id'         => $this->clientId,
                'tracker_id'        => $this->trackerId,
                'navixy_tracker_id' => $tracker->navixy_tracker_id,
                'vehicle_label'     => $tracker->label,
                'incident_date'     => $incidentDateTime,
                'description'       => $this->description,
                'status'            => 'analysing',
                'logged_by'         => Auth::id(),
                'prepared_by_name'  => $this->preparedByName,
                'prepared_by_title' => $this->preparedByTitle,
                // Fixed per spec — stored (not just displayed) so historic
                // reports keep whoever actually reviewed them at the time.
                'reviewed_by_name'  => 'Tadiwa Mzenda',
                'reviewed_by_title' => 'Business Intelligence Analyst',
            ]);

            // Extract PDF text now, synchronously — the Livewire temp upload
            // won't reliably survive until the queued job picks this up later,
            // so we can't pass file references into the job, only plain text.
            $navixyReports = [
                'trip_report_text'   => $this->extractPdfText($this->tripReportPdf),
                'speed_report_text'  => $this->extractPdfText($this->speedReportPdf),
                'events_report_text' => $this->extractPdfText($this->eventsReportPdf),
            ];

            GenerateAiReportJob::dispatch(
                reportType: 'incident_analysis',
                clientId:   $this->clientId,
                trackerId:  $this->trackerId,
                options: array_merge([
                    'incident_description' => $this->description,
                    'incident_date'        => $incidentDateTime->toDateTimeString(),
                    'incident_id'          => $incident->id,
                    'use_cache'            => false,
                ], $navixyReports)
            );

            $this->incidentId = $incident->id;
            $this->submitted  = true;

        } catch (\Throwable $e) {
            $this->error = 'Failed to log incident: ' . $e->getMessage();
        }
    }

    private function extractPdfText($uploadedFile): ?string
    {
        if (!$uploadedFile) {
            return null;
        }

        try {
            $parser   = new PdfParser();
            $document = $parser->parseFile($uploadedFile->getRealPath());
            return $document->getText();
        } catch (\Throwable $e) {
            Log::warning('IncidentForm: PDF text extraction failed', [
                'file'  => method_exists($uploadedFile, 'getClientOriginalName') ? $uploadedFile->getClientOriginalName() : 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function render()
    {
        $allowedNavixyIds = null;

        if (\Illuminate\Support\Facades\Auth::user()?->isClientUser()) {
            $sessionHash = session('navixy_hash');
            if ($sessionHash) {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(15)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post('https://api.us.navixy.com/v2/tracker/list', [
                            'hash' => $sessionHash,
                        ]);
                    $allowedNavixyIds = collect($response->json()['list'] ?? [])
                        ->pluck('id')
                        ->toArray();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('IncidentForm: could not fetch Navixy tracker list', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->when($allowedNavixyIds !== null, fn($q) => $q->whereIn('navixy_tracker_id', $allowedNavixyIds))
            ->orderBy('label')
            ->get();

        return view('afisincidents::livewire.incident-form', compact('trackers'));
    }
}