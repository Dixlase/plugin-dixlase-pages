{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

Dixlase Pages is dual-licensed. You may use this file under either:

  (a) the GNU General Public License version 3 or later, as published
      by the Free Software Foundation; or

  (b) a commercial license agreement obtained from exc-D inc.

Unless you have entered into a commercial license agreement, this
file is governed by the GPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

{{-- Parent page selector. Lets an editor place this page under another
     page so its public URL becomes /page/{parent}/{slug}. The candidate
     list comes from prepareFormData() and already excludes the current
     page and its descendants (cycle prevention) along with anything that
     would push the subtree past the depth limit. --}}
<div class="mb-6">
    <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        {{ __('dixlase-pages::admin/pages/form.parent_id') }}
    </label>
    <select
        id="parent_id"
        name="parent_id"
        class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
    >
        <option value="">{{ __('dixlase-pages::admin/pages/form.parent_id_top_level') }}</option>
        @foreach($parentOptions as $optionId => $optionLabel)
            <option value="{{ $optionId }}" @selected((int) $parentValue === (int) $optionId)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ __('dixlase-pages::admin/pages/form.parent_id_help') }}
    </p>
</div>

<x-dixlase-pages::page-content-editor
    :title="$page->title ?? ''"
    :storageType="$page->storage_type?->slug() ?? 'database'"
    :editorType="$page->editor_type?->slug() ?? 'html'"
    :content="$content"
    :identifier="$page->slug ?? ''"
    :pageId="$page->id ?? null"
    :editorCardOptions="$editorCardOptions"
    :storageOptions="$storageOptions"
    :storageDescriptions="$storageDescriptions"
    :slugValue="$page->slug ?? ''"
    :slugBaseUrl="$slugBaseUrl"
    :statusValue="$statusValue"
    :statusOptions="$statusOptions"
    :publishedAtValue="$publishedAtValue"
    :isEditMode="$page->exists"
    :fileStorageBasePath="$fileStorageBasePath"
    :previewUrl="$previewUrl"
    :previewFrameUrl="$previewFrameUrl"
    :previewRenderUrl="$previewRenderUrl"
    :customCss="$customCss"
    :customJs="$customJs"
    :languageOptions="$languageOptions"
    :langValue="$langValue"
    :multilingualEnabled="$multilingualEnabled"
    :isSimpleMode="$isSimpleMode"
    :isAdvancedEditor="$isAdvancedEditor"
    :canPublish="$canPublish"
    :seoMetaEnabled="$seoMetaEnabled ?? false"
    :seoMeta="$seoMeta ?? null"
    :seoOgpMedia="$seoOgpMedia ?? null"
/>
