<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase Pages is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Plugins\DixlasePages\App\Actions\Settings;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

/**
 * Save the plugin's settings and record the change in the audit log.
 *
 * Follows core's UpdateSettingsAction: read the submitted keys before and
 * after the write and log one bulk settings entry with the diff. None of
 * these settings holds personal data, so the old and new values are kept;
 * publish_min_role in particular decides who may publish pages.
 *
 * Access to the settings screen is decided per route by
 * config/admin/roles.php and the controller, so this action adds no core
 * permission of its own.
 */
class UpdateDixlasePagesSettingsAction extends AbstractAction
{
    /** Identifies this screen in the audit log. */
    public const SETTINGS_PAGE = 'dixlase_pages.settings';

    protected function requiredPermission(): ?Permission
    {
        return null;
    }

    protected function auditAction(): string
    {
        return self::SETTINGS_PAGE;
    }

    /**
     * @param  array<string, mixed>  $data  Validated DixlasePagesUpdatePagesSettingsRequest input
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $keys = array_keys($data);

        $before = $this->read($keys);
        DixlasePagesPageSetting::setMany($data);
        $after = $this->read($keys);

        return new ActionResult(
            success: true,
            metadata: [
                'before' => $before,
                'after' => $after,
            ],
        );
    }

    /**
     * Log the change as a bulk settings entry; nothing is written when no
     * value changed.
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        Audit::logBulkSettingsChange(
            self::SETTINGS_PAGE,
            $result->metadata['before'] ?? [],
            $result->metadata['after'] ?? [],
            $actor->toAuditMorph(),
        );
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function read(array $keys): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = DixlasePagesPageSetting::getValue($key);
        }

        return $values;
    }
}
