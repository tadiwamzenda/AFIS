<?php

namespace Modules\AdmmInventory\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\AdmmInventory\Models\GpsDevice;
use Modules\AdmmInventory\Models\InstallationEvent;
use Modules\AdmmInventory\Models\Technician;

/**
 * Technician statistics.
 *
 *  Held now       devices with status with_technician in their custody
 *  Installed now  devices currently installed that they installed
 *  Period stats   counted from the append-only installation events:
 *                 received (paired/assigned to them), returned (unpaired),
 *                 new installs, repair/swaps.
 *
 * Events written by the Asset Register backfill are excluded from period
 * stats (their timestamp is the backfill run, not the real install date).
 */
class TechnicianStatsService
{
    public const TZ = 'Africa/Harare';

    /** @return array{0:Carbon,1:Carbon} start and end of the period in Harare time */
    public static function range(string $period, ?string $from = null, ?string $to = null): array
    {
        $now = Carbon::now(self::TZ);

        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'week':   // Monday to Sunday
                return [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)];
            case 'month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'custom':
                if (!$from || !$to) {
                    throw new \DomainException('Choose both a start and an end date.');
                }
                try {
                    $f = Carbon::parse($from, self::TZ)->startOfDay();
                    $t = Carbon::parse($to, self::TZ)->endOfDay();
                } catch (\Throwable) {
                    throw new \DomainException('Invalid date.');
                }
                if ($f->gt($t)) {
                    throw new \DomainException('The start date must be before the end date.');
                }
                if ($f->diffInDays($t) > 366) {
                    throw new \DomainException('Choose a period of one year or less.');
                }
                return [$f, $t];
        }

        throw new \DomainException('Unknown period.');
    }

    /** Current holdings and installs per technician (not period based). */
    public function snapshot(): Collection
    {
        return Technician::query()
            ->withCount([
                'devices as held_count'      => fn ($q) => $q->where('status', 'with_technician'),
                'devices as installed_count' => fn ($q) => $q->where('status', 'installed'),
            ])
            ->orderByDesc('is_active')->orderBy('name')
            ->get();
    }

    /**
     * @return array{rows:Collection, totals:array, buckets:Collection, from:Carbon, to:Carbon}
     */
    public function report(Carbon $from, Carbon $to, ?int $technicianId = null, string $groupBy = 'day'): array
    {
        $tz     = self::TZ;
        $dbFrom = $from->copy()->setTimezone(config('app.timezone'));
        $dbTo   = $to->copy()->setTimezone(config('app.timezone'));

        $events = InstallationEvent::query()
            ->whereBetween('created_at', [$dbFrom, $dbTo])
            ->whereIn('event_type', ['paired', 'assigned', 'unpaired', 'installed', 'repair_swap'])
            ->where(fn ($q) => $q->whereNull('notes')->orWhere('notes', 'not like', 'Backfilled%'))
            ->when($technicianId, fn ($q) => $q->where('technician_id', $technicianId))
            ->get(['technician_id', 'event_type', 'created_at']);

        $kind = fn ($t) => match ($t) {
            'paired', 'assigned' => 'received',
            'unpaired'           => 'returned',
            'installed'          => 'installs',
            'repair_swap'        => 'repairs',
        };

        $blank = ['received' => 0, 'returned' => 0, 'installs' => 0, 'repairs' => 0];

        $byTech = [];
        $byBucket = [];
        foreach ($events as $e) {
            $k = $kind($e->event_type);
            $tid = (int) $e->technician_id;
            $byTech[$tid] ??= $blank;
            $byTech[$tid][$k]++;

            $local = $e->created_at->copy()->timezone($tz);
            [$sortKey, $label] = $this->bucket($local, $groupBy);
            $byBucket[$sortKey] ??= ['label' => $label] + $blank;
            $byBucket[$sortKey][$k]++;
        }
        ksort($byBucket);

        $snap = $this->snapshot()->when($technicianId, fn ($c) => $c->where('id', $technicianId));

        $rows = $snap->map(function ($t) use ($byTech, $blank) {
            $s = $byTech[$t->id] ?? $blank;
            return ['name' => $t->name, 'active' => $t->is_active,
                    'held' => $t->held_count, 'installed' => $t->installed_count] + $s
                 + ['total_installs' => $s['installs'] + $s['repairs']];
        })->filter(fn ($r) => $r['active'] || $r['held'] || $r['installed'] || $r['received'] || $r['returned'] || $r['total_installs'])
          ->values();

        if (isset($byTech[0]) && !$technicianId) {
            $s = $byTech[0];
            $rows->push(['name' => '(no technician recorded)', 'active' => true, 'held' => 0, 'installed' => 0]
                + $s + ['total_installs' => $s['installs'] + $s['repairs']]);
        }

        $totals = [
            'held'           => $rows->sum('held'),
            'installed'      => $rows->sum('installed'),
            'received'       => $rows->sum('received'),
            'returned'       => $rows->sum('returned'),
            'installs'       => $rows->sum('installs'),
            'repairs'        => $rows->sum('repairs'),
            'total_installs' => $rows->sum('total_installs'),
        ];

        $buckets = collect($byBucket)->values()->map(fn ($b) => $b + ['total_installs' => $b['installs'] + $b['repairs']]);

        return compact('rows', 'totals', 'buckets', 'from', 'to');
    }

    /** @return array{0:string,1:string} [sort key, label] */
    private function bucket(Carbon $d, string $groupBy): array
    {
        return match ($groupBy) {
            'week'  => [$d->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
                        'Week of ' . $d->copy()->startOfWeek(Carbon::MONDAY)->format('d M Y')],
            'month' => [$d->format('Y-m'), $d->format('F Y')],
            default => [$d->format('Y-m-d'), $d->format('D d M Y')],
        };
    }
}
