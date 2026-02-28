{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc.
Website: https://exc-d.com

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
    :title="$page->title ?? ''"
    :storageType="$page->storage_type?->value ?? 'database'"
    :editorType="$page->editor_type?->value ?? 'html'"
    :content="$content"
    :identifier="$page->slug ?? ''"
    :pageId="$page->id ?? null"
    :editorTranslations="$editorTranslations"
    :storageOptions="$storageOptions"
    :storageDescriptions="$storageDescriptions"
    :editorIcons="$editorIcons"
    :editorColors="$editorColors"
    :slugValue="$page->slug ?? ''"
    :slugBaseUrl="$slugBaseUrl"
    :statusValue="$statusValue"
    :statusOptions="$statusOptions"
    :publishedAtValue="$publishedAtValue"
    :isEditMode="$page->exists"
    :fileStorageBasePath="$fileStorageBasePath"
    :previewUrl="$previewUrl"
/>
