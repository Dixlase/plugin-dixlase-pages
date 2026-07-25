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

namespace Plugins\DixlasePages\App\Actions\Page;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;

/**
 * Action to toggle the protection flag of a page revision
 *
 * Revisions with protection enabled are excluded from automatic deletion when the retention count is exceeded.
 */
class ToggleDixlasePagesPageRevisionProtectionAction extends AbstractAction
{
    public function __construct(
        protected readonly DixlasePagesPageRevision $revision,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'dixlase_pages.page.revision.protection_toggled';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $this->revision->update(['is_protected' => ! $this->revision->is_protected]);

        return ActionResult::success(
            model: $this->revision,
            label: (string) $this->revision->id,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'revision_id' => $this->revision->id,
            'is_protected' => $this->revision->is_protected,
        ];
    }
}
