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

@extends('layouts.admin')

@section('content')
    <form id="pages-settings-form" action="{{ route('dixlase-pages::admin.pages.settings.update') }}" method="POST">
        @csrf

        {{-- 基本設定 --}}
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin/pages/settings.basic.title') }}</h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- ページディレクトリ --}}
                <div>
                    <x-form-label
                        for="pages_directory"
                        :text="__('dixlase-pages::admin/pages/settings.basic.pages_directory')"
                        :required="true"
                    />
                    <div class="mt-1 flex items-center">
                        <span class="inline-flex items-center px-3 py-2 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                            {{ $siteUrl }}/
                        </span>
                        <input
                            type="text"
                            name="pages_directory"
                            id="pages_directory"
                            value="{{ old('pages_directory', $settings['pages_directory'] ?? 'pages') }}"
                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-blue-500 focus:border-blue-500"
                            required
                        />
                    </div>
                    <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.basic.pages_directory_help')" />
                    <x-form-error name="pages_directory" />
                </div>

                {{-- デフォルトステータス --}}
                <div>
                    <x-form-label
                        :text="__('dixlase-pages::admin/pages/settings.basic.default_status')"
                        :required="true"
                        class="mb-2"
                    />
                    <x-form-radio-card-group
                        name="default_status"
                        :options="$statusCardOptions"
                        :value="old('default_status', $settings['default_status'] ?? 'published')"
                        :columns="2"
                    />
                    <x-form-error name="default_status" />
                </div>

                {{-- デフォルトエディタタイプ --}}
                <div>
                    <x-form-label
                        :text="__('dixlase-pages::admin/pages/settings.basic.default_editor_type')"
                        :required="true"
                        class="mb-2"
                    />
                    <x-form-radio-card-group
                        name="default_editor_type"
                        :options="$editorTypeCardOptions"
                        :value="old('default_editor_type', $settings['default_editor_type'] ?? 'html')"
                        :columns="3"
                    />
                    <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.basic.default_editor_type_help')" />
                    <x-form-error name="default_editor_type" />
                </div>

                {{-- デフォルト保存方式 --}}
                <div>
                    <x-form-label
                        :text="__('dixlase-pages::admin/pages/settings.basic.default_storage_type')"
                        :required="true"
                        class="mb-2"
                    />
                    <x-form-radio-card-group
                        name="default_storage_type"
                        :options="$storageTypeCardOptions"
                        :value="old('default_storage_type', $settings['default_storage_type'] ?? 'database')"
                        :columns="2"
                    />
                    <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.basic.default_storage_type_help')" />
                    <x-form-error name="default_storage_type" />
                </div>
            </div>
        </section>

    </form>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmPagesSettingsModal"
        :label="__('common.save')"
        :title="__('dixlase-pages::admin/pages/settings.confirm_title')"
        :message="__('dixlase-pages::admin/pages/settings.confirm_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="pages-settings-form"
    />
@endsection
