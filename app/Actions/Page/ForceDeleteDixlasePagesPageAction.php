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

use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * Delete a trashed page permanently. The model's forceDeleted hook removes
 * its files.
 */
class ForceDeleteDixlasePagesPageAction extends AbstractDixlasePagesPageAction
{
    public function __construct(
        protected readonly DixlasePagesPage $page,
    ) {}

    protected function auditAction(): string
    {
        return 'dixlase_pages.page.force_deleted';
    }

    /**
     * The forceDeleted hook deletes files, which a transaction cannot roll
     * back.
     */
    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $this->page->forceDelete();

        return ActionResult::success(model: $this->page, label: $this->page->slug);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return ['slug' => $this->page->slug];
    }
}
