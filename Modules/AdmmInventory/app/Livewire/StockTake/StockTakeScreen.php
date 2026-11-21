<?php

namespace Modules\AdmmInventory\Livewire\StockTake;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\AdmmInventory\Livewire\Concerns\HasPeriodFilter;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\SimCard;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\StockTakeService;

class StockTakeScreen extends Component
{
    use WithPagination, HasPeriodFilter;

    private const PER_PAGE = 25;

    public string $section = 'technicians';   // technicians | sims | devices | faulty

    public string $search     = '';
    public bool   $onlyStale  = false;
    public $technicianFilter = null;   // untyped: the select sends '' for "All"

    public string $message = '';
    public string $error   = '';

    public function updatingSearch(): void    { $this->resetPage(); }
    public function updatingOnlyStale(): void { $this->resetPage(); }

    public function mount(string $section = 'technicians'): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        abort_unless(in_array($section, ['technicians', 'sims', 'devices', 'faulty'], true), 404);
        $this->section = $section;
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function verifyDevice(int $id, StockTakeService $s): void   { $this->run(fn () => $s->verifyDevice($id, auth()->id()), 'Device verified.'); }
    public function verifySim(int $id, StockTakeService $s): void      { $this->run(fn () => $s->verifySim($id, auth()->id()), 'SIM card verified.'); }
    public function flagSimLost(int $id, StockTakeService $s): void    { $this->run(fn () => $s->flagSimLost($id, auth()->id()), 'SIM card flagged as lost.'); }
    public function flagSimFaulty(int $id, StockTakeService $s): void  { $this->run(fn () => $s->flagSimFaulty($id, auth()->id()), 'SIM card moved to Faulty.'); }
    public function flagDeviceFaulty(int $id, StockTakeService $s): void { $this->run(fn () => $s->flagDeviceFaulty($id, auth()->id()), 'Device moved to Faulty.'); }
    public function flagDeviceLost(int $id, StockTakeService $s): void { $this->run(fn () => $s->flagDeviceLost($id, auth()->id()), 'Device flagged as lost / stolen.'); }
    public function returnDevice(int $id, StockTakeService $s): void   { $this->run(fn () => $s->returnDeviceToStock($id, auth()->id()), 'Device returned to office stock.'); }
    public function returnSim(int $id, StockTakeService $s): void      { $this->run(fn () => $s->returnSimToStock($id, auth()->id()), 'SIM card returned to stock.'); }
    public function writeOffDevice(int $id, StockTakeService $s): void { $this->run(fn () => $s->writeOffDevice($id, auth()->id()), 'Device written off.'); }
    public function writeOffSim(int $id, StockTakeService $s): void    { $this->run(fn () => $s->writeOffSim($id, auth()->id()), 'SIM card written off.'); }

    private function technicianId(): ?int
    {
        return ($this->technicianFilter !== null && $this->technicianFilter !== '') ? (int) $this->technicianFilter : null;
    }

    private function run(callable $action, string $success): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        $this->message = $this->error = '';

        try {
            $action();
            $this->message = $success;
        } catch (\DomainException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ─── CSV export of the technician stock view ─────────────────────────────

    public function exportTechnicianCsv(StockTakeService $svc)
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $groups = $svc->technicianStock($this->technicianId());
        $safe   = fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : (string) $v;

        return response()->streamDownload(function () use ($groups, $safe) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Technician', 'IMEI', 'Device type', 'ICCID', 'Phone number', 'Paired on'], ',', '"', '');
            foreach ($groups as $g) {
                foreach ($g['units'] as $u) {
                    fputcsv($out, [
                        $safe($g['name']),
                        $u['device']->imei,
                        $safe($u['device']->device_type),
                        $u['sim']?->iccid,
                        $u['sim']?->msisdn,
                        $u['paired_at']?->timezone('Africa/Harare')->format('Y-m-d H:i'),
                    ], ',', '"', '');
                }
            }
            fclose($out);
        }, 'technician_stock_' . now()->timezone('Africa/Harare')->format('Ymd_Hi') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ─── CSV export of the current list (same filters as the screen) ─────────

    public function exportCsv(StockTakeService $svc)
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);
        abort_unless(in_array($this->section, ['sims', 'devices', 'faulty'], true), 404);

        $fmtDate = fn ($c, $f = 'Y-m-d H:i') => $c?->timezone('Africa/Harare')->format($f);
        $safe    = fn ($v) => $this->csvSafe($v);

        if ($this->section === 'sims') {
            $q = $this->applyPeriod($svc->unpairedSims($this->search, $this->onlyStale), 'created_at');
            $file = 'unpaired_sims';
            $head = ['ICCID', 'Phone', 'Network', 'Status', 'Added', 'Last verified'];
            $row  = fn ($s) => [$s->iccid, $s->msisdn, $safe($s->network_provider), $s->status_label, $fmtDate($s->created_at), $fmtDate($s->last_verified_at)];
        } elseif ($this->section === 'devices') {
            $q = $this->applyPeriod($svc->availableDevices($this->search, $this->onlyStale), 'created_at');
            $file = 'available_devices';
            $head = ['IMEI', 'Device type', 'Model', 'Added', 'Last verified'];
            $row  = fn ($d) => [$d->imei, $safe($d->device_type), $safe($d->model), $fmtDate($d->created_at), $fmtDate($d->last_verified_at)];
        }

        if ($this->section === 'faulty') {
            $dq = $this->applyPeriod(GpsDevice::with('simCard')->where('status', 'faulty')->orderByDesc('updated_at'), 'updated_at');
            $sq = $this->applyPeriod(SimCard::where('status', 'faulty')->orderByDesc('updated_at'), 'updated_at');
            if ($this->periodError) {
                return null;
            }

            return response()->streamDownload(function () use ($dq, $sq, $fmtDate, $safe) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Type', 'IMEI / ICCID', 'Details', 'Marked faulty'], ',', '"', '');
                foreach ($dq->get() as $d) {
                    fputcsv($out, ['Device', $d->imei, $safe(trim(($d->device_type ?: '') . ($d->simCard ? ' · SIM ' . $d->simCard->iccid : ''))), $fmtDate($d->updated_at)], ',', '"', '');
                }
                foreach ($sq->get() as $s) {
                    fputcsv($out, ['SIM', $s->iccid, $safe(trim(($s->msisdn ?: 'No phone number') . ' · ' . $s->network_provider)), $fmtDate($s->updated_at)], ',', '"', '');
                }
                fclose($out);
            }, 'faulty_stock_' . now()->timezone('Africa/Harare')->format('Ymd_Hi') . '.csv', ['Content-Type' => 'text/csv']);
        }

        if ($this->periodError) {
            return null;
        }

        return response()->streamDownload(function () use ($q, $head, $row) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $head, ',', '"', '');
            // unpairedSims/availableDevices carry their own ordering, so chunk() (not chunkById)
            $q->chunk(500, function ($rows) use ($out, $row) {
                foreach ($rows as $r) {
                    fputcsv($out, $row($r), ',', '"', '');
                }
            });
            fclose($out);
        }, $file . '_' . now()->timezone('Africa/Harare')->format('Ymd_Hi') . '.csv', ['Content-Type' => 'text/csv']);
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render(StockTakeService $svc)
    {
        $data = ['limit' => StockTakeService::LIST_LIMIT, 'staleDays' => StockTakeService::STALE_DAYS];

        switch ($this->section) {
            case 'technicians':
                $groups = $svc->technicianStock($this->technicianId());
                $data += [
                    'groups'      => $groups,
                    'totalUnits'  => $groups->sum(fn ($g) => $g['units']->count()),
                    'technicians' => Technician::active()->orderBy('name')->get(['id', 'name']),
                ];
                break;

            case 'sims':
                $all = $svc->unpairedSims();
                $data += [
                    'rows'       => $this->applyPeriod($svc->unpairedSims($this->search, $this->onlyStale), 'created_at')->paginate(self::PER_PAGE),
                    'total'      => (clone $all)->count(),
                    'staleTotal' => $svc->staleCount($all),
                ];
                break;

            case 'devices':
                $all = $svc->availableDevices();
                $data += [
                    'rows'       => $this->applyPeriod($svc->availableDevices($this->search, $this->onlyStale), 'created_at')->paginate(self::PER_PAGE),
                    'total'      => (clone $all)->count(),
                    'staleTotal' => $svc->staleCount($all),
                ];
                break;

            case 'faulty':
                $data += [
                    'devices' => $this->applyPeriod(GpsDevice::with('simCard')->where('status', 'faulty')->orderByDesc('updated_at'), 'updated_at')
                                      ->paginate(self::PER_PAGE, ['*'], 'devicesPage'),
                    'sims'    => $this->applyPeriod(SimCard::where('status', 'faulty')->orderByDesc('updated_at'), 'updated_at')
                                      ->paginate(self::PER_PAGE, ['*'], 'simsPage'),
                ];
                break;
        }

        return view('admminventory::livewire.stock-take.index', $data);
    }
}
