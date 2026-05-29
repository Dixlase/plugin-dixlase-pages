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

@props([
    // 親ページ候補オプション (id => 表示ラベル)
    'parentOptions' => [],
    // 現在選択中の親ページ id (null = トップレベル)
    'parentValue' => null,
    // 親 id → 公開 URL パス (id => "ancestor-slug/.../parent-slug")
    // URL プレビューが親階層を含めるために使う。
    'parentPaths' => [],
    // タイトル
    'title' => '',
    // エディター & ストレージ
    'storageType' => 'database',
    'editorType' => 'html',
    'content' => '',
    'identifier' => '',
    'editorCardOptions' => [],
    'storageOptions' => [],
    'storageDescriptions' => [],
    // スラッグ
    'slugValue' => '',
    'slugBaseUrl' => '',
    // ステータス
    'statusValue' => 'draft',
    'statusOptions' => [],
    'publishedAtValue' => '',
    // ページID（API用）
    'pageId' => null,
    // 編集モードフラグ
    'isEditMode' => false,
    // ファイル保存時の表示用ベースパス
    'fileStorageBasePath' => '',
    // プレビューURL（別タブ）
    'previewUrl' => '',
    // iframeプレビューフレームURL
    'previewFrameUrl' => '',
    // サーバーサイドレンダリングURL
    'previewRenderUrl' => '',
    // カスタムCSS/JS
    'customCss' => '',
    'customJs' => '',
    // 言語オプション
    'languageOptions' => [],
    'langValue' => '',
    // Whether the multilingual plugin is active; toggles language UI visibility.
    'multilingualEnabled' => false,
    // 簡単モード
    'isSimpleMode' => false,
    'isAdvancedEditor' => false,
    // GUI editor plugin info
    'guiEditorInfo' => null,
    'guiEditorAssetHtml' => '',
    'hasGuiEditor' => false,
    // SEOメタ（SEOプラグイン連携、optional依存）
    'seoMetaEnabled' => false,
    'seoMeta' => null,
    'seoOgpMedia' => null,
])

{{--
Layout:
+----------------------------------+---+----------------+
| Main Content                     |[>]| Right Sidebar  |
|  Title                           |   | Slug           |
|  Editor Type (radio cards)       |   | Storage Type   |
|  Content Editor                  |   | Publish        |
|                                  |   |                |
+----------------------------------+---+----------------+
--}}

<div x-data="pageEditor({
        storageType: '{{ old('storage_type', $storageType) }}',
        editorType: '{{ old('editor_type', $editorType) }}',
        content: @js(old('content', $content)),
        identifier: @js($identifier),
        status: '{{ $statusValue }}',
        slug: @js(old('slug', $slugValue)),
        publishedAt: '{{ $publishedAtValue }}',
        slugBaseUrl: '{{ $slugBaseUrl }}',
        parentId: @js(old('parent_id', $parentValue)),
        parentPaths: @js($parentPaths),
        isEditMode: {{ $isEditMode ? 'true' : 'false' }},
        fileStorageBasePath: '{{ $fileStorageBasePath }}',
        previewFrameUrl: '{{ $previewFrameUrl }}',
        previewRenderUrl: '{{ $previewRenderUrl }}',
        customCss: @js(old('custom_css', $customCss)),
        customJs: @js(old('custom_js', $customJs)),
        lang: '{{ $langValue }}',
        isSimpleMode: {{ $isSimpleMode ? 'true' : 'false' }},
        isAdvancedEditor: {{ $isAdvancedEditor ? 'true' : 'false' }},
        hasGuiEditor: {{ $hasGuiEditor ? 'true' : 'false' }}
     })">

        {{-- ===== Main Content Area ===== --}}

        {{-- 1. Editor Type Selection --}}
        <div class="mb-6">
            @if ($isEditMode)
                {{-- Edit mode: Show preview toggle only (editor type, language, storage format are shown in right column meta info) --}}
                <input type="hidden" name="editor_type" value="{{ $editorType }}">
                @if($previewFrameUrl)
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <button type="button" @click="togglePreview()"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border transition-colors"
                            :class="previewVisible
                                ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300'"
                            :title="previewVisible ? '{{ __('components/content-editor.preview_hide') }}' : '{{ __('components/content-editor.preview_show') }}'">
                            <i class="fas" :class="previewVisible ? 'fa-eye' : 'fa-eye-slash'"></i>
                            <span x-text="previewVisible ? '{{ __('components/content-editor.preview_hide') }}' : '{{ __('components/content-editor.preview_show') }}'"></span>
                        </button>
                    </div>
                @endif
            @else
                {{-- Create mode: Editor type radio card selection --}}
                @include('components::form-label', [
                    'for' => 'editor_type',
                    'text' => __('common.content_editor.label'),
                ])
                <x-form-radio-card-group
                    name="editor_type"
                    :options="$editorCardOptions"
                    :value="old('editor_type', $editorType)"
                    :columns="3"
                    xModel="editorType"
                />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('dixlase-pages::components/page-content-editor.editor_type_help') }}
                </p>
                @include('components::form-error', ['name' => 'editor_type'])
            @endif

            {{-- Preview toggle button (create mode) --}}
            @if($previewFrameUrl && !$isEditMode)
                <div class="mt-3">
                    <button type="button" @click="togglePreview()"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md border transition-colors text-xs"
                        :class="previewVisible
                            ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-700'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:text-gray-700 dark:hover:text-gray-300'"
                        :title="previewVisible ? '{{ __('components/content-editor.preview_hide') }}' : '{{ __('components/content-editor.preview_show') }}'">
                        <i class="fas" :class="previewVisible ? 'fa-eye' : 'fa-eye-slash'"></i>
                        <span x-text="previewVisible ? '{{ __('components/content-editor.preview_hide') }}' : '{{ __('components/content-editor.preview_show') }}'"></span>
                    </button>
                </div>
            @endif
        </div>

        {{-- 3. Content Editor (split pane) --}}
        <div x-ref="splitContainer"
             class="flex gap-4 overflow-hidden"
             :class="[
                 isHorizontal ? 'flex-row' : 'flex-col',
                 (isDragging || isResizingPreview) ? 'select-none' : ''
             ]">

            {{-- Editor pane --}}
            <div x-ref="editorPane"
                 class="w-full min-w-0"
                 :class="isHorizontal && previewVisible ? 'overflow-y-auto' : ''"
                 :style="isHorizontal && previewVisible ? { width: (splitRatio * 100) + '%', maxHeight: 'calc(100vh - 160px)' } : {}">

                <div class="space-y-4">
                    @include('components::form-label', [
                        'for' => 'content',
                        'text' => __('common.content'),
                    ])

                    {{-- GUI editor --}}
                    <div x-show="editorType === 'gui'" x-cloak>
                        @if($guiEditorInfo)
                            @include($guiEditorInfo->viewName, [
                                'contentFieldName' => 'content',
                                'editorInfo' => $guiEditorInfo,
                            ])
                        @else
                            <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                                <i class="fas fa-paint-brush text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ __('common.content_editor.gui_coming_soon') }}
                                </p>
                            </div>
                            <input type="hidden" name="content" x-model="content">
                        @endif
                    </div>

                    {{-- Text editor (common for HTML / Markdown) --}}
                    <div x-show="editorType !== 'gui'" x-cloak>
                        @if(!$isSimpleMode)
                            {{-- Tab navigation (HTML editor only) --}}
                            <nav x-show="editorType === 'html'" class="flex border-b border-gray-200 dark:border-gray-600 mb-4" role="tablist">
                                <button type="button"
                                        @click="activeTab = 'content'"
                                        :class="activeTab === 'content'
                                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                                        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'content'">
                                    <i class="fas fa-code mr-1"></i> {{ __('dixlase-pages::components/page-content-editor.tab_content') }}
                                </button>
                                <button type="button"
                                        @click="activeTab = 'css'"
                                        :class="activeTab === 'css'
                                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                                        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'css'">
                                    <i class="fab fa-css3-alt mr-1"></i> {{ __('dixlase-pages::components/page-content-editor.tab_css') }}
                                </button>
                                <button type="button"
                                        @click="activeTab = 'js'"
                                        :class="activeTab === 'js'
                                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                                        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                                        role="tab"
                                        :aria-selected="activeTab === 'js'">
                                    <i class="fab fa-js mr-1"></i> {{ __('dixlase-pages::components/page-content-editor.tab_js') }}
                                </button>
                            </nav>
                        @endif

                        {{-- Content textarea --}}
                        <div x-show="activeTab === 'content' || editorType !== 'html'" role="tabpanel">
                            <x-form-textarea
                                id="content"
                                name="content"
                                :value="$content"
                                rows="6"
                                class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500"
                                xModel="content"
                            />
                        </div>

                        @if(!$isSimpleMode)
                            {{-- CSS tab (HTML editor only) --}}
                            <div x-show="activeTab === 'css' && editorType === 'html'" x-cloak role="tabpanel">
                                <x-form-textarea
                                    id="custom_css"
                                    name="custom_css"
                                    :value="$customCss"
                                    rows="6"
                                    :placeholder="__('dixlase-pages::components/page-content-editor.css_placeholder')"
                                    class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                                    xModel="customCss"
                                    data-auto-resize
                                />
                            </div>

                            {{-- JS tab (HTML editor only) --}}
                            <div x-show="activeTab === 'js' && editorType === 'html'" x-cloak role="tabpanel">
                                <x-form-textarea
                                    id="custom_js"
                                    name="custom_js"
                                    :value="$customJs"
                                    rows="6"
                                    :placeholder="__('dixlase-pages::components/page-content-editor.js_placeholder')"
                                    class="font-mono text-sm !bg-gray-950 !text-gray-200 !border-gray-600 focus:!border-blue-500 !overflow-hidden !resize-none"
                                    xModel="customJs"
                                    data-auto-resize
                                />
                            </div>
                        @endif
                    </div>

                    @include('components::form-error', ['name' => 'content'])
                </div>
            </div>

            {{-- Preview pane (edit mode only) --}}
            @if($previewFrameUrl)
                @include('components.content-editor.preview-pane')
            @endif
        </div>

        {{-- Scroll button (edit mode only) --}}
        @if($previewFrameUrl)
            @include('components.content-editor.scroll-buttons')
        @endif

        {{-- ===== Right Sidebar ===== --}}
        <x-admin.right-sidebar
            :openLabel="__('dixlase-pages::components/page-content-editor.sidebar_open')"
            :closeLabel="__('dixlase-pages::components/page-content-editor.sidebar_close')"
            panelClass="page-editor-right-sidebar"
        >

            {{-- Simple mode notice --}}
            @if($isSimpleMode)
                <x-ui-message type="info" :message="__('dixlase-pages::components/page-content-editor.simple_mode_notice')" textSize="text-xs" />
            @endif

            {{-- Advanced editor notice (HTML/Blade page in simple mode) --}}
            @if($isSimpleMode && $isAdvancedEditor)
                <x-ui-message type="warning" :message="__('dixlase-pages::components/page-content-editor.advanced_editor_notice')" textSize="text-xs" />
            @endif

            {{-- Preview in new tab --}}
            <x-content-editor.new-tab-preview :url="$previewUrl" />

            {{-- Meta info (edit mode: placed directly below preview, above title) --}}
            @if($isEditMode)
                @php
                    $currentCard = collect($editorCardOptions)->firstWhere('value', $editorType);
                @endphp
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        {{ __('dixlase-pages::components/page-content-editor.meta_section') }}
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            <i class="{{ $currentCard['icon'] ?? 'fas fa-file' }}" style="color: {{ $editorColors[$editorType] ?? 'gray' }}"></i>
                            {{ $currentCard['label'] ?? $editorType }}
                        </span>
                        @if($multilingualEnabled)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                <i class="fas fa-globe"></i>
                                {{ $languageOptions[$langValue] ?? $langValue }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                            <i class="fas {{ $storageType === 'file' ? 'fa-file-code' : 'fa-database' }} text-gray-500 dark:text-gray-400"></i>
                            {{ $storageOptions[$storageType] ?? $storageType }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('dixlase-pages::components/page-content-editor.meta_locked_help') }}
                    </p>

                    {{-- In edit mode, send meta info via hidden input --}}
                    <input type="hidden" name="storage_type" value="{{ $storageType }}">
                    <input type="hidden" name="lang" value="{{ $langValue }}">

                    {{-- Display info for file storage --}}
                    @if($storageType === 'file')
                        <div class="mt-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                <div class="flex-1">
                                    <div class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                                        {{ __('common.content_storage.file_info_title') }}
                                    </div>
                                    <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                        <p>{{ __('common.content_storage.file_info_description') }}</p>
                                        <p class="font-mono text-xs bg-white dark:bg-gray-800 p-2 rounded mt-2 break-all" x-text="filePath"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Title --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dixlase-pages::components/page-content-editor.title') }}<x-form-required-badge />
                </label>
                @include('components::form-text', [
                    'name' => 'title',
                    'value' => $title,
                    'placeholder' => __('dixlase-pages::components/page-content-editor.title_placeholder'),
                ])
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('dixlase-pages::components/page-content-editor.title_required_help') }}
                </p>
                @include('components::form-error', ['name' => 'title'])
            </div>

            {{-- 4. Slug --}}
            @if($isSimpleMode && !$isEditMode)
                {{-- Simple mode (new page): slug auto-generated from title --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('dixlase-pages::components/page-content-editor.slug_auto_generated') }}
                    </p>
                    @include('components::form-error', ['name' => 'slug'])
                </div>
            @else
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    @include('components::form-label', [
                        'for' => 'slug',
                        'text' => __('dixlase-pages::components/page-content-editor.slug'),
                    ])
                    @include('components::form-text', [
                        'name' => 'slug',
                        'value' => $slugValue,
                        'xModel' => 'slug',
                    ])
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('dixlase-pages::components/page-content-editor.slug_help') }}
                    </p>
                    {{-- URL Preview --}}
                    <div class="mt-2 text-sm" x-show="slug" x-cloak>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('dixlase-pages::components/page-content-editor.slug_url_preview') }}:</span>
                        @if ($isEditMode)
                            <a :href="pageUrl" target="_blank" rel="noopener noreferrer"
                               class="font-mono text-blue-600 dark:text-blue-400 break-all hover:underline"
                               x-text="pageUrl"></a>
                        @else
                            <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="pageUrl"></span>
                        @endif
                    </div>
                    @include('components::form-error', ['name' => 'slug'])
                </div>
            @endif

            {{-- 4.5 SEO Meta settings (shown only when SEO plugin is enabled and seo-meta capability is declared) --}}
            {{-- <x-dynamic-component> resolves components at runtime, so it's safe even when the SEO plugin is disabled --}}
            @if($seoMetaEnabled)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <x-dynamic-component
                        component="dixlase-seo::meta-fields"
                        :description="$seoMeta?->description"
                        :ogpMediaId="$seoMeta?->ogpMediaId"
                        :ogpMedia="$seoOgpMedia"
                    />
                </div>
            @endif

            {{-- 5. Save Method (creation only: when editing, integrated into the meta info section below preview) --}}
            @if($isSimpleMode)
                {{-- Simple mode: save format is fixed via hidden input --}}
                <input type="hidden" name="storage_type" value="database">
            @elseif(! $isEditMode)
                {{-- On creation: save format is selectable --}}
                <div>
                    @include('components::form-label', [
                        'for' => 'storage_type',
                        'text' => __('common.content_storage.label'),
                    ])

                    <x-form-select
                        name="storage_type"
                        :options="$storageOptions"
                        :value="$storageType"
                        xModel="storageType"
                    />
                    <ul class="mt-2 space-y-1 text-sm text-gray-500 dark:text-gray-400">
                        @foreach ($storageDescriptions as $value => $description)
                            <li><span class="font-medium text-gray-700 dark:text-gray-300">{{ $storageOptions[$value] }}</span> — {{ $description }}</li>
                        @endforeach
                    </ul>

                    {{-- Display info for file storage --}}
                    <div x-show="isFileStorage" x-cloak class="mt-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <div class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                                    {{ __('common.content_storage.file_info_title') }}
                                </div>
                                <div class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                                    <p>{{ __('common.content_storage.file_info_description') }}</p>
                                    <p class="font-mono text-xs bg-white dark:bg-gray-800 p-2 rounded mt-2 break-all" x-text="filePath"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @include('components::form-error', ['name' => 'storage_type'])
                </div>
            @endif

            {{-- 6.5. Language Selection (creation only: when editing, integrated into meta info) --}}
            {{-- Hidden when the multilingual plugin is inactive: the site only --}}
            {{-- has one language so the picker would be misleading. The store --}}
            {{-- request fills `lang` from the site default in that case. --}}
            @if($multilingualEnabled)
                <div>
                    @include('components::form-label', [
                        'for' => 'lang',
                        'text' => __('dixlase-pages::components/page-content-editor.lang'),
                    ])

                    <x-form-select
                        name="lang"
                        :options="$languageOptions"
                        :value="$langValue"
                        xModel="lang"
                    />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if($isEditMode)
                            {{ __('dixlase-pages::components/page-content-editor.lang_edit_help') }}
                        @else
                            {{ __('dixlase-pages::components/page-content-editor.lang_help') }}
                        @endif
                    </p>
                    @include('components::form-error', ['name' => 'lang'])
                </div>
            @endif

            {{-- Revision History (edit only) --}}
            @if($isEditMode && $pageId)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('dixlase-pages::components/page-content-editor.revisions_section') }}
                    </h3>
                    <x-form-button
                        type="link"
                        icon="fas fa-clock-rotate-left"
                        variant="secondary"
                        size="sm"
                        :href="route('dixlase-pages::admin.pages.revisions.index', ['page' => $pageId])"
                        class="w-full"
                    >
                        {{ __('dixlase-pages::components/page-content-editor.revisions_button') }}
                    </x-form-button>
                    <x-form-help-text :text="__('dixlase-pages::components/page-content-editor.revisions_help')" />
                </div>
            @endif

            {{-- Parent page (hierarchy). Placed between revisions and publish
                 settings so the URL-shaping inputs sit together vertically.
                 The candidate list is built server-side and already excludes
                 the current page and its descendants (cycle prevention) plus
                 anything that would push the subtree past MAX_DEPTH. --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('dixlase-pages::admin/pages/form.parent_id') }}
                </label>
                <select
                    id="parent_id"
                    name="parent_id"
                    x-model="parentId"
                    class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">{{ __('dixlase-pages::admin/pages/form.parent_id_top_level') }}</option>
                    @foreach($parentOptions as $optionId => $optionLabel)
                        <option value="{{ $optionId }}">{{ $optionLabel }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('dixlase-pages::admin/pages/form.parent_id_help') }}
                </p>
                @include('components::form-error', ['name' => 'parent_id'])
            </div>

            {{-- 7. Public settings --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dixlase-pages::components/page-content-editor.publish_section') }}
                </h3>

                {{-- Status --}}
                <div class="mb-4">
                    @include('components::form-label', [
                        'for' => 'status',
                        'text' => __('dixlase-pages::components/page-content-editor.status'),
                    ])

                    <x-form-select
                        name="status"
                        :options="$statusOptions"
                        :value="$statusValue"
                        xModel="status"
                    />
                    @include('components::form-error', ['name' => 'status'])
                </div>

                {{-- Publication date/time (shown only for scheduled public) --}}
                <div x-show="status === 'scheduled'" x-cloak x-transition>
                    @include('components::form-label', [
                        'for' => 'published_at',
                        'text' => __('dixlase-pages::components/page-content-editor.published_at'),
                    ])
                    <input type="datetime-local"
                           id="published_at"
                           name="published_at"
                           x-model="publishedAt"
                           class="input-common input-full my-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('dixlase-pages::components/page-content-editor.published_at_help') }}
                    </p>
                    @include('components::form-error', ['name' => 'published_at'])
                </div>
            </div>

        </x-admin.right-sidebar>

</div>

@if($guiEditorAssetHtml)
    {!! $guiEditorAssetHtml !!}
@endif
