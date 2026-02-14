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
<div class="mx-auto">
    <!-- ページヘッダー -->
    <div class="flex justify-start mb-4">
        @include('components::form-button', [
            'type' => 'button',
            'variant' => 'primary',
            'label' => __('common.create'),
            'icon' => 'fas fa-plus',
            'onclick' => "window.location.href='" . route('dixlase-pages::admin.pages.create') . "'",
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
                        'draft' => 'components.status.draft',
                        'published' => 'components.status.published',
                        'scheduled' => 'components.status.scheduled',
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
        'totalLabel' => 'components.pagination.total_count',
        'perPageLabel' => 'components.pagination.per_page_label',
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
                                            'status' => $page->status->value,
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
                                        <div class="flex flex-wrap gap-1">
                                            @include('components::form-button', [
                                                'type' => 'button',
                                                'variant' => 'secondary',
                                                'size' => 'sm',
                                                'label' => __('common.edit'),
                                                'icon' => 'fas fa-edit',
                                                'class' => 'my-1',
                                                'onclick' => "window.location.href='" . route('dixlase-pages::admin.pages.edit', $page) . "'",
                                            ])
                                            
                                            @include('components::form-button', [
                                                'type' => 'button',
                                                'variant' => 'danger',
                                                'size' => 'sm',
                                                'label' => __('common.delete'),
                                                'icon' => 'fas fa-trash',
                                                'class' => 'my-1',
                                                'onclick' => "if(confirm('" . __('dixlase-pages::admin/pages/index.delete_confirm') . "')) {
                                                    var form = document.createElement('form');
                                                    form.method = 'POST';
                                                    form.action = '" . route('dixlase-pages::admin.pages.destroy', $page) . "';
                                                    form.innerHTML = '<input type=\"hidden\" name=\"_token\" value=\"" . csrf_token() . "\"><input type=\"hidden\" name=\"_method\" value=\"DELETE\">';
                                                    document.body.appendChild(form);
                                                    form.submit();
                                                }",
                                            ])
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
                    <div class="admin-empty-state__icon">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                    </div>
                    <h3 class="admin-empty-state__title">
                        {{ __('dixlase-pages::admin/pages/index.no_pages_found') }}
                    </h3>
                    <p class="admin-empty-state__description">
                        {{ __('dixlase-pages::admin/pages/index.no_pages_description') }}
                    </p>
                    @include('components::form-button', [
                        'type' => 'button',
                        'variant' => 'primary',
                        'label' => __('common.create'),
                        'icon' => 'fas fa-plus',
                        'onclick' => "window.location.href='" . route('dixlase-pages::admin.pages.create') . "'",
                    ])
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
