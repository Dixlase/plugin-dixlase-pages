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
                <x-form-text
                    name="pages_directory"
                    :label="__('dixlase-pages::admin/pages/settings.basic.pages_directory')"
                    :value="old('pages_directory', $settings['pages_directory'] ?? 'pages')"
                    :help="__('dixlase-pages::admin/pages/settings.basic.pages_directory_help')"
                    required
                />

                {{-- デフォルトステータス --}}
                <x-form-select
                    name="default_status"
                    :label="__('dixlase-pages::admin/pages/settings.basic.default_status')"
                    :value="old('default_status', $settings['default_status'] ?? 'published')"
                    :options="[
                        'published' => __('dixlase-pages::admin/pages/settings.basic.status_published'),
                        'draft' => __('dixlase-pages::admin/pages/settings.basic.status_draft'),
                    ]"
                    required
                />

                {{-- デフォルトエディタタイプ --}}
                <x-form-select
                    name="default_editor_type"
                    :label="__('dixlase-pages::admin/pages/settings.basic.default_editor_type')"
                    :value="old('default_editor_type', $settings['default_editor_type'] ?? 'html')"
                    :options="$editorTypeOptions"
                    :help="__('dixlase-pages::admin/pages/settings.basic.default_editor_type_help')"
                    required
                />

                {{-- デフォルト保存方式 --}}
                <x-form-select
                    name="default_storage_type"
                    :label="__('dixlase-pages::admin/pages/settings.basic.default_storage_type')"
                    :value="old('default_storage_type', $settings['default_storage_type'] ?? 'database')"
                    :options="$storageTypeOptions"
                    :help="__('dixlase-pages::admin/pages/settings.basic.default_storage_type_help')"
                    required
                />
            </div>
        </section>

        {{-- 機能設定 --}}
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin/pages/settings.features.title') }}</h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- Bladeエディタの許可 --}}
                <x-form-toggle
                    name="blade_enabled"
                    :label="__('dixlase-pages::admin/pages/settings.features.blade_enabled')"
                    :checked="old('blade_enabled', $settings['blade_enabled'] ?? false)"
                    :help="__('dixlase-pages::admin/pages/settings.features.blade_enabled_help')"
                />

                {{-- スケジュール公開の有効化 --}}
                <x-form-toggle
                    name="scheduled_publish_enabled"
                    :label="__('dixlase-pages::admin/pages/settings.features.scheduled_publish_enabled')"
                    :checked="old('scheduled_publish_enabled', $settings['scheduled_publish_enabled'] ?? false)"
                    :help="__('dixlase-pages::admin/pages/settings.features.scheduled_publish_enabled_help')"
                />
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
