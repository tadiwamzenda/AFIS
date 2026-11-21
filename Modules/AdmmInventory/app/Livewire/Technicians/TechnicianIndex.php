<?php

namespace Modules\AdmmInventory\Livewire\Technicians;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\AdmmInventory\Models\Technician;
use Modules\AdmmInventory\Services\TechnicianStatsService;
use Modules\Core\Contracts\AuditLogInterface;

class TechnicianIndex extends Component
{
    public string $name  = '';
    public string $phone = '';

    public ?int   $editingId = null;
    public string $editName  = '';
    public string $editPhone = '';

    // Statistics report
    public string $period      = 'month';   // today | week | month | custom
    public string $fromDate    = '';
    public string $toDate      = '';
    public string $groupBy     = 'day';     // day | week | month
    public $reportTechnician   = '';        // untyped: select sends '' for "all"
    public string $reportError = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $this->toDate   = now()->timezone(TechnicianStatsService::TZ)->toDateString();
        $this->fromDate = now()->timezone(TechnicianStatsService::TZ)->subDays(6)->toDateString();
    }

    public function setPeriod(string $period): void
    {
        abort_unless(in_array($period, ['today', 'week', 'month', 'custom'], true), 422);
        $this->period = $period;
        $this->reportError = '';
        if ($period === 'month') {
            $this->groupBy = 'day';
        }
    }

    private function technicianFilter(): ?int
    {
        return ($this->reportTechnician !== '' && $this->reportTechnician !== null) ? (int) $this->reportTechnician : null;
    }

    private function runReport(TechnicianStatsService $svc): ?array
    {
        $this->reportError = '';
        abort_unless(in_array($this->groupBy, ['day', 'week', 'month'], true), 422);

        try {
            [$from, $to] = TechnicianStatsService::range($this->period, $this->fromDate, $this->toDate);
        } catch (\DomainException $e) {
            $this->reportError = $e->getMessage();
            return null;
        }

        return $svc->report($from, $to, $this->technicianFilter(), $this->groupBy);
    }

    public function exportReport(TechnicianStatsService $svc)
    {
        abort_unless(auth()->user()?->isBtStaff(), 403);

        $r = $this->runReport($svc);
        if (!$r) {
            return null;
        }

        $name = 'technician-report-' . $r['from']->format('Ymd') . '-' . $r['to']->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Period', $r['from']->format('d M Y') . ' to ' . $r['to']->format('d M Y')]);
            fputcsv($out, []);
            fputcsv($out, ['Technician', 'Devices held now', 'Devices installed now', 'Received in period', 'Returned in period', 'New installs', 'Repair/swaps', 'Total installs']);
            foreach ($r['rows'] as $row) {
                fputcsv($out, [$row['name'], $row['held'], $row['installed'], $row['received'], $row['returned'], $row['installs'], $row['repairs'], $row['total_installs']]);
            }
            $t = $r['totals'];
            fputcsv($out, ['TOTAL', $t['held'], $t['installed'], $t['received'], $t['returned'], $t['installs'], $t['repairs'], $t['total_installs']]);
            fputcsv($out, []);
            fputcsv($out, ['Breakdown', 'Received', 'Returned', 'New installs', 'Repair/swaps', 'Total installs']);
            foreach ($r['buckets'] as $b) {
                fputcsv($out, [$b['label'], $b['received'], $b['returned'], $b['installs'], $b['repairs'], $b['total_installs']]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
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

    public function render(TechnicianStatsService $svc)
    {
        return view('admminventory::livewire.technicians.index', [
            'technicians' => $svc->snapshot(),
            'report'      => $this->runReport($svc),
        ]);
    }
}
