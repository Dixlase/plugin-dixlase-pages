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

@props([
    // タイトル
    'title' => '',
    // エディター & ストレージ
    'storageType' => 'database',
    'editorType' => 'html',
    'content' => '',
    'identifier' => '',
    'editorTranslations' => [],
    'storageOptions' => [],
    'storageDescriptions' => [],
    'editorIcons' => [],
    'editorColors' => [],
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
])

{{--
レイアウト:
+----------------------------------+---+----------------+
| Main Content                     |[>]| Right Sidebar  |
|  Title                           |   | Slug           |
|  Editor Type (radio cards)       |   | Storage Type   |
|  Content Editor                  |   | Publish        |
|                                  |   |                |
+----------------------------------+---+----------------+
--}}

<div data-translations='@json($editorTranslations)'
     x-data="pageEditor({
        storageType: '{{ old('storage_type', $storageType) }}',
        editorType: '{{ old('editor_type', $editorType) }}',
        content: @js(old('content', $content)),
        identifier: @js($identifier),
        status: '{{ $statusValue }}',
        slug: @js(old('slug', $slugValue)),
        publishedAt: '{{ $publishedAtValue }}',
        slugBaseUrl: '{{ $slugBaseUrl }}',
        isEditMode: {{ $isEditMode ? 'true' : 'false' }},
        fileStorageBasePath: '{{ $fileStorageBasePath }}'
     })">

        {{-- ===== メインコンテンツエリア ===== --}}
        <div class="space-y-6">

            {{-- 1. タイトル --}}
            <div>
                @include('components::form-label', [
                    'for' => 'title',
                    'text' => __('dixlase-pages::components/page-content-editor.title'),
                ])
                @include('components::form-text', [
                    'name' => 'title',
                    'value' => $title,
                    'placeholder' => __('dixlase-pages::components/page-content-editor.title_placeholder'),
                ])
                @include('components::form-error', ['name' => 'title'])
            </div>

            {{-- 2. エディタータイプ選択 --}}
            <div>
                @include('components::form-label', [
                    'for' => 'editor_type',
                    'text' => __('common.content_editor.label'),
                ])

                @if ($isEditMode)
                    {{-- 編集時: エディタータイプ表示（変更不可） --}}
                    <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                        <i class="{{ $editorIcons[$editorType] ?? 'fas fa-file' }} text-lg"
                           style="color: {{ $editorColors[$editorType] ?? 'gray' }}"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $editorTranslations["common.content_editor.{$editorType}"] ?? $editorType }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $editorTranslations["common.content_editor.{$editorType}_description"] ?? '' }}
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="editor_type" value="{{ $editorType }}">
                @else
                    {{-- 作成時: ラジオカード選択 --}}
                    <div x-data="{
                        editorIcons: {{ Js::from($editorIcons) }},
                        editorColors: {{ Js::from($editorColors) }},
                        editorOptions: [],
                        updateEditorOptions() {
                            this.editorOptions = this.availableEditors.map(editor => ({
                                value: editor,
                                label: this.$t(`common.content_editor.${editor}`),
                                description: this.$t(`common.content_editor.${editor}_description`),
                                icon: this.editorIcons[editor] || 'fas fa-file',
                                color: this.editorColors[editor] || 'gray',
                            }));
                        }
                    }" x-init="updateEditorOptions(); $watch('availableEditors', () => updateEditorOptions())">
                        <template x-if="editorOptions.length > 0">
                            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                                <template x-for="option in editorOptions" :key="option.value">
                                    <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none transition-all duration-150"
                                           :class="[
                                               editorType === option.value
                                                   ? 'border-blue-600 dark:border-blue-500 ring-3 ring-blue-600 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                                   : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-500'
                                           ]"
                                           @click="editorType = option.value">
                                        <input type="radio"
                                               name="editor_type"
                                               :value="option.value"
                                               x-model="editorType"
                                               class="sr-only">

                                        <span class="flex flex-1">
                                            <span class="flex flex-col justify-center">
                                                <span class="flex items-center gap-2 text-sm font-medium"
                                                      :class="editorType === option.value ? 'text-blue-700 dark:text-blue-300' : 'text-gray-900 dark:text-white'">
                                                    <i :class="option.icon"></i>
                                                    <span x-text="option.label"></span>
                                                </span>
                                                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="option.description"></span>
                                            </span>
                                        </span>

                                        <span class="absolute top-3 right-3 flex items-center justify-center"
                                              x-show="editorType === option.value"
                                              x-transition:enter="transition ease-out duration-100"
                                              x-transition:enter-start="opacity-0 scale-75"
                                              x-transition:enter-end="opacity-100 scale-100"
                                              x-transition:leave="transition ease-in duration-75"
                                              x-transition:leave-start="opacity-100 scale-100"
                                              x-transition:leave-end="opacity-0 scale-75">
                                            <i class="fas fa-check-circle text-lg text-blue-600 dark:text-blue-400"></i>
                                        </span>

                                        <span class="pointer-events-none absolute -inset-px rounded-lg"
                                              :class="editorType === option.value ? 'border-2 border-blue-600 dark:border-blue-500' : 'border border-transparent'"
                                              aria-hidden="true"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                @endif

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    {{ __('dixlase-pages::components/page-content-editor.editor_type_help') }}
                </p>
                @include('components::form-error', ['name' => 'editor_type'])
            </div>

            {{-- 3. コンテンツエディタ --}}
            <div>
                @include('components::form-label', [
                    'for' => 'content',
                    'text' => __('common.content'),
                ])

                {{-- GUI エディタ（将来実装） --}}
                <div x-show="editorType === 'gui'" x-cloak>
                    <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-center">
                        <i class="fas fa-magic text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400">
                            {{ __('common.content_editor.gui_coming_soon') }}
                        </p>
                    </div>
                    <input type="hidden" name="content" x-model="content">
                </div>

                {{-- Markdown エディタ --}}
                <div x-show="editorType === 'markdown'" x-cloak>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('common.content_editor.markdown_editor') }}
                            </div>
                            @include('components::form-textarea', [
                                'id' => 'content_markdown',
                                'name' => 'content',
                                'value' => $content,
                                'class' => 'min-h-96 font-mono text-sm',
                                'xModel' => 'content',
                            ])
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('common.content_editor.preview') }}
                            </div>
                            <div class="min-h-96 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 prose dark:prose-invert max-w-none overflow-auto"
                                 x-html="marked.parse(content || '')"></div>
                        </div>
                    </div>
                </div>

                {{-- HTML エディタ --}}
                <div x-show="editorType === 'html'" x-cloak>
                    @include('components::form-textarea', [
                        'id' => 'content_html',
                        'name' => 'content',
                        'value' => $content,
                        'class' => 'min-h-96 font-mono text-sm',
                        'xModel' => 'content',
                    ])
                </div>

                {{-- Blade エディタ --}}
                <div x-show="editorType === 'blade'" x-cloak>
                    @include('components::form-textarea', [
                        'id' => 'content_blade',
                        'name' => 'content',
                        'value' => $content,
                        'class' => 'min-h-96 font-mono text-sm',
                        'xModel' => 'content',
                    ])
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                        {{ __('common.content_editor.blade_warning') }}
                    </div>
                </div>

                @include('components::form-error', ['name' => 'content'])
            </div>

        </div>

        {{-- ===== 右サイドバートグルボタン（Desktop のみ） ===== --}}
        <button type="button"
                @click="toggleRightSidebar()"
                class="hidden lg:flex fixed top-14 right-0 z-40 items-center backdrop-blur-sm dark:bg-gray-900/75 bg-white/75 text-blue-400 dark:text-white px-1.5 py-4 rounded-l-lg shadow-md border border-r-0 border-gray-300 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                :class="{
                    'translate-x-0': rightSidebarCollapsed,
                    '-translate-x-80': !rightSidebarCollapsed
                }"
                :style="rightSidebarReady ? 'transition: transform 200ms ease-in-out' : ''"
                :aria-label="rightSidebarCollapsed
                    ? '{{ __('dixlase-pages::components/page-content-editor.sidebar_open') }}'
                    : '{{ __('dixlase-pages::components/page-content-editor.sidebar_close') }}'">
            <i class="fas text-sm" :class="rightSidebarCollapsed ? 'fa-chevron-left' : 'fa-chevron-right'"></i>
        </button>

        {{-- ===== 右サイドバー ===== --}}
        <div class="page-editor-right-sidebar mt-6 lg:mt-0 space-y-6 lg:fixed lg:top-12 lg:right-0 lg:bottom-0 lg:w-80 lg:z-30 lg:overflow-y-auto lg:bg-white/75 dark:lg:bg-gray-900/75 lg:backdrop-blur-sm lg:border-l lg:border-gray-200 dark:lg:border-gray-600 lg:shadow-md lg:px-6 lg:py-6"
             :class="{
                 'lg:translate-x-80': rightSidebarCollapsed,
                 'lg:translate-x-0': !rightSidebarCollapsed
             }"
             :style="rightSidebarReady ? 'transition: transform 300ms ease-in-out' : ''"
             >

            {{-- 4. スラッグ --}}
            <div>
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
                {{-- URLプレビュー --}}
                <div class="mt-2 text-sm" x-show="slug" x-cloak>
                    <span class="text-gray-500 dark:text-gray-400">{{ __('dixlase-pages::components/page-content-editor.slug_url_preview') }}:</span>
                    <span class="font-mono text-blue-600 dark:text-blue-400 break-all" x-text="pageUrl"></span>
                </div>
                @include('components::form-error', ['name' => 'slug'])
            </div>

            {{-- 5. 保存方法選択 --}}
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

                {{-- ファイル保存時の情報表示 --}}
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

            {{-- 6. 公開設定 --}}
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dixlase-pages::components/page-content-editor.publish_section') }}
                </h3>

                {{-- ステータス --}}
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

                {{-- 公開日時（予約公開の場合のみ表示） --}}
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

        </div>

</div>
