<?php

namespace Modules\AdmmInventory\Livewire\Accessories;

use Livewire\Component;
use Modules\AdmmInventory\Models\Accessory;
use Modules\AdmmInventory\Models\AccessoryType;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\Core\Contracts\AuditLogInterface;

class AccessoryForm extends Component
{
    public ?Accessory $accessory = null;
    public bool $isEditing       = false;

    public string $serial_number      = '';
    public string $accessory_type_id  = '';
    public string $status             = 'in_office_stock';
    public string $location_context   = 'internal_stock';
    public ?int   $client_id          = null;
    public ?int   $gps_device_id      = null;
    public string $purchase_date      = '';
    public string $notes              = '';

    protected function rules(): array
    {
        return [
            'serial_number'     => ['nullable', 'string', 'max:100'],
            'accessory_type_id' => ['required', 'exists:adm_accessory_types,id'],
            'status'            => ['required', 'in:installed_client,in_office_stock,faulty,decommissioned,lost'],
            'location_context'  => ['required', 'in:client_assigned,internal_stock,unallocated'],
            'client_id'         => ['nullable', 'exists:clients,id'],
            'gps_device_id'     => ['nullable', 'exists:adm_gps_devices,id'],
            'purchase_date'     => ['nullable', 'date'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?Accessory $accessory = null): void
    {
        if ($accessory && $accessory->exists) {
            $this->isEditing         = true;
            $this->accessory         = $accessory;
            $this->serial_number     = $accessory->serial_number    ?? '';
            $this->accessory_type_id = (string) $accessory->accessory_type_id;
            $this->status            = $accessory->status;
            $this->location_context  = $accessory->location_context;
            $this->client_id         = $accessory->client_id;
            $this->gps_device_id     = $accessory->gps_device_id;
            $this->purchase_date     = $accessory->purchase_date?->format('Y-m-d') ?? '';
            $this->notes             = $accessory->notes ?? '';
        }
    }

    public function save(AuditLogInterface $auditLog): void
    {
        $data = $this->validate();

        if (empty($data['purchase_date']))  $data['purchase_date']  = null;
        if (empty($data['client_id']))      $data['client_id']      = null;
        if (empty($data['gps_device_id']))  $data['gps_device_id']  = null;
        if (empty($data['serial_number']))  $data['serial_number']  = null;

        if ($this->isEditing) {
            $before = $this->accessory->toArray();
            $this->accessory->update($data);

            $auditLog->record(
                event: 'accessory.updated',
                module: 'AdmmInventory',
                data: ['before' => $before, 'after' => $this->accessory->fresh()->toArray()],
                entityType: 'Accessory',
                entityId: $this->accessory->id
            );

            session()->flash('success', 'Accessory updated successfully.');
        } else {
            $accessory = Accessory::create($data);

            $auditLog->record(
                event: 'accessory.created',
                module: 'AdmmInventory',
                data: $accessory->toArray(),
                entityType: 'Accessory',
                entityId: $accessory->id
            );

            session()->flash('success', 'Accessory created successfully.');
        }

        $this->redirect(route('admin.admm.accessories.index'));
    }

    public function render()
    {
        return view('admminventory::livewire.accessories.form', [
            'types'      => AccessoryType::active()->orderBy('name')->get(),
            'clients'    => Client::active()->orderBy('name')->get(),
            'gpsDevices' => GpsDevice::whereNotIn('status', ['decommissioned', 'lost_stolen'])
                ->orderBy('serial_number')
                ->get(),
        ]);
    }   
}