<?php

namespace Modules\AdmmInventory\Livewire\GpsDevices;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AuditLog\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;

class GpsDeviceForm extends Component
{
    public ?GpsDevice $device = null;

    public string $imei                = '';
    public string $model               = '';
    public string $device_type         = '';
    public string $vehicle_registration = '';
    public string $vehicle_make        = '';
    public string $fleet_number        = '';
    public string $status              = 'in_office_stock';
    public string $location_context    = 'in_office_stock';
    public string $technician          = '';
    public string $installed_at        = '';
    public string $notes               = '';
    public ?int   $client_id           = null;
    public ?int   $sim_card_id         = null;

    protected function rules(): array
    {
        $imeiUnique = $this->device?->exists
            ? 'unique:adm_gps_devices,imei,' . $this->device->id
            : 'unique:adm_gps_devices,imei';

        return [
            'imei'                 => ['required', 'string', 'max:20', $imeiUnique],
            'model'                => ['nullable', 'string', 'max:100'],
            'device_type'          => ['nullable', 'string', 'max:50'],
            'vehicle_registration' => ['nullable', 'string', 'max:20'],
            'vehicle_make'         => ['nullable', 'string', 'max:100'],
            'fleet_number'         => ['nullable', 'string', 'max:50'],
            'status'               => ['required', 'in:' . implode(',', array_keys(GpsDevice::STATUSES))],
            'location_context'     => ['nullable', 'string', 'max:100'],
            'client_id'            => ['nullable', 'exists:clients,id'],
            'sim_card_id'          => ['nullable', 'exists:adm_sim_cards,id'],
            'technician'           => ['nullable', 'string', 'max:100'],
            'installed_at'         => ['nullable', 'date'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?int $deviceId = null): void
    {
        $this->device = $deviceId ? GpsDevice::findOrFail($deviceId) : new GpsDevice();

        if ($this->device->exists) {
            $this->imei                = $this->device->imei ?? '';
            $this->model               = $this->device->model ?? '';
            $this->device_type         = $this->device->device_type ?? '';
            $this->vehicle_registration = $this->device->vehicle_registration ?? '';
            $this->vehicle_make        = $this->device->vehicle_make ?? '';
            $this->fleet_number        = $this->device->fleet_number ?? '';
            $this->status              = $this->device->status ?? 'in_office_stock';
            $this->location_context    = $this->device->location_context ?? 'in_office_stock';
            $this->client_id           = $this->device->client_id;
            $this->sim_card_id         = $this->device->sim_card_id;
            $this->technician          = $this->device->technician ?? '';
            $this->installed_at        = $this->device->installed_at?->format('Y-m-d') ?? '';
            $this->notes               = $this->device->notes ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'imei'                 => $this->imei,
            'model'                => $this->model ?: null,
            'device_type'          => $this->device_type ?: null,
            'vehicle_registration' => $this->vehicle_registration ?: null,
            'vehicle_make'         => $this->vehicle_make ?: null,
            'fleet_number'         => $this->fleet_number ?: null,
            'status'               => $this->status,
            'client_id'            => $this->client_id ?: null,
            'sim_card_id'          => $this->sim_card_id ?: null,
            'technician'           => $this->technician ?: null,
            'installed_at'         => $this->installed_at ?: null,
            'notes'                => $this->notes ?: null,
        ];

        $isNew = !$this->device->exists;
        $this->device->fill($data);
        $this->device->save();

        app(AuditLogService::class)->record(
            event:      $isNew ? 'gps_device.created' : 'gps_device.updated',
            module:     'AdmmInventory',
            data:       $data,
            entityType: GpsDevice::class,
            entityId:   $this->device->id
        );

        session()->flash('success', 'GPS device ' . ($isNew ? 'added' : 'updated') . ' successfully.');
        $this->redirect(route('admin.admm.gps-devices.index'));
    }

    public function delete(): void
    {
        if (!$this->device->exists) return;

        app(AuditLogService::class)->record(
            event:      'gps_device.deleted',
            module:     'AdmmInventory',
            data:       ['imei' => $this->device->imei],
            entityType: GpsDevice::class,
            entityId:   $this->device->id
        );

        $this->device->delete();

        session()->flash('success', 'GPS device deleted successfully.');
        $this->redirect(route('admin.admm.gps-devices.index'));
    }

    public function render()
    {
        return view('admminventory::livewire.gps-devices.form', [
            'clients'  => Client::active()->orderBy('name')->get(),
            'simCards' => SimCard::whereNull('client_id')
                ->orWhere('client_id', $this->client_id)
                ->orderBy('msisdn')
                ->get(),
        ]);
    }
}