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

namespace Plugins\DixlasePages\App\Http\Controllers\Admin;

use App\Actors\MemberActor;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Presenters\Admin\RevisionDiffPresenter;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\DixlasePages\App\Actions\Page\RestoreDixlasePagesPageRevisionAction;
use Plugins\DixlasePages\App\Actions\Page\ToggleDixlasePagesPageRevisionProtectionAction;
use Plugins\DixlasePages\App\Actions\Page\UpdateDixlasePagesPageRevisionNoteAction;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageRevision;

/**
 * Page revision list, diff display, and restore controller
 */
class DixlasePagesAdminPageRevisionController extends AdminLoggedInController
{
    public function __construct(
        protected RevisionService $revisionService,
        protected RevisionDiffPresenter $diffPresenter,
    ) {
        parent::__construct();
    }

    /**
     * Revision list
     */
    public function index(DixlasePagesPage $page): View
    {
        $this->setDescription(__('dixlase-pages::admin/pages/revisions.index.description'));

        $revisions = $page->revisions()->with('creator')->paginate(20);

        $this->applyBreadcrumbs($page);

        $this->viewParams['page'] = $page;
        $this->viewParams['revisions'] = $revisions;
        $this->viewParams['typeLabels'] = $this->typeLabels();
        $this->viewParams['retention'] = $this->revisionService->getRetentionCount();
        $this->viewParams['protectedCount'] = $this->revisionService->countProtected($page);

        return view('dixlase-pages::admin.pages.revisions.index', $this->viewParams);
    }

    /**
     * Revision details (diff display with current version)
     */
    public function show(DixlasePagesPage $page, int $id): View
    {
        $revision = DixlasePagesPageRevision::query()
            ->where('page_id', $page->id)
            ->with('creator')
            ->findOrFail($id);

        $currentSnapshot = $this->revisionService->buildSnapshot($page);
        $revisionSnapshot = $revision->snapshot;

        $fields = ['title', 'slug', 'content', 'custom_css', 'custom_js'];
        $diffs = [];
        foreach ($fields as $field) {
            $left = (string) ($revisionSnapshot[$field] ?? '');
            $right = (string) ($currentSnapshot[$field] ?? '');
            if (! $this->diffPresenter->hasChanges($left, $right)) {
                continue;
            }
            $diffs[$field] = $this->diffPresenter->buildSideBySide($left, $right);
        }

        $metaDiffs = [];
        foreach (['lang', 'storage_type', 'editor_type', 'status', 'published_at'] as $field) {
            $left = $revisionSnapshot[$field] ?? null;
            $right = $currentSnapshot[$field] ?? null;
            if ($left !== $right) {
                $metaDiffs[$field] = ['old' => $left, 'new' => $right];
            }
        }

        $this->applyBreadcrumbs($page, $revision->id);

        $this->viewParams['page'] = $page;
        $this->viewParams['revision'] = $revision;
        $this->viewParams['diffs'] = $diffs;
        $this->viewParams['metaDiffs'] = $metaDiffs;
        $this->viewParams['hasChanges'] = ! empty($diffs) || ! empty($metaDiffs);
        $this->viewParams['typeLabels'] = $this->typeLabels();

        return view('dixlase-pages::admin.pages.revisions.show', $this->viewParams);
    }

    /**
     * Revision restore
     */
    public function restore(DixlasePagesPage $page, int $id): RedirectResponse
    {
        $revision = DixlasePagesPageRevision::query()
            ->where('page_id', $page->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new RestoreDixlasePagesPageRevisionAction($revision, $this->revisionService))
            ->execute($actor, []);

        return redirect()
            ->route('dixlase-pages::admin.pages.revisions.index', $page)
            ->with('success', __('dixlase-pages::admin/pages/revisions.restore_success'));
    }

    /**
     * Toggle the protection flag of a revision
     */
    public function toggleProtection(DixlasePagesPage $page, int $id): RedirectResponse
    {
        $revision = DixlasePagesPageRevision::query()
            ->where('page_id', $page->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new ToggleDixlasePagesPageRevisionProtectionAction($revision))
            ->execute($actor, []);

        return back()->with(
            'success',
            $revision->fresh()->is_protected
                ? __('dixlase-pages::admin/pages/revisions.protect_enabled')
                : __('dixlase-pages::admin/pages/revisions.protect_disabled')
        );
    }

    /**
     * Update the memo of a revision
     */
    public function updateNote(Request $request, DixlasePagesPage $page, int $id): RedirectResponse
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $revision = DixlasePagesPageRevision::query()
            ->where('page_id', $page->id)
            ->findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());
        (new UpdateDixlasePagesPageRevisionNoteAction($revision))
            ->execute($actor, $data);

        return redirect()
            ->route('dixlase-pages::admin.pages.revisions.show', ['page' => $page, 'id' => $revision->id])
            ->with('success', __('dixlase-pages::admin/pages/revisions.note_updated'));
    }

    /**
     * Set up breadcrumb list (revision routes require the {page} parameter)
     */
    private function applyBreadcrumbs(DixlasePagesPage $page, ?int $revisionId = null): void
    {
        $pageLabel = $page->title ?: $page->slug;
        $breadcrumbs = [
            ['label' => __('dixlase-pages::admin/navigation.pages.index'), 'route' => 'dixlase-pages::admin.pages.index'],
            ['label' => $pageLabel, 'route' => 'dixlase-pages::admin.pages.edit', 'params' => ['page' => $page->id]],
        ];

        if ($revisionId !== null) {
            $breadcrumbs[] = [
                'label' => __('dixlase-pages::admin/pages/revisions.index.heading'),
                'route' => 'dixlase-pages::admin.pages.revisions.index',
                'params' => ['page' => $page->id],
            ];
            $breadcrumbs[] = ['label' => __('dixlase-pages::admin/pages/revisions.show.heading')];
        } else {
            $breadcrumbs[] = ['label' => __('dixlase-pages::admin/pages/revisions.index.heading')];
        }

        $this->viewParams['breadcrumbs'] = $breadcrumbs;
    }

    /**
     * @return array<string, string>
     */
    private function typeLabels(): array
    {
        return [
            DixlasePagesPageRevision::TYPE_AUTO => __('dixlase-pages::admin/pages/revisions.type_auto'),
            DixlasePagesPageRevision::TYPE_MANUAL => __('dixlase-pages::admin/pages/revisions.type_manual'),
            DixlasePagesPageRevision::TYPE_RESTORE_BACKUP => __('dixlase-pages::admin/pages/revisions.type_restore_backup'),
        ];
    }
}
