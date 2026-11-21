<?php

namespace Modules\AdmmInventory\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\AdmmInventory\Services\TechnicianStatsService;

/**
 * Period dropdown shared by list screens: All time, Daily (today), Weekly
 * (this week, Mon-Sun), Monthly (this month) or a custom date range, in
 * Harare time. Use with Livewire's WithPagination.
 */
trait HasPeriodFilter
{
    public string $period      = 'all';   // all | today | week | month | custom
    public string $fromDate    = '';
    public string $toDate      = '';
    public string $periodError = '';

    public function updatedPeriod($value): void
    {
        if (!in_array($value, ['all', 'today', 'week', 'month', 'custom'], true)) {
            $this->period = 'all';
        }

        if ($this->period === 'custom' && ($this->fromDate === '' || $this->toDate === '')) {
            $now = now()->timezone(TechnicianStatsService::TZ);
            $this->toDate   = $now->toDateString();
            $this->fromDate = $now->copy()->subDays(6)->toDateString();
        }

        $this->periodError = '';
        $this->resetPageSafely();
    }

    public function updatedFromDate(): void { $this->resetPageSafely(); }
    public function updatedToDate(): void   { $this->resetPageSafely(); }

    private function resetPageSafely(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
        foreach (['devicesPage', 'simsPage'] as $name) {
            if (method_exists($this, 'resetPage') && property_exists($this, 'paginators')) {
                $this->resetPage($name);
            }
        }
    }

    /**
     * Restrict a query to the chosen period. $dateOnly is for DATE columns
     * (e.g. installed_at); other columns are timestamps stored in app time.
     * An invalid custom range returns no rows and sets $periodError.
     */
    protected function applyPeriod(Builder $q, string $column, bool $dateOnly = false): Builder
    {
        $this->periodError = '';

        if ($this->period === 'all') {
            return $q;
        }

        try {
            [$from, $to] = TechnicianStatsService::range($this->period, $this->fromDate, $this->toDate);
        } catch (\DomainException $e) {
            $this->periodError = $e->getMessage();
            return $q->whereRaw('1 = 0');
        }

        if ($dateOnly) {
            return $q->whereBetween($column, [$from->toDateString(), $to->toDateString()]);
        }

        $tz = config('app.timezone');

        return $q->whereBetween($column, [$from->copy()->setTimezone($tz), $to->copy()->setTimezone($tz)]);
    }

    /** Spreadsheet-safe cell (stops =, +, -, @ being run as formulas). */
    protected function csvSafe($v): string
    {
        $v = (string) $v;
        return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
    }
}
