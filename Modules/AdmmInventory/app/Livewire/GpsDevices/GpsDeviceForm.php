<?php

namespace Modules\AdmmInventory\Livewire\GpsDevices;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\Core\Contracts\AuditLogInterface;

class GpsDeviceForm extends Component
{
    public ?GpsDevice $device = null;
    public bool $isEditing    = false;

    public string  $serial_number        = '';
    public string  $model                = '';
    public string  $firmware_version     = '';
    public string  $purchase_date        = '';
    public string  $warranty_expiry_date = '';
    public string  $status               = 'in_office_stock';
    public string  $location_context     = 'internal_stock';
    public ?int    $client_id            = null;
    public ?int    $sim_card_id          = null;
    public string  $vehicle_registration = '';
    public string  $notes                = '';

    protected function rules(): array
    {
        $serialUnique = $this->isEditing
            ? 'unique:adm_gps_devices,serial_number,' . $this->device?->id
            : 'unique:adm_gps_devices,serial_number';

        return [
            'serial_number'        => ['required', 'string', 'max:100', $serialUnique],
            'model'                => ['required', 'string', 'max:100'],
            'firmware_version'     => ['nullable', 'string', 'max:50'],
            'purchase_date'        => ['nullable', 'date'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'status'               => ['required', 'in:installed_client,in_office_stock,under_repair,awaiting_disposal,decommissioned,lost_stolen'],
            'location_context'     => ['required', 'in:client_assigned,internal_stock,unallocated'],
            'client_id'            => ['nullable', 'exists:clients,id'],
            'sim_card_id'          => ['nullable', 'exists:adm_sim_cards,id'],
            'vehicle_registration' => ['nullable', 'string', 'max:20'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?GpsDevice $device = null): void
    {
        if ($device && $device->exists) {
            $this->isEditing            = true;
            $this->device               = $device;
            $this->serial_number        = $device->serial_number;
            $this->model                = $device->model;
            $this->firmware_version     = $device->firmware_version     ?? '';
            $this->purchase_date        = $device->purchase_date?->format('Y-m-d')        ?? '';
            $this->warranty_expiry_date = $device->warranty_expiry_date?->format('Y-m-d') ?? '';
            $this->status               = $device->status;
            $this->location_context     = $device->location_context;
            $this->client_id            = $device->client_id;
            $this->sim_card_id          = $device->sim_card_id;
            $this->vehicle_registration = $device->vehicle_registration ?? '';
            $this->notes                = $device->notes ?? '';
        }
    }

    public function save(AuditLogInterface $auditLog): void
    {
        $data = $this->validate();

        foreach (['purchase_date', 'warranty_expiry_date'] as $field) {
            if (empty($data[$field])) $data[$field] = null;
        }
        if (empty($data['client_id']))   $data['client_id']   = null;
        if (empty($data['sim_card_id'])) $data['sim_card_id'] = null;

        if ($this->isEditing) {
            $before = $this->device->toArray();
            $this->device->update($data);

            $auditLog->record(
                event: 'gps_device.updated',
                module: 'AdmmInventory',
                data: ['before' => $before, 'after' => $this->device->fresh()->toArray()],
                entityType: 'GpsDevice',
                entityId: $this->device->id
            );

            session()->flash('success', "Device {$this->device->serial_number} updated successfully.");
        } else {
            $device = GpsDevice::create($data);

            $auditLog->record(
                event: 'gps_device.created',
                module: 'AdmmInventory',
                data: $device->toArray(),
                entityType: 'GpsDevice',
                entityId: $device->id
            );

            session()->flash('success', "Device {$device->serial_number} created successfully.");
        }

        $this->redirect(route('admin.admm.gps-devices.index'));
    }

    public function render()
    {
        return view('admminventory::livewire.gps-devices.form', [
            'clients'  => Client::active()->orderBy('name')->get(),
            'simCards' => SimCard::where('location_context', '!=', 'client_assigned')
                ->orWhere('id', $this->sim_card_id)
                ->orderBy('iccid')
                ->get(),
        ]);
    }
}