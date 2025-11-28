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
use App\Helpers\LocaleHelper;
use Plugins\DixlasePages\App\Models\PageSetting;

// 翻訳データの準備
$translations = [];
// ファイルコンテンツがコントローラーから渡されていない場合は空配列
$fileContents = $fileContents ?? [];

if (isset($page) && $page->exists) {
    foreach (LocaleHelper::supportedLocales() as $locale) {
        $translation = $page->translate($locale);
        // ファイル保存の場合はファイルコンテンツを優先、なければ翻訳データを使用
        $content = $fileContents[$locale] ?? ($translation->content ?? '');
        $translations[$locale] = [
            'title' => old("translations.{$locale}.title", $translation->title ?? ''),
            'content' => old("translations.{$locale}.content", $content),
            'meta_description' => old("translations.{$locale}.meta_description", $translation->meta_description ?? ''),
            'ogp_image_id' => old("translations.{$locale}.ogp_image_id", $translation->ogp_image_id ?? ''),
        ];
    }
} else {
    // 新規作成時は空の配列
    foreach (LocaleHelper::supportedLocales() as $locale) {
        $translations[$locale] = [
            'title' => old("translations.{$locale}.title", ''),
            'content' => old("translations.{$locale}.content", ''),
            'meta_description' => old("translations.{$locale}.meta_description", ''),
            'ogp_image_id' => old("translations.{$locale}.ogp_image_id", ''),
        ];
    }
}

// ステータス値の取得
$statusValue = old('status', isset($page) && $page->exists ? $page->status->value : 'draft');
$publishedAtValue = old('published_at', isset($page) && $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : '');

// ページディレクトリ設定を取得
$pagesDirectory = PageSetting::getValue('pages_directory', config('custom.pages_directory', 'pages'));
@endphp

{{-- 
フォーム順序:
1. 言語タブ
2. タイトル
3. エディタータイプ
4. コンテンツ
5. スラッグ
6. 保存方法
7. OGP
8. 状態
--}}

<!-- 多言語コンテンツエディタ（すべてのフィールドを含む） -->
<x-multilingual-content-editor
    :storageType="old('storage_type', $page->storage_type ?? 'database')"
    :editorType="old('editor_type', $page->editor_type ?? 'html')"
    :translations="$translations"
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
    @foreach(['slug', 'storage_type', 'editor_type', 'status', 'published_at'] as $field)
        @if($errors->has($field))
            @include('components::form.error', ['messages' => $errors->get($field)])
        @endif
    @endforeach
    @foreach(LocaleHelper::supportedLocales() as $locale)
        @foreach(['title', 'content', 'meta_description', 'ogp_image_id'] as $field)
            @if($errors->has("translations.{$locale}.{$field}"))
                @include('components::form.error', ['messages' => $errors->get("translations.{$locale}.{$field}")])
            @endif
        @endforeach
    @endforeach
</div>
@endif
