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
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Services\RevisionService;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;

/**
 * Create a page, write its files when it is stored as a file, and record the
 * first revision.
 */
class CreateDixlasePagesPageAction extends AbstractDixlasePagesPageAction
{
    public function __construct(
        protected readonly DixlasePagesPageContentService $contentService,
        protected readonly RevisionService $revisionService,
    ) {}

    protected function auditAction(): string
    {
        return 'dixlase_pages.page.created';
    }

    /**
     * File I/O happens alongside the DB write, which a transaction cannot
     * roll back.
     */
    protected function useTransaction(): bool
    {
        return false;
    }

    /**
     * @param  array<string, mixed>  $data  Validated DixlasePagesStorePageRequest input
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $storageTypeSlug = $data['storage_type'];
        $editorTypeSlug = $data['editor_type'];
        $content = $data['content'] ?? '';
        $customCss = $data['custom_css'] ?? '';
        $customJs = $data['custom_js'] ?? '';
        $lang = $data['lang'] ?? app()->getLocale();

        // For file save, also save to file
        if ($storageTypeSlug === 'file') {
            $this->contentService->saveToFile($data['slug'], $lang, $editorTypeSlug, $content);

            // Save CSS/JS files as well
            if (! empty($customCss)) {
                $this->contentService->saveCssToFile($data['slug'], $lang, $customCss);
            }
            if (! empty($customJs)) {
                $this->contentService->saveJsToFile($data['slug'], $lang, $customJs);
            }
        }

        // Convert from slug to enum instance (int-backed enums need conversion from slug)
        $storageType = ContentStorageType::tryFromSlug($storageTypeSlug) ?? ContentStorageType::DATABASE;
        $editorType = ContentEditorType::tryFromSlug($editorTypeSlug) ?? ContentEditorType::HTML;

        // Create page (always save content to DB as well = backup)
        $page = DixlasePagesPage::create([
            'parent_id' => $data['parent_id'] ?? null,
            'slug' => $data['slug'],
            'lang' => $lang,
            'title' => $data['title'] ?? null,
            'storage_type' => $storageType,
            'editor_type' => $editorType,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'content' => $content,
            'custom_css' => $customCss,
            'custom_js' => $customJs,
        ]);

        // Record revision (manual because it's an explicit save by user)
        $this->revisionService->record(
            $page->fresh() ?? $page,
            type: RevisionService::TYPE_MANUAL,
            userId: $actor->getActorId(),
        );

        return ActionResult::success(model: $page, label: $page->slug);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        /** @var DixlasePagesPage $page */
        $page = $result->model;

        return [
            'slug' => $page->slug,
            'status' => $page->status?->slug(),
            'storage_type' => $page->storage_type?->slug(),
            'editor_type' => $page->editor_type?->slug(),
        ];
    }
}
