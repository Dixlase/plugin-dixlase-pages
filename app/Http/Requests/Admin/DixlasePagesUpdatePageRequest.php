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

namespace Plugins\DixlasePages\App\Http\Requests\Admin;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Rules\UniqueContentSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;
use Plugins\DixlasePages\App\Services\ScriptAuthoringPolicy;

class DixlasePagesUpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // An HTML (or legacy Blade) body is script-capable: only members who
        // may author scripts may change such a page. See ScriptAuthoringPolicy.
        $page = $this->route('page');
        if ($page instanceof DixlasePagesPage) {
            return ScriptAuthoringPolicy::canEditEditorType(
                Auth::guard('member')->user(),
                $page->editor_type
            );
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        // Slug uniqueness is scoped by (parent_id, slug) so siblings
        // cannot collide; cycle/depth/self checks live in withValidator.
        $slugRule = UniqueContentSlug::for('plg_dixlase_pages')
            ->where('parent_id', $this->input('parent_id'));
        if ($pageId !== null) {
            $slugRule = $slugRule->ignore($pageId);
        }

        return [
            // Slug is required (existing slug is present during updates)
            // Exclude soft-deleted records from unique check
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                $slugRule,
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('plg_dixlase_pages', 'id')->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'storage_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStorageType::cases()))],
            'status' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStatus::cases()))],
            'published_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after_or_equal:now'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'lang' => ['required', 'string', 'max:10'],
            // SEO meta (optional dependency, validation passes even if SEO plugin is disabled)
            'seo_meta' => ['nullable', 'array'],
            'seo_meta.description' => ['nullable', 'string', 'max:300'],
            'seo_meta.ogp_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    /**
     * Additional checks after validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->title)) {
                $validator->errors()->add('title', __('dixlase-pages::admin/pages/validation.title_required'));
            }

            $parentId = $this->input('parent_id');
            $page = $this->route('page');
            if (! ($page instanceof \Plugins\DixlasePages\App\Models\DixlasePagesPage)) {
                return;
            }

            if ($parentId === null || $parentId === '') {
                return;
            }

            $parentId = (int) $parentId;

            // Self-parent: the page cannot be its own parent.
            if ($parentId === (int) $page->id) {
                $validator->errors()->add(
                    'parent_id',
                    __('dixlase-pages::admin/pages/validation.parent_self')
                );

                return;
            }

            // Cycle: parent must not live inside this page's own subtree,
            // otherwise the ancestors() walk would loop forever.
            if (in_array($parentId, $page->subtreeIds(), true)) {
                $validator->errors()->add(
                    'parent_id',
                    __('dixlase-pages::admin/pages/validation.parent_cycle')
                );

                return;
            }

            // Depth limit: moving the subtree under the new parent must
            // keep every descendant within MAX_DEPTH. The deepest
            // descendant's new depth equals parent.depth() + 1 + this
            // page's subtreeMaxDepth().
            $parent = \Plugins\DixlasePages\App\Models\DixlasePagesPage::find($parentId);
            if ($parent !== null) {
                $newDeepest = $parent->depth() + 1 + $page->subtreeMaxDepth();
                if ($newDeepest > \Plugins\DixlasePages\App\Models\DixlasePagesPage::MAX_DEPTH) {
                    $validator->errors()->add(
                        'parent_id',
                        __('dixlase-pages::admin/pages/validation.parent_depth_exceeded')
                    );
                }
            }
        });
    }

    /**
     * Processing before validation
     */
    protected function prepareForValidation(): void
    {
        $page = $this->route('page');
        if ($page instanceof DixlasePagesPage) {
            // Custom CSS / JS are only served for HTML-editor pages (the
            // editor type cannot change on update), so never store them on
            // any other page.
            if ($page->editor_type !== ContentEditorType::HTML) {
                $this->merge(['custom_css' => null, 'custom_js' => null]);
            }

            // Simple mode hides the file option: a database page stays in the
            // database. (An existing file page keeps its choice.)
            if (ScriptAuthoringPolicy::isSimpleMode() && $page->storage_type === ContentStorageType::DATABASE) {
                $this->merge(['storage_type' => ContentStorageType::DATABASE->slug()]);
            }
        }

        // The parent_id <select> emits "" for the "Top-level page" option.
        // Coerce that to null before validation so the `nullable|integer`
        // rule chain skips cleanly and $validated['parent_id'] holds null
        // (not ""), which the controller then passes straight to MySQL.
        // Empty strings here would otherwise produce "set parent_id = "
        // and trigger SQLSTATE 22007 on save.
        if ($this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }

        // <x-media.picker /> (from DixlaseSEO's <x-seo::meta-fields />)
        // submits 0 / "0" / "" when nothing is selected. The
        // `exists:media,id` rule then refuses 0 as a valid id and the
        // form rejects every save with "選択された seo meta.ogp media id は
        // 無効です", even though the field is supposed to be optional.
        // Normalise the "no selection" forms to null so the nullable
        // chain skips exists cleanly.
        $ogpId = $this->input('seo_meta.ogp_media_id');
        if ($ogpId === '' || $ogpId === '0' || $ogpId === 0) {
            $seoMeta = (array) $this->input('seo_meta', []);
            $seoMeta['ogp_media_id'] = null;
            $this->merge(['seo_meta' => $seoMeta]);
        }

        // If language is not specified, set the site settings language as default
        if (empty($this->lang)) {
            $this->merge(['lang' => app()->getLocale()]);
        }

        // Save format is locked after creation: overwrite with existing page value during edit
        $page = $this->route('page');
        if ($page instanceof \Plugins\DixlasePages\App\Models\DixlasePagesPage && $page->exists) {
            $this->merge(['storage_type' => $page->storage_type->slug()]);
        }

        // Public permission check: force status to draft for members without permission
        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);
        $member = Auth::guard('member')->user();
        if ($member && $member->role->value < $publishMinRole) {
            $this->merge([
                'status' => ContentStatus::DRAFT->slug(),
                'published_at' => null,
            ]);
        }

        // Clear published_at if not date-specified
        if ($this->status !== ContentStatus::SCHEDULED->slug()) {
            $this->merge(['published_at' => null]);
        }

        // Set current time if status is public
        if ($this->status === ContentStatus::PUBLISHED->slug()) {
            $this->merge(['published_at' => now()]);
        }
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'title.required' => __('dixlase-pages::admin/pages/validation.title_required'),
            'title.max' => __('dixlase-pages::admin/pages/validation.title_max'),
            'slug.regex' => __('dixlase-pages::admin/pages/validation.slug_format'),
            'content.required' => __('dixlase-pages::admin/pages/validation.content_required'),
            'status.required' => __('dixlase-pages::admin/pages/validation.status_required'),
            'published_at.date' => __('dixlase-pages::admin/pages/validation.published_at_date'),
            'published_at.required_if' => __('dixlase-pages::admin/pages/validation.published_at_required'),
            'published_at.after_or_equal' => __('dixlase-pages::admin/pages/validation.published_at_future'),
        ];
    }
}
