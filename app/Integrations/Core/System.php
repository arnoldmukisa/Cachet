<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Integrations\Core;

use CachetHQ\Cachet\Integrations\Contracts\System as SystemContract;
use CachetHQ\Cachet\Models\Component;
use CachetHQ\Cachet\Models\Incident;

/**
 * This is the core system class.
 *
 * @author James Brooks <james@alt-three.com>
 */
class System implements SystemContract
{
    /**
     * Get the entire system status.
     *
     * @return array
     */
    public function getStatus()
    {
        $enabledScope = Component::enabled();
        $totalComponents = $enabledScope->count();
        $majorOutages = $enabledScope->status(4)->count();
        $isMajorOutage = $totalComponents ? ($majorOutages / $totalComponents) >= 0.5 : false;

        // Default data
        $status = [
            'system_status'  => 'info',
            'system_message' => trans_choice('cachet.service.bad', $totalComponents),
            'favicon'        => 'favicon-high-alert',
        ];

        if ($isMajorOutage) {
            $status = [
                'system_status'  => 'danger',
                'system_message' => trans_choice('cachet.service.major', $totalComponents),
                'favicon'        => 'favicon-high-alert',
            ];
        } elseif ($enabledScope->notStatus(1)->count() === 0) {
            // If all our components are ok, do we have any non-fixed incidents?
            $incidents = Incident::notScheduled()->orderBy('occurred_at', 'desc')->get()->filter(function ($incident) {
                return $incident->status > 0;
            });
            $incidentCount = $incidents->count();
            $unresolvedCount = $incidents->filter(function ($incident) {
                return !$incident->is_resolved;
            })->count();

            if ($incidentCount === 0 || ($incidentCount >= 1 && $unresolvedCount === 0)) {
                $status = [
                    'system_status'  => 'success',
                    'system_message' => trans_choice('cachet.service.good', $totalComponents),
                    'favicon'        => 'favicon',
                ];
            }
        } elseif ($enabledScope->whereIn('status', [2, 3])->count() > 0) {
            $status['favicon'] = 'favicon-medium-alert';
        }

        return $status;
    }

    /**
     * Get the cachet version.
     *
     * @return string
     */
    public function getVersion()
    {
        return CACHET_VERSION;
    }
}
