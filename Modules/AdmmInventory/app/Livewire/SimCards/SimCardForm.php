<?php

namespace Modules\AdmmInventory\Livewire\SimCards;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\SimCard;
use Modules\Core\Contracts\AuditLogInterface;


class SimCardForm extends Component
{
    public ?SimCard $simCard = null;
    public bool $isEditing   = false;
    public string $batch_code = '';

    // Form fields
    public string  $iccid               = '';
    public string  $msisdn              = '';
    public string  $network_provider    = '';
    public string  $bundle_type         = '';
    public string  $bundle_renewal_date = '';
    public string  $status              = 'unassigned';
    public string  $location_context    = 'unallocated';
    public ?int    $client_id           = null;
    public string  $notes               = '';

    protected function rules(): array
    {
        $iccidUnique = $this->isEditing
            ? 'unique:adm_sim_cards,iccid,' . $this->simCard?->id
            : 'unique:adm_sim_cards,iccid';

        return [
            'iccid'               => ['required', 'string', 'max:22', $iccidUnique],
            'msisdn'              => ['nullable', 'string', 'max:20'],
            'network_provider'    => ['required', 'string', 'max:100'],
            'batch_code'          => ['nullable', 'string', 'max:100'],
            'bundle_type'         => ['nullable', 'string', 'max:100'],
            'bundle_renewal_date' => ['nullable', 'date'],
            'status'              => ['required', 'in:active_client,active_internal,unassigned,inactive,suspended,deactivated,lost'],
            'location_context'    => ['required', 'in:client_assigned,internal_stock,unallocated'],
            'client_id'           => ['nullable', 'exists:clients,id'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?SimCard $simCard = null): void
    {
        if ($simCard && $simCard->exists) {
            $this->isEditing          = true;
            $this->simCard            = $simCard;
            $this->iccid              = $simCard->iccid;
            $this->msisdn             = $simCard->msisdn ?? '';
            $this->network_provider   = $simCard->network_provider;
            $this->batch_code         = $simCard->batch_code ?? '';
            $this->bundle_type        = $simCard->bundle_type ?? '';
            $this->bundle_renewal_date = $simCard->bundle_renewal_date?->format('Y-m-d') ?? '';
            $this->status             = $simCard->status;
            $this->location_context   = $simCard->location_context;
            $this->client_id          = $simCard->client_id;
            $this->notes              = $simCard->notes ?? '';
        }
    }

    public function save(AuditLogInterface $auditLog): void
    {
        $data = $this->validate();

        if (empty($data['bundle_renewal_date'])) {
            $data['bundle_renewal_date'] = null;
        }
        if (empty($data['client_id'])) {
            $data['client_id'] = null;
        }

        if ($this->isEditing) {
            $before = $this->simCard->toArray();
            $this->simCard->update($data);

            $auditLog->record(
                event: 'sim_card.updated',
                module: 'AdmmInventory',
                data: ['before' => $before, 'after' => $this->simCard->fresh()->toArray()],
                entityType: 'SimCard',
                entityId: $this->simCard->id
            );

            session()->flash('success', "SIM card {$this->simCard->iccid} updated successfully.");
        } else {
            $simCard = SimCard::create($data);

            $auditLog->record(
                event: 'sim_card.created',
                module: 'AdmmInventory',
                data: $simCard->toArray(),
                entityType: 'SimCard',
                entityId: $simCard->id
            );

            session()->flash('success', "SIM card {$simCard->iccid} created successfully.");
        }

        $this->redirect(route('admin.admm.sim-cards.index'));
    }

    public function render()
    {
        return view('admminventory::livewire.sim-cards.form', [
            'clients' => Client::active()->orderBy('name')->get(),
        ]);
    }
}