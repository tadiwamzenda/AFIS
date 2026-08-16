<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\AdmmInventory\Models\Client;
use Modules\Notifications\Models\AfisNotification;
use Modules\AfisPipeline\Models\AfisOfflineIncident;

class NotificationReportController extends Controller
{
    public function generate(Request $request)
    {
        $durationFilter = $request->duration ?? '';
        $severity       = $request->severity ?? '';
        $status         = $request->status   ?? '';
        $client         = $request->client   ?? '';
        $search         = $request->search   ?? '';

        $notifications = AfisNotification::query()
            ->when($severity, fn($q) => $q->where('severity', $severity))
            ->when($status,   fn($q) => $q->where('status', $status))
            ->when($client,   fn($q) => $q->where('data->client', $client))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('data->tracker_label', 'like', "%{$search}%")
                        ->orWhere('data->vehicle_label', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->get();

        // Duration is only meaningful for the three offline-alert types,
        // which store offline_hours in their data payload at creation
        // time. Notification types with no offline-duration concept (fuel
        // drain, sync failures, new trackers) are excluded whenever a
        // bucket is actively selected — duration simply doesn't apply.
        if ($durationFilter !== '') {
            $notifications = $notifications->filter(function ($n) use ($durationFilter) {
                $hours = $n->data['offline_hours'] ?? null;
                if ($hours === null) return false;
                return $this->durationBucket($hours * 3600) === $durationFilter;
            })->values();
        }

        $totalCount    = $notifications->count();
        $pendingCount  = $notifications->where('status', 'pending')->count();
        $attendedCount = $notifications->where('status', 'attended')->count();
        $criticalCount = $notifications->where('severity', 'critical')->count();

        // Hard cap on rows actually rendered — DomPDF's table-layout engine
        // (Cellmap.php) scales memory use disproportionately with row count
        // and can exhaust PHP's memory limit entirely on a large enough
        // table (confirmed: ZETDC hit the 2GB ceiling). Stat cards above
        // still reflect the TRUE total; only the printed table is capped.
        $notificationsOmitted = max(0, $totalCount - 500);
        $notificationsForPdf  = $notifications->take(500);

        // Displayed range reflects what actually matched, rather than an
        // artificial rolling window that wasn't really being enforced.
        $from = $notifications->min('created_at');
        $to   = $notifications->max('created_at');

        // Vehicle summary — scoped to the SAME search/client filters as the
        // notifications list above. Previously this ran a completely
        // separate, unfiltered query, so searching for one vehicle still
        // showed every other vehicle's incident history alongside it.
        $clientId = $client ? Client::where('name', $client)->value('id') : null;

        $vehicleSummary = AfisOfflineIncident::with('tracker')
            ->when($search, fn($q) => $q->whereHas('tracker', fn($t) => $t->where('label', 'like', "%{$search}%")))
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->get()
            ->groupBy('tracker_id')
            ->map(function ($rows) {
                return [
                    'label'        => $rows->first()->tracker?->label ?? '—',
                    'went_offline' => $rows->count(),
                    'came_online'  => $rows->whereNotNull('came_online_at')->count(),
                ];
            })
            ->sortByDesc('went_offline')
            ->values();

        $vehicleSummaryOmitted = max(0, $vehicleSummary->count() - 200);
        $vehicleSummaryForPdf  = $vehicleSummary->take(200);

        $pdf = Pdf::loadView('notifications::reports.notification-report', [
            'notifications'         => $notificationsForPdf,
            'notificationsOmitted'  => $notificationsOmitted,
            'vehicleSummaryOmitted' => $vehicleSummaryOmitted,
            'from'           => $from,
            'to'             => $to,
            'durationFilter' => $durationFilter,
            'severity'       => $severity,
            'status'         => $status,
            'client'         => $client,
            'search'         => $search,
            'totalCount'     => $totalCount,
            'pendingCount'   => $pendingCount,
            'attendedCount'  => $attendedCount,
            'criticalCount'  => $criticalCount,
            'vehicleSummary' => $vehicleSummaryForPdf,
            'isPdf'          => true,
        ])->setPaper('a4', 'landscape');

        $filename = 'notifications_report_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    private function durationBucket(int $seconds): string
    {
        if ($seconds < 3600)   return 'none';
        if ($seconds < 86400)  return 'hourly';
        if ($seconds < 604800) return 'daily';
        return 'weekly';
    }
}