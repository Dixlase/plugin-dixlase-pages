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
use App\Contracts\TranslationResolver;
use App\DTO\Action\ActionResult;
use App\Enums\ContentStorageType;
use App\Services\RevisionService;
use Illuminate\Support\Facades\App;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;

/**
 * Update a page: rename or rewrite its files, keep the DB copy and any
 * existing translation overlay in step, and record a revision.
 */
class UpdateDixlasePagesPageAction extends AbstractDixlasePagesPageAction
{
    /**
     * Attributes whose change is recorded in the audit context, by name only.
     * Values stay out of the log: content can be large and is already kept
     * in the revision history.
     */
    private const AUDITED_ATTRIBUTES = [
        'parent_id', 'slug', 'lang', 'title', 'storage_type', 'status',
        'published_at', 'content', 'custom_css', 'custom_js',
    ];

    /** @var list<string> */
    private array $changed = [];

    private string $oldSlug;

    public function __construct(
        protected readonly DixlasePagesPage $page,
        protected readonly DixlasePagesPageContentService $contentService,
        protected readonly RevisionService $revisionService,
    ) {}

    protected function auditAction(): string
    {
        return 'dixlase_pages.page.updated';
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
     * @param  array<string, mixed>  $data  Validated DixlasePagesUpdatePageRequest input
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $page = $this->page;

        // editor_type maintains the existing model value (cannot be changed during editing)
        $editorTypeSlug = $page->editor_type->slug();
        $newStorageType = ContentStorageType::tryFromSlug($data['storage_type']) ?? ContentStorageType::DATABASE;

        $this->oldSlug = $page->slug;
        $oldStorageType = $page->storage_type;
        $locale = app()->getLocale();

        // If slug is changed, rename the file
        if ($oldStorageType === ContentStorageType::FILE && $this->oldSlug !== $data['slug']) {
            $this->contentService->renameFile($this->oldSlug, $data['slug'], $editorTypeSlug, $locale);
            // Rename CSS/JS files as well
            $this->contentService->renameAssetFiles($this->oldSlug, $data['slug'], $locale);
        }

        $content = $data['content'] ?? '';
        $customCss = $data['custom_css'] ?? '';
        $customJs = $data['custom_js'] ?? '';

        // Handle storage method changes
        if ($oldStorageType !== $newStorageType) {
            if ($oldStorageType === ContentStorageType::FILE && $newStorageType === ContentStorageType::DATABASE) {
                // File→DB: Delete file (no need to load since DB always has backup)
                $this->contentService->deleteFile($data['slug'], $locale, $editorTypeSlug);
                $this->contentService->deleteCssFile($data['slug'], $locale);
                $this->contentService->deleteJsFile($data['slug'], $locale);
            }
        }

        // For file save, also save to file
        if ($newStorageType === ContentStorageType::FILE) {
            $this->contentService->saveToFile($data['slug'], $locale, $editorTypeSlug, $content);

            // Save CSS/JS files as well
            if (! empty($customCss)) {
                $this->contentService->saveCssToFile($data['slug'], $locale, $customCss);
            } else {
                $this->contentService->deleteCssFile($data['slug'], $locale);
            }
            if (! empty($customJs)) {
                $this->contentService->saveJsToFile($data['slug'], $locale, $customJs);
            } else {
                $this->contentService->deleteJsFile($data['slug'], $locale);
            }
        }

        // Update page (always save content to DB as backup)
        $page->update([
            'parent_id' => $data['parent_id'] ?? null,
            'slug' => $data['slug'],
            'lang' => $data['lang'] ?? app()->getLocale(),
            'title' => $data['title'] ?? null,
            'storage_type' => $newStorageType,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? null,
            'content' => $content,
            'custom_css' => $customCss,
            'custom_js' => $customJs,
        ]);

        $this->changed = array_values(array_intersect(
            self::AUDITED_ATTRIBUTES,
            array_keys($page->getChanges()),
        ));

        // Keep any existing multilingual translation overlay in sync with the
        // column write. Without this, an overlay row that already exists for
        // the current locale (e.g. one the operator authored via the
        // translation manager earlier) shadows the column on display, so
        // edits made here would silently "revert" the moment the page is
        // re-rendered. We only touch overlays that already exist — new ones
        // are still owned by the translation manager, so a fresh page won't
        // suddenly grow overlay rows just because someone saved it once.
        if (App::bound(TranslationResolver::class)) {
            $resolver = app(TranslationResolver::class);
            $titleValue = $data['title'] ?? '';
            foreach ([['title', $titleValue], ['content', $content]] as [$field, $value]) {
                if ($resolver->exists($page, $field, $locale)) {
                    $resolver->store($page, $field, $value, $locale);
                }
            }
        }

        // Record revision (manual because it's an explicit save by user)
        // Skipped if there are no changes from the previous revision
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

        $context = [
            'changed' => $this->changed,
            'status' => $page->status?->slug(),
            'storage_type' => $page->storage_type?->slug(),
        ];

        if ($this->oldSlug !== $page->slug) {
            $context['previous_slug'] = $this->oldSlug;
        }

        return $context;
    }
}
