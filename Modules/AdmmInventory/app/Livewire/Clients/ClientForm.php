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
    public string $contact_person  = '';
    public string $contact_email   = '';
    public string $contact_phone   = '';
    public bool   $is_active       = true;
    public string $notes           = '';

    protected function rules(): array
    {
        $accountUnique = $this->isEditing
            ? 'unique:clients,navixy_account_id,' . $this->client?->id
            : 'unique:clients,navixy_account_id';

        return [
            'name'              => ['required', 'string', 'max:255'],
            'navixy_account_id' => ['required', 'integer', $accountUnique],
            'contact_person'    => ['nullable', 'string', 'max:255'],
            'contact_email'     => ['nullable', 'email', 'max:255'],
            'contact_phone'     => ['nullable', 'string', 'max:20'],
            'is_active'         => ['boolean'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->isEditing        = true;
            $this->client           = $client;
            $this->name             = $client->name;
            $this->navixy_account_id = (string) $client->navixy_account_id;
            $this->contact_person   = $client->contact_person ?? '';
            $this->contact_email    = $client->contact_email  ?? '';
            $this->contact_phone    = $client->contact_phone  ?? '';
            $this->is_active        = $client->is_active;
            $this->notes            = $client->notes ?? '';
        }
    }

    public function save(AuditLogInterface $auditLog): void
    {
        $data = $this->validate();

        if ($this->isEditing) {
            $before = $this->client->toArray();
            $this->client->update($data);

            $auditLog->record(
                event: 'client.updated',
                module: 'AdmmInventory',
                data: ['before' => $before, 'after' => $this->client->fresh()->toArray()],
                entityType: 'Client',
                entityId: $this->client->id
            );

            session()->flash('success', "Client {$this->client->name} updated successfully.");
        } else {
            $client = Client::create($data);

            $auditLog->record(
                event: 'client.created',
                module: 'AdmmInventory',
                data: $client->toArray(),
                entityType: 'Client',
                entityId: $client->id
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