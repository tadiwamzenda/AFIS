<?php

namespace Modules\AdmmInventory\Livewire\StockTake;

use Livewire\Component;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\StockTakeService;

class StockTakeScreen extends Component
{
    public string $section = 'technicians';   // technicians | sims | devices | faulty

    public string $search     = '';
    public bool   $onlyStale  = false;
    public $technicianFilter = null;   // untyped: the select sends '' for "All"

    public string $message = '';
    public string $error   = '';

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
                    'rows'       => $svc->unpairedSims($this->search, $this->onlyStale)->limit(StockTakeService::LIST_LIMIT)->get(),
                    'total'      => (clone $all)->count(),
                    'staleTotal' => $svc->staleCount($all),
                ];
                break;

            case 'devices':
                $all = $svc->availableDevices();
                $data += [
                    'rows'       => $svc->availableDevices($this->search, $this->onlyStale)->limit(StockTakeService::LIST_LIMIT)->get(),
                    'total'      => (clone $all)->count(),
                    'staleTotal' => $svc->staleCount($all),
                ];
                break;

            case 'faulty':
                $data += ['devices' => $svc->faultyDevices(), 'sims' => $svc->faultySims()];
                break;
        }

        return view('admminventory::livewire.stock-take.index', $data);
    }
}
