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

<x-dixlase-pages::page-content-editor
    :parentOptions="$parentOptions"
    :parentValue="$parentValue"
    :parentPaths="$parentPaths"
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
