<?php

namespace Modules\AfisIncidents\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Jobs\GenerateAiReportJob;
use Modules\AfisIncidents\Models\AfisIncident;
use Modules\AfisPipeline\Models\AfisTracker;

class IncidentForm extends Component
{
    public int    $clientId;
    public ?int   $trackerId     = null;
    public string $incidentDate  = '';
    public string $incidentTime  = '';
    public string $description   = '';
    public bool   $submitted     = false;
    public ?int   $incidentId    = null;
    public string $error         = '';

    protected function rules(): array
    {
        return [
            'trackerId'    => ['required', 'exists:afis_trackers,id'],
            'incidentDate' => ['required', 'date'],
            'incidentTime' => ['required'],
            'description'  => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    public function mount(int $clientId): void
    {
        $this->clientId    = $clientId;
        $this->incidentDate = now()->format('Y-m-d');
        $this->incidentTime = now()->format('H:i');
    }

    public function submit(): void
    {
        $this->validate();
        $this->error = '';

        try {
            $tracker       = AfisTracker::findOrFail($this->trackerId);
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
            ]);

            // Dispatch AI analysis job
            GenerateAiReportJob::dispatch(
                reportType: 'incident_analysis',
                clientId:   $this->clientId,
                trackerId:  $this->trackerId,
                options: [
                    'incident_description' => $this->description,
                    'incident_date'        => $incidentDateTime->toDateTimeString(),
                    'incident_id'          => $incident->id,
                    'use_cache'            => false,
                ]
            );

            $this->incidentId = $incident->id;
            $this->submitted  = true;

        } catch (\Throwable $e) {
            $this->error = 'Failed to log incident: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $trackers = AfisTracker::where('client_id', $this->clientId)
            ->orderBy('label')
            ->get();

        return view('afisincidents::livewire.incident-form', compact('trackers'));
    }
}