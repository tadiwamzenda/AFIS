<?php

namespace Modules\AdmmInventory\Livewire\Clients;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\Client;
use Modules\Core\Contracts\AuditLogInterface;

class ClientIndex extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function delete(int $id, AuditLogInterface $auditLog): void
    {
        $client = Client::findOrFail($id);

        $auditLog->record(
            event:      'client.deleted',
            module:     'AdmmInventory',
            data:       ['name' => $client->name],
            entityType: 'Client',
            entityId:   $id
        );

        $client->delete();
        session()->flash('success', "{$client->name} deleted successfully.");
    }

    public function render()
    {
        $clients = Client::query()
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('contact_email', 'like', "%{$this->search}%")
                  ->orWhere('contact_person', 'like', "%{$this->search}%");
            }))
            ->when($this->statusFilter !== '', fn($q) =>
                $q->where('is_active', $this->statusFilter === 'active')
            )
            ->withCount(['gpsDevices', 'accessories'])
            ->orderBy('name')
            ->paginate(20);

        return view('admminventory::livewire.clients.index', compact('clients'));
    }
}