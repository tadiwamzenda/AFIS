<?php

namespace Modules\AdmmInventory\Livewire\Clients;

use Livewire\Component;
use Modules\AdmmInventory\Models\Client;
use Modules\Core\Contracts\AuditLogInterface;

class ClientForm extends Component
{
    public ?Client $client = null;
    public bool $isEditing = false;

    public string $name            = '';
    public string $navixy_account_id = '';
    public int    $navixy_instance        = 1;
    public ?string $navixy_security_group_id = '';
    public string $contact_person  = '';
    public string $contact_email   = '';
    public string $contact_phone   = '';
    public bool   $is_active       = true;
    public string $notes           = '';
    public string $navixy_group_prefix = '';
    public string $navixy_api_key      = '';
    public int    $navixy_instance_secondary = 0;


    protected function rules(): array
    {
        $accountUnique = $this->isEditing
            ? 'unique:clients,navixy_account_id,' . $this->client?->id
            : 'unique:clients,navixy_account_id';

            return [
            'name'                    => ['required', 'string', 'max:255'],
            'navixy_instance'         => ['required', 'in:1,2'],
            'navixy_account_id'       => ['nullable', 'integer'],
            'navixy_security_group_id'=> ['nullable', 'integer'],
            'navixy_group_prefix'     => ['nullable', 'string', 'max:100'],
            'navixy_api_key'            => ['nullable', 'string', 'max:255'],
            'navixy_instance_secondary' => ['nullable', 'in:0,1,2'],
            'contact_person'          => ['nullable', 'string', 'max:255'],
            'contact_email'           => ['nullable', 'email', 'max:255'],
            'contact_phone'           => ['nullable', 'string', 'max:20'],
            'is_active'               => ['boolean'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
            ];
    }

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->isEditing        = true;
            $this->client           = $client;
            $this->name             = $client->name;
            $this->navixy_account_id = (string) $client->navixy_account_id;
            $this->navixy_instance         = $client->navixy_instance ?? 1;
            $this->navixy_security_group_id = (string) ($client->navixy_security_group_id ?? '');
            $this->contact_person   = $client->contact_person ?? '';
            $this->contact_email    = $client->contact_email  ?? '';
            $this->contact_phone    = $client->contact_phone  ?? '';
            $this->is_active        = $client->is_active;
            $this->notes            = $client->notes ?? '';
            $this->navixy_group_prefix = $client->navixy_group_prefix ?? '';
            $this->navixy_api_key             = $client->navixy_api_key ?? '';
            $this->navixy_instance_secondary  = $client->navixy_instance_secondary ?? 0;
        }
    }

    public function save(AuditLogInterface $auditLog): void
    {
        $data = $this->validate();

        // Convert empty strings to null for optional integer fields
        $data['navixy_account_id']        = !empty($data['navixy_account_id']) ? (int) $data['navixy_account_id'] : 0;
        $data['navixy_security_group_id'] = !empty($data['navixy_security_group_id']) ? (int) $data['navixy_security_group_id'] : null;
        $data['navixy_group_prefix']      = !empty($data['navixy_group_prefix']) ? $data['navixy_group_prefix'] : null;
        $data['navixy_api_key']            = !empty($data['navixy_api_key']) ? $data['navixy_api_key'] : null;
        $data['navixy_instance_secondary'] = !empty($data['navixy_instance_secondary']) ? (int) $data['navixy_instance_secondary'] : null;

        if ($this->isEditing) {
            $before = $this->client->toArray();
            $this->client->update($data);

            $auditLog->record(
                event:      'client.updated',
                module:     'AdmmInventory',
                data:       ['before' => $before, 'after' => $this->client->fresh()->toArray()],
                entityType: 'Client',
                entityId:   $this->client->id
            );

            session()->flash('success', "Client {$this->client->name} updated successfully.");
        } else {
            $client = Client::create($data);

            $auditLog->record(
                event:      'client.created',
                module:     'AdmmInventory',
                data:       $client->toArray(),
                entityType: 'Client',
                entityId:   $client->id
            );

            session()->flash('success', "Client {$client->name} created successfully.");
        }

        $this->redirect(route('admin.admm.clients.index'));
    }

    public function render()
    {
        return view('admminventory::livewire.clients.form');
    }
}