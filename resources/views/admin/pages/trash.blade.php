{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
Website: https://exc-d.com

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
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto">
    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <a href="{{ route('dixlase-pages::admin.pages.index') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
            {{ __('dixlase-pages::admin/pages/trash.back_to_index') }}
        </a>

        @if($pages->total() > 0)
            <form method="POST" action="{{ route('dixlase-pages::admin.pages.trash.empty') }}" id="emptyTrashForm">
                @csrf
            </form>
            <x-form-button
                type="button"
                variant="danger"
                size="sm"
                icon="fas fa-trash"
                xClick="openModal('emptyTrashModal')"
            >
                {{ __('dixlase-pages::admin/pages/trash.empty_button') }}
            </x-form-button>
            <x-ui-modal
                id="emptyTrashModal"
                :title="__('dixlase-pages::admin/pages/trash.empty_confirm_title')"
                :message="__('dixlase-pages::admin/pages/trash.empty_confirm')"
                :confirm_label="__('dixlase-pages::admin/pages/trash.empty_button')"
                :cancel_label="__('common.cancel')"
                icon_type="danger"
                confirm_color="red"
                form="emptyTrashForm"
            />
        @endif
    </div>

    {{-- Search --}}
    <section class="mb-4">
        <form method="GET" role="search">
            <fieldset>
                <legend class="sr-only">{{ __('common.search') }}</legend>
                <div class="flex gap-2">
                    <input type="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('dixlase-pages::admin/pages/trash.search_placeholder') }}"
                           class="input-common flex-1">
                    <x-form-button type="submit" variant="secondary" icon="fas fa-search">
                        {{ __('common.search') }}
                    </x-form-button>
                </div>
            </fieldset>
        </form>
    </section>

    {{-- Item count display --}}
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        {{ __('dixlase-pages::admin/pages/trash.total_count', ['count' => $pages->total()]) }}
    </p>

    {{-- Page list in trash --}}
    @if($pages->isEmpty())
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <i class="fas fa-trash text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('dixlase-pages::admin/pages/trash.empty_state') }}
            </p>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                            {{ __('dixlase-pages::admin/pages/trash.col_title') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                            {{ __('dixlase-pages::admin/pages/trash.col_slug') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                            {{ __('dixlase-pages::admin/pages/trash.col_lang') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                            {{ __('dixlase-pages::admin/pages/trash.col_deleted_at') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 dark:text-gray-300 uppercase">
                            {{ __('dixlase-pages::admin/pages/trash.col_actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($pages as $page)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-gray-900 dark:text-white">
                                {{ $page->title ?: '(無題)' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $page->slug }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $page->lang }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $page->deleted_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    {{-- Restore --}}
                                    <form method="POST" action="{{ route('dixlase-pages::admin.pages.trash.restore', ['id' => $page->id]) }}" id="restoreForm-{{ $page->id }}">
                                        @csrf
                                    </form>
                                    <x-form-button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        icon="fas fa-undo"
                                        :xClick="'openModal(\'restoreModal-' . $page->id . '\')'"
                                    >
                                        {{ __('dixlase-pages::admin/pages/trash.restore_button') }}
                                    </x-form-button>
                                    <x-ui-modal
                                        :id="'restoreModal-' . $page->id"
                                        :title="__('dixlase-pages::admin/pages/trash.restore_confirm_title')"
                                        :message="__('dixlase-pages::admin/pages/trash.restore_confirm')"
                                        :confirm_label="__('dixlase-pages::admin/pages/trash.restore_button')"
                                        :cancel_label="__('common.cancel')"
                                        icon_type="info"
                                        confirm_color="blue"
                                        :form="'restoreForm-' . $page->id"
                                    />

                                    {{-- Permanent delete --}}
                                    <form method="POST" action="{{ route('dixlase-pages::admin.pages.trash.force-destroy', ['id' => $page->id]) }}" id="forceDeleteForm-{{ $page->id }}">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <x-form-button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        icon="fas fa-trash-alt"
                                        :xClick="'openModal(\'forceDeleteModal-' . $page->id . '\')'"
                                    >
                                        {{ __('dixlase-pages::admin/pages/trash.force_delete_button') }}
                                    </x-form-button>
                                    <x-ui-modal
                                        :id="'forceDeleteModal-' . $page->id"
                                        :title="__('dixlase-pages::admin/pages/trash.force_delete_confirm_title')"
                                        :message="__('dixlase-pages::admin/pages/trash.force_delete_confirm')"
                                        :confirm_label="__('dixlase-pages::admin/pages/trash.force_delete_button')"
                                        :cancel_label="__('common.cancel')"
                                        icon_type="danger"
                                        confirm_color="red"
                                        :form="'forceDeleteForm-' . $page->id"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $pages->links() }}
        </div>
    @endif
</div>
@endsection
