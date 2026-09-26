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
use App\Enums\ContentEditorType;
use App\Enums\Permission;
use App\Services\RevisionService;
use Illuminate\Validation\ValidationException;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;

/**
 * Action to restore a page from a specified revision
 *
 * A backup is automatically created as TYPE_RESTORE_BACKUP only if the state before restoration
 * differs from the latest revision (logic on the RevisionService side).
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

    /**
     * Refuse to restore a snapshot stored with the Blade editor type.
     *
     * RevisionService::restore() writes the snapshot back as-is, so an old
     * Blade revision would re-arm a page whose body is executed on render.
     * Pages can no longer be created or edited as Blade; neither can they be
     * restored as Blade.
     */
    protected function validate(Actor $actor, array $data): void
    {
        $stored = ($this->revision->snapshot ?? [])['editor_type'] ?? null;

        $editorType = match (true) {
            $stored instanceof ContentEditorType => $stored,
            is_int($stored), is_string($stored) && ctype_digit($stored) => ContentEditorType::tryFrom((int) $stored),
            is_string($stored) => ContentEditorType::tryFromSlug($stored),
            default => null,
        };

        if ($editorType === ContentEditorType::BLADE) {
            throw ValidationException::withMessages([
                'revision' => __('dixlase-pages::admin/pages/validation.revision_blade_not_restorable'),
            ]);
        }
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
