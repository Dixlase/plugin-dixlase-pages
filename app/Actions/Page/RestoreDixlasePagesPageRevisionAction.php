<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
use App\Services\RevisionService;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;

/**
 * ページを指定リビジョンから復元する Action
 *
 * 復元前の状態が直前リビジョンと差分がある場合のみ、TYPE_RESTORE_BACKUP として
 * 自動的にバックアップが作成される（RevisionService 側のロジック）。
 */
class RestoreDixlasePagesPageRevisionAction extends AbstractAction
{
    public function __construct(
        protected readonly DixlasePagesPageRevision $revision,
        protected readonly RevisionService $revisionService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'dixlase_pages.page.revision.restored';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        /** @var DixlasePagesPage $page */
        $page = $this->revisionService->restore($this->revision, $actor->getActorId());

        return ActionResult::success(
            model: $page,
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
            'revision_type' => $this->revision->type,
            'revision_created_at' => $this->revision->created_at?->toIso8601String(),
        ];
    }
}
