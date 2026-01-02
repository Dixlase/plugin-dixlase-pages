{{--
This file is part of DixlasePages.

Copyright (C) 2025 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@php
use Plugins\DixlasePages\App\Models\PageSetting;

// ステータス値の取得
$statusValue = old('status', isset($page) && $page->exists ? $page->status->value : 'draft');
$publishedAtValue = old('published_at', isset($page) && $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '');

// ページディレクトリ設定を取得
$pagesDirectory = PageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));

// コンテンツ取得（ファイル保存の場合はファイルから）
$content = $fileContents ?? ($page->getContentByEditorType() ?? '');
@endphp

{{-- 
フォーム順序:
1. タイトル
2. エディタータイプ
3. コンテンツ
4. スラッグ
5. 保存方法
6. OGP
7. 状態
--}}

<!-- コンテンツエディタ（単一言語） -->
<x-content-editor
    :storageType="old('storage_type', $page->storage_type ?? 'database')"
    :editorType="old('editor_type', $page->editor_type ?? 'html')"
    :title="old('title', $page->title ?? '')"
    :content="old('content', $content)"
    :metaDescription="old('meta_description', $page->meta_description ?? '')"
    :ogpImageId="old('ogp_image_id', $page->ogp_image_id ?? '')"
    :identifier="$page->slug ?? ''"
    :pageId="$page->id ?? null"
    :showMetaDescription="true"
    :showOgpImage="true"
    :showSlug="true"
    :slugValue="old('slug', $page->slug ?? '')"
    :showStatus="true"
    :statusValue="$statusValue"
    :publishedAtValue="$publishedAtValue"
    :pagesDirectory="$pagesDirectory"
/>

<!-- バリデーションエラー表示 -->
@if($errors->any())
<div class="mt-4">
    @foreach(['title', 'content', 'slug', 'storage_type', 'editor_type', 'status', 'published_at', 'meta_description', 'ogp_image_id'] as $field)
        @if($errors->has($field))
            @include('components::form.error', ['messages' => $errors->get($field)])
        @endif
    @endforeach
</div>
@endif
