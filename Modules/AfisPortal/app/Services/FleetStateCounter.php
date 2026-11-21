<?php

namespace Modules\AfisPortal\Services;

use Modules\AfisPipeline\Models\AfisOfflineIncident;
use Modules\AfisPipeline\Models\AfisTracker;

/**
 * Fleet / Online / Offline numbers for the top panels.
 *
 * "Offline" counts only vehicles that are offline AND Functional (Active) AND
 * with Bantu Track as action party (a blank counts as those defaults).
 * Offline vehicles marked otherwise are reported separately as "excluded".
 * Total Fleet is every tracker, so  total = online + offline + excluded + unknown.
 */
class FleetStateCounter
{
    /** @return array<int, array{total:int,online:int,offline:int,excluded:int,offline_all:int,unknown:int}> keyed by client id */
    public function byClient(array $clientIds): array
    {
        if (!$clientIds) {
            return [];
        }

        $rows = AfisTracker::whereIn('client_id', $clientIds)
            ->selectRaw("client_id, COUNT(*) AS total, SUM(online_status = 'online') AS online, SUM(online_status = 'offline') AS offline_all")
            ->groupBy('client_id')
            ->get()->keyBy('client_id');

        $excluded = AfisTracker::whereIn('client_id', $clientIds)
            ->where('online_status', 'offline')
            ->whereIn('id', AfisOfflineIncident::open()->excluded()->select('tracker_id'))
            ->selectRaw('client_id, COUNT(*) AS n')
            ->groupBy('client_id')
            ->pluck('n', 'client_id');

        $out = [];
        foreach ($clientIds as $id) {
            $r   = $rows->get($id);
            $tot = (int) ($r->total ?? 0);
            $on  = (int) ($r->online ?? 0);
            $all = (int) ($r->offline_all ?? 0);
            $exc = (int) ($excluded[$id] ?? 0);

            $out[$id] = [
                'total'       => $tot,
                'online'      => $on,
                'offline'     => $all - $exc,
                'excluded'    => $exc,
                'offline_all' => $all,
                'unknown'     => $tot - $on - $all,
            ];
        }

        return $out;
    }

    /** @return array{total:int,online:int,offline:int,excluded:int,offline_all:int,unknown:int} */
    public function forClients(array $clientIds): array
    {
        $sum = ['total' => 0, 'online' => 0, 'offline' => 0, 'excluded' => 0, 'offline_all' => 0, 'unknown' => 0];

        foreach ($this->byClient($clientIds) as $c) {
            foreach ($sum as $k => $_) {
                $sum[$k] += $c[$k];
            }
        }

        return $sum;
    }
}
