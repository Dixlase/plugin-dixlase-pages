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

@extends('layouts.admin')

@section('content')
    <form id="pages-settings-form" action="{{ route('admin.pages.settings.update') }}" method="POST">
        @csrf
        
        <!-- 基本設定 -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin.settings.basic.title') }}</h2>
            
            <fieldset>
                <legend>{{ __('dixlase-pages::admin.settings.basic.pages_directory') }}</legend>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- ページディレクトリ -->
                    @include('components::form.text', [
                        'name' => 'pages_directory',
                        'label' => __('dixlase-pages::admin.settings.basic.pages_directory'),
                        'value' => old('pages_directory', $settings['pages_directory'] ?? 'pages'),
                        'required' => true,
                        'help' => __('dixlase-pages::admin.settings.basic.pages_directory_help')
                    ])

                    <!-- デフォルトステータス -->
                    @include('components::form.select', [
                        'name' => 'default_status',
                        'label' => __('dixlase-pages::admin.settings.basic.default_status'),
                        'value' => old('default_status', $settings['default_status'] ?? 'published'),
                        'options' => [
                            'published' => __('dixlase-pages::admin.settings.basic.status_published'),
                            'draft' => __('dixlase-pages::admin.settings.basic.status_draft')
                        ],
                        'required' => true
                    ])
                </div>
            </fieldset>
        </section>

        <!-- 機能設定 -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-4">{{ __('dixlase-pages::admin.settings.features.title') }}</h2>
            
            <fieldset>
                <legend>{{ __('dixlase-pages::admin.settings.features.seo_enabled') }}</legend>
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- SEO機能 -->
                    @include('components::form.checkbox', [
                        'name' => 'seo_enabled',
                        'label' => __('dixlase-pages::admin.settings.features.seo_enabled'),
                        'checked' => old('seo_enabled', $settings['seo_enabled'] ?? true),
                        'help' => __('dixlase-pages::admin.settings.features.seo_enabled_help')
                    ])
                </div>
            </fieldset>
        </section>

    </form>
@endsection

@section('save')
    @include('components.save', [
        'id_confirmation' => 'confirmPagesSettingsModal',
        'label' => __('common.save'),
        'title' => __('dixlase-pages::admin.settings.confirm_title'),
        'message' => __('dixlase-pages::admin.settings.confirm_message'),
        'confirm_label' => __('common.save'),
        'cancel_label' => __('common.cancel'),
        'form' => 'pages-settings-form',
    ])
@endsection
