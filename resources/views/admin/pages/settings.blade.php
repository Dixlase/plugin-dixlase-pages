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

@extends('layouts.admin')

@section('content')
    <form id="pages-settings-form" action="{{ route('dixlase-pages::admin.pages.settings.update') }}" method="POST">
        @csrf

        {{-- Simple mode notice --}}
        @if($isSimpleMode)
            <div class="mb-6">
                <x-ui-message type="info" :message="__('dixlase-pages::admin/pages/settings.simple_mode_notice')" />
            </div>
        @endif

        {{-- Basic settings --}}
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin/pages/settings.basic.title') }}</h2>

            <div class="grid grid-cols-1 gap-6">
                {{-- URL slug --}}
                <div>
                    <x-form-label
                        for="route_slug"
                        :text="__('dixlase-pages::admin/pages/settings.basic.route_slug')"
                        :required="true"
                    />
                    <div class="mt-1 flex items-center">
                        <span class="inline-flex items-center px-3 py-2 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                            {{ $siteUrl }}/
                        </span>
                        <input
                            type="text"
                            name="route_slug"
                            id="route_slug"
                            value="{{ old('route_slug', $settings['route_slug'] ?? 'pages') }}"
                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm focus:ring-blue-500 focus:border-blue-500"
                            required
                        />
                    </div>
                    <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.basic.route_slug_help')" />
                    <x-form-error name="route_slug" />
                </div>

                {{-- Default status --}}
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
                        :columns="3"
                    />
                    <x-form-error name="default_status" />
                </div>

                {{-- Default editor type --}}
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
                        :columns="$isSimpleMode ? 2 : 3"
                    />
                    <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.basic.default_editor_type_help')" />
                    <x-form-error name="default_editor_type" />
                </div>

                {{-- Default save method (hidden in simple mode) --}}
                @if($isSimpleMode)
                    <input type="hidden" name="default_storage_type" value="database">
                @else
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
                @endif
            </div>
        </section>

        {{-- Public permission settings --}}
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin/pages/settings.publish_permission.title') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ __('dixlase-pages::admin/pages/settings.publish_permission.description') }}
            </p>

            <div x-data="{
                publishIndex: {{ $publishRoleIndex }},
                roleValues: {{ json_encode(array_values($publishRoleValues)) }},
                get publishValue() { return this.roleValues[this.publishIndex] || this.roleValues[0]; }
            }">
                <x-form-label
                    :text="__('dixlase-pages::admin/pages/settings.publish_permission.min_role')"
                    class="mb-2"
                />

                <input type="hidden" name="publish_min_role" :value="publishValue">

                <x-form-range
                    id="publish_min_role_range"
                    name=""
                    :value="$publishRoleIndex"
                    :min="0"
                    :max="$publishMaxIndex"
                    :step="1"
                    :labels="$publishRoleLabels"
                    :showValue="false"
                    :showLabels="true"
                    xModel="publishIndex"
                />

                <x-form-help-text :text="__('dixlase-pages::admin/pages/settings.publish_permission.min_role_help')" class="!mt-4" />
                <x-form-error name="publish_min_role" />
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
