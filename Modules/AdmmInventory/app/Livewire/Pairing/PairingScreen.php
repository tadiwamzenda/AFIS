<?php

namespace Modules\AdmmInventory\Livewire\Pairing;

use Livewire\Component;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\PairingService;

class PairingScreen extends Component
{
    public string $deviceSearch = '';
    public string $simSearch    = '';
    public string $techSearch   = '';

    public ?int $deviceId     = null;
    public ?int $simId        = null;
    public ?int $technicianId = null;

    public string $message = '';
    public string $error   = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
    }

    // ─── Step 1: device ──────────────────────────────────────────────────────

    public function selectDevice(int $id, PairingService $svc): void
    {
        $this->clearNotices();
        if (!$svc->availableDevices()->whereKey($id)->exists()) {
            $this->error = 'That device is no longer available.';
            return;
        }
        $this->deviceId = $id;
        $this->deviceSearch = '';
    }

    public function clearDevice(): void
    {
        $this->deviceId = null;
        $this->clearNotices();
    }

    // ─── Step 2: SIM ─────────────────────────────────────────────────────────

    public function selectSim(int $id, PairingService $svc): void
    {
        $this->clearNotices();
        if (!$svc->availableSims()->whereKey($id)->exists()) {
            $this->error = 'That SIM card is no longer available.';
            return;
        }
        $this->simId = $id;
        $this->simSearch = '';
    }

    public function clearSim(): void
    {
        $this->simId = null;
        $this->clearNotices();
    }

    // ─── Step 3: technician ──────────────────────────────────────────────────

    public function selectTechnician(int $id): void
    {
        $this->clearNotices();
        if (!Technician::active()->whereKey($id)->exists()) {
            $this->error = 'That technician is not active.';
            return;
        }
        $this->technicianId = $id;
        $this->techSearch = '';
    }

    public function clearTechnician(): void
    {
        $this->technicianId = null;
        $this->clearNotices();
    }

    // ─── Save / undo ─────────────────────────────────────────────────────────

    public function confirm(PairingService $svc): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->clearNotices();

        if (!$this->deviceId || !$this->simId || !$this->technicianId) {
            $this->error = 'Choose a device, a SIM card and a technician first.';
            return;
        }

        try {
            $done = $svc->pair($this->deviceId, $this->simId, $this->technicianId, auth()->id());
        } catch (\DomainException $e) {
            $this->error = $e->getMessage();
            return;
        }

        // The technician stays selected so a batch can be handed to the same person.
        $this->reset('deviceId', 'simId', 'deviceSearch', 'simSearch');
        $this->message = "Paired IMEI {$done['imei']} with ICCID {$done['iccid']} and assigned to {$done['technician']}.";
    }

    public function unpair(int $deviceId, PairingService $svc): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->clearNotices();

        try {
            $done = $svc->unpair($deviceId, auth()->id());
        } catch (\DomainException $e) {
            $this->error = $e->getMessage();
            return;
        }

        $this->message = "Pairing undone. IMEI {$done['imei']} is back in office stock.";
    }

    private function clearNotices(): void
    {
        $this->message = '';
        $this->error   = '';
    }

    /** Escapes $text and wraps the first match of $needle in <mark>. */
    public function highlight(string $text, string $needle): string
    {
        $needle = trim($needle);
        if ($needle === '' || ($pos = mb_stripos($text, $needle)) === false) {
            return e($text);
        }
        $len = mb_strlen($needle);

        return e(mb_substr($text, 0, $pos))
            . '<mark class="bg-yellow-200 rounded px-0.5">' . e(mb_substr($text, $pos, $len)) . '</mark>'
            . e(mb_substr($text, $pos + $len));
    }

    public function render(PairingService $svc)
    {
        $device = $this->deviceId ? GpsDevice::find($this->deviceId) : null;
        $sim    = $this->simId ? SimCard::find($this->simId) : null;
        $tech   = $this->technicianId ? Technician::find($this->technicianId) : null;

        // A selection can disappear (paired by someone else, technician deactivated)
        if ($this->deviceId && !$device) { $this->deviceId = null; }
        if ($this->simId && !$sim)       { $this->simId = null; }
        if ($this->technicianId && (!$tech || !$tech->is_active)) { $this->technicianId = null; $tech = null; }

        $deviceResults = (!$device && $this->deviceSearch !== '') ? $svc->searchDevices($this->deviceSearch) : collect();
        $simResults    = ($device && !$sim && $this->simSearch !== '') ? $svc->searchSims($this->simSearch) : collect();

        $technicians = collect();
        if ($device && $sim && !$tech) {
            $technicians = Technician::active()
                ->withCount(['devices as held_count' => fn ($q) => $q->where('status', 'with_technician')])
                ->when($this->techSearch !== '', fn ($q) => $q->where('name', 'like', '%' . addcslashes(trim($this->techSearch), '%_\\') . '%'))
                ->orderBy('name')
                ->limit(12)
                ->get();
        }

        $recent = GpsDevice::with(['simCard', 'assignedTechnician'])
            ->where('status', 'with_technician')
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        return view('admminventory::livewire.pairing.index', [
            'device'        => $device,
            'sim'           => $sim,
            'tech'          => $tech,
            'deviceResults' => $deviceResults,
            'simResults'    => $simResults,
            'technicians'   => $technicians,
            'recent'        => $recent,
            'minDigits'     => PairingService::MIN_SEARCH_DIGITS,
        ]);
    }
}
