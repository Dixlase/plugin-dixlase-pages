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
<div class="mx-auto">
    <!-- ページヘッダー -->
    <div class="flex justify-end gap-2 mb-4">
        @include('components::form-button', [
            'type' => 'link',
            'variant' => 'secondary',
            'label' => __('dixlase-pages::admin/pages/index.trash_link'),
            'icon' => 'fas fa-trash',
            'href' => route('dixlase-pages::admin.pages.trash'),
        ])
        @include('components::form-button', [
            'type' => 'link',
            'variant' => 'primary',
            'label' => __('common.create'),
            'icon' => 'fas fa-plus',
            'href' => route('dixlase-pages::admin.pages.create'),
        ])
    </div>

    <!-- 検索・フィルターセクション -->
    <section>
        <form method="GET" role="search">
            <fieldset>
                <legend>{{ __('common.search') }}</legend>
                @include('components::form-text', [
                    'name' => 'search',
                    'value' => request('search'),
                    'placeholder' => __('dixlase-pages::admin/pages/index.search_placeholder'),
                ])
            </fieldset>
            
            <fieldset>
                <legend>{{ __('dixlase-pages::admin/pages/index.status_filter') }}</legend>
                @include('components::form-select', [
                    'name' => 'status',
                    'options' => [
                        '' => 'dixlase-pages::admin/pages/index.all_status',
                        'draft' => 'common.publish_status.draft',
                        'published' => 'common.publish_status.published',
                        'scheduled' => 'common.publish_status.scheduled',
                    ],
                    'value' => request('status'),
                ])
            </fieldset>
            
            <div class="flex gap-2 mt-4">
                @include('components::form-button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => __('common.search'),
                    'icon' => 'fas fa-search',
                ])
            </div>
        </form>
    </section>

    <!-- ページネーションコントロール -->
    @include('components::ui-pagination-controls', [
        'paginator' => $pages,
        'currentPerPage' => request('per_page', 25),
        'totalLabel' => 'components/ui-pagination.total_count',
        'perPageLabel' => 'components/ui-pagination.per_page_label',
        'showSort' => true,
        'sortOptions' => [
            'title' => __('common.title'),
            'slug' => __('common.slug'),
            'status' => __('common.status'),
            'created_at' => __('common.created_at'),
            'updated_at' => __('common.updated_at'),
            'published_at' => __('common.published_at'),
        ],
        'currentSort' => $currentSort ?? 'created_at',
        'currentOrder' => $currentOrder ?? 'desc',
    ])

     <!-- ページネーション -->
    @php
        $paginationData = [
            'current_page' => $pages->currentPage(),
            'last_page' => $pages->lastPage(),
            'prev_page' => $pages->previousPageUrl() ? $pages->currentPage() - 1 : null,
            'next_page' => $pages->nextPageUrl() ? $pages->currentPage() + 1 : null,
        ];
        
        $routeParams = array_filter([
            'search' => request('search'),
            'status' => request('status'),
            'per_page' => request('per_page'),
        ]);
    @endphp
    
    @include('components::ui-pagination', [
        'pagination' => $paginationData,
        'route' => 'dixlase-pages::admin.pages.index',
        'routeParams' => $routeParams,
    ])

    <!-- ページ一覧セクション -->
    <section class="admin-section !p-0 !border-0 !bg-transparent !dark:bg-transparent" aria-label="{{ __('dixlase-pages::admin/pages/index.list_section') }}">
        <div class="admin-card">
            @if($pages->count() > 0)
                <div class="responsive-table mb-6">
                    <table class="admin-table" role="table" aria-label="{{ __('dixlase-pages::admin/pages/index.table_label') }}">
                        <thead class="admin-table__head">
                            <tr class="admin-table__row">
                                <th class="admin-table__header" scope="col">
                                    ID
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('common.title') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('common.slug') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('common.status') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('common.published_at') }}
                                </th>
                                <th class="admin-table__header admin-table__header--actions" scope="col">
                                    {{ __('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            @foreach($pages as $page)
                                <tr class="admin-table__row" role="row">
                                    <td class="admin-table__cell" data-label="ID" role="gridcell">
                                        <span class="font-mono text-sm">{{ $page->id }}</span>
                                    </td>
                                    <td class="admin-table__cell admin-table__cell--primary" data-label="{{ __('common.title') }}" role="gridcell">
                                        <div class="flex flex-col gap-1">
                                            <div class="admin-table__primary-content">
                                                <a href="{{ route('dixlase-pages::admin.pages.edit', $page) }}" class="hover:underline font-medium">
                                                    {{ $page->title }}
                                                </a>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="{{ $page->page_url }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline break-all">
                                                    {{ $page->page_url }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="admin-table__cell" data-label="{{ __('common.slug') }}" role="gridcell">
                                        <code class="admin-code">{{ $page->slug }}</code>
                                    </td>
                                    <td class="admin-table__cell" data-label="{{ __('common.status') }}" role="gridcell">
                                        @include('components::ui-status-badge', [
                                            'status' => $page->status->slug(),
                                            'label' => $page->status->label(),
                                            'variant' => $page->status->cssClass(),
                                        ])
                                    </td>
                                    <td class="admin-table__cell" data-label="{{ __('common.published_at') }}" role="gridcell">
                                        <time datetime="{{ $page->published_at?->toISOString() }}" class="admin-datetime">
                                            @if($page->published_at)
                                                @if(app()->getLocale() === 'ja')
                                                    {{ $page->published_at->format('Y年m月d日 H:i') }}
                                                @else
                                                    {{ $page->published_at->format('Y-m-d H:i') }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </time>
                                    </td>
                                    <td class="admin-table__cell admin-table__cell--actions" data-label="{{ __('common.actions') }}" role="gridcell">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-form-button
                                                type="link"
                                                variant="ghost"
                                                size="sm"
                                                icon="fas fa-edit"
                                                :href="route('dixlase-pages::admin.pages.edit', $page)"
                                                title="{{ __('common.edit') }}"
                                            />

                                            <form action="{{ route('dixlase-pages::admin.pages.destroy', $page) }}" method="POST" id="deleteForm-{{ $page->id }}">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <x-form-button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                icon="fas fa-trash"
                                                class="!text-red-600 hover:!text-red-900 dark:!text-red-400 dark:hover:!text-red-300"
                                                :xClick="'openModal(\'deleteModal-' . $page->id . '\')'"
                                                title="{{ __('common.delete') }}"
                                            />
                                            <x-ui-modal
                                                :id="'deleteModal-' . $page->id"
                                                :title="__('dixlase-pages::admin/pages/index.delete_confirm_title')"
                                                :message="__('dixlase-pages::admin/pages/index.delete_confirm')"
                                                :confirm_label="__('common.delete')"
                                                :cancel_label="__('common.cancel')"
                                                icon_type="danger"
                                                confirm_color="red"
                                                :form="'deleteForm-' . $page->id"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーション -->
                @php
                    $paginationData = [
                        'current_page' => $pages->currentPage(),
                        'last_page' => $pages->lastPage(),
                        'prev_page' => $pages->previousPageUrl() ? $pages->currentPage() - 1 : null,
                        'next_page' => $pages->nextPageUrl() ? $pages->currentPage() + 1 : null,
                    ];
                    
                    $routeParams = array_filter([
                        'search' => request('search'),
                        'status' => request('status'),
                        'per_page' => request('per_page'),
                    ]);
                @endphp
                
                @include('components::ui-pagination', [
                    'pagination' => $paginationData,
                    'route' => 'dixlase-pages::admin.pages.index',
                    'routeParams' => $routeParams,
                ])
            @else
                <div class="admin-empty-state">
                    <h3 class="admin-empty-state__title">
                        {{ __('dixlase-pages::admin/pages/index.no_pages_found') }}
                    </h3>
                    @include('components::form-button', [
                        'type' => 'link',
                        'variant' => 'primary',
                        'label' => __('common.create'),
                        'icon' => 'fas fa-plus',
                        'href' => route('dixlase-pages::admin.pages.create'),
                    ])
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
