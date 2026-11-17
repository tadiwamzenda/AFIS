<?php

namespace Modules\AdmmInventory\Livewire\Technicians;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\AdmmInventory\Models\Technician;
use Modules\Core\Contracts\AuditLogInterface;

class TechnicianIndex extends Component
{
    public string $name  = '';
    public string $phone = '';

    public ?int   $editingId = null;
    public string $editName  = '';
    public string $editPhone = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
    }

    public function add(AuditLogInterface $auditLog): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $this->name = $this->clean($this->name);

        $data = $this->validate([
            'name'  => ['required', 'string', 'max:100', 'unique:adm_technicians,name'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $tech = Technician::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?: null,
            'is_active' => true,
        ]);

        $auditLog->record(
            event: 'technician.created', module: 'AdmmInventory',
            data: $tech->toArray(), entityType: 'Technician', entityId: $tech->id
        );

        $this->reset('name', 'phone');
        session()->flash('success', "Technician {$tech->name} added.");
    }

    public function startEdit(int $id): void
    {
        $tech = Technician::findOrFail($id);
        $this->editingId = $tech->id;
        $this->editName  = $tech->name;
        $this->editPhone = $tech->phone ?? '';
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editName', 'editPhone');
        $this->resetErrorBag();
    }

    public function saveEdit(AuditLogInterface $auditLog): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $tech = Technician::findOrFail($this->editingId);
        $this->editName = $this->clean($this->editName);

        $data = $this->validate([
            'editName'  => ['required', 'string', 'max:100', Rule::unique('adm_technicians', 'name')->ignore($tech->id)],
            'editPhone' => ['nullable', 'string', 'max:30'],
        ]);

        $before = $tech->toArray();
        $tech->update(['name' => $data['editName'], 'phone' => $data['editPhone'] ?: null]);

        $auditLog->record(
            event: 'technician.updated', module: 'AdmmInventory',
            data: ['before' => $before, 'after' => $tech->fresh()->toArray()],
            entityType: 'Technician', entityId: $tech->id
        );

        $this->cancelEdit();
        session()->flash('success', 'Technician updated.');
    }

    public function toggleActive(int $id, AuditLogInterface $auditLog): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $tech = Technician::findOrFail($id);
        $tech->update(['is_active' => !$tech->is_active]);

        $auditLog->record(
            event: $tech->is_active ? 'technician.activated' : 'technician.deactivated',
            module: 'AdmmInventory', data: ['name' => $tech->name],
            entityType: 'Technician', entityId: $tech->id
        );
    }

    private function clean(string $v): string
    {
        return trim(preg_replace('/\s+/', ' ', $v));
    }

    public function render()
    {
        return view('admminventory::livewire.technicians.index', [
            'technicians' => Technician::withCount('devices')
                ->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }
}
