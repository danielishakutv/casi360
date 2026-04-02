<?php

namespace App\Traits;

use App\Services\CacheService;

/**
 * Trait for controllers that perform write operations.
 * Call $this->bustCache('module') after create/update/delete to
 * invalidate cached responses for the affected module.
 */
trait InvalidatesCache
{
    /**
     * Invalidate response cache for one or more modules.
     *
     * @param string|array $modules Module name(s) e.g. 'hr', 'procurement', ['hr', 'reports']
     */
    protected function bustCache(string|array $modules): void
    {
        $modules = is_array($modules) ? $modules : [$modules];

        foreach ($modules as $module) {
            CacheService::invalidateResponses($module);
        }
    }
}
