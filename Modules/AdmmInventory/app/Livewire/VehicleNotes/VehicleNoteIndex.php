<?php

namespace Modules\AdmmInventory\Livewire\VehicleNotes;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Models\Client;
use Modules\AdmmInventory\Models\VehicleNote;
use Modules\AfisPipeline\Models\AfisTracker;

class VehicleNoteIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public $clientFilter = '';
    public string $status = 'active';   // active | resolved | all
    public string $message = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingClientFilter(): void { $this->resetPage(); }
    public function updatingStatus(): void       { $this->resetPage(); }

    public function resolve(int $id): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        VehicleNote::active()->whereKey($id)->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);
        $this->message = 'Note resolved.';
    }

    public function render()
    {
        $term = trim($this->search);

        $notes = VehicleNote::query()
            ->when($this->status === 'active', fn ($q) => $q->active())
            ->when($this->status === 'resolved', fn ($q) => $q->where(fn ($w) => $w->whereNotNull('resolved_at')
                ->orWhere('expires_at', '<', now()->timezone('Africa/Harare')->toDateString())))
            ->when($this->clientFilter !== '' && $this->clientFilter !== null, fn ($q) => $q->where('client_id', (int) $this->clientFilter))
            ->when($term !== '', function ($q) use ($term) {
                $ids = AfisTracker::where('label', 'like', "%{$term}%")->limit(500)->pluck('id');
                $q->where(fn ($w) => $w->where('note', 'like', "%{$term}%")->orWhereIn('tracker_id', $ids));
            })
            ->orderByDesc('id')
            ->paginate(25);

        return view('admminventory::livewire.vehicle-notes.index', [
            'notes'    => $notes,
            'trackers' => AfisTracker::whereIn('id', $notes->pluck('tracker_id'))->pluck('label', 'id'),
            'clients'  => Client::orderBy('name')->get(),
            'users'    => \App\Models\User::whereIn('id', $notes->pluck('created_by')->filter()->unique())->pluck('name', 'id'),
        ]);
    }
}
