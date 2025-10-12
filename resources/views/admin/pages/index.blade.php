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

@extends('admin::partials.layout')

@section('content')
    <!-- ページヘッダー -->
    <div class="flex justify-end mb-4">
        @include('components::form.button', [
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
                @include('components::form.text', [
                    'name' => 'search',
                    'value' => request('search'),
                    'placeholder' => __('dixlase-pages::admin.pages.search_placeholder'),
                ])
            </fieldset>
            
            <fieldset>
                <legend>{{ __('dixlase-pages::admin.pages.status_filter') }}</legend>
                @include('components::form.select', [
                    'name' => 'status',
                    'options' => [
                        '' => 'dixlase-pages::admin.pages.all_status',
                        'draft' => 'components.status.draft',
                        'published' => 'components.status.published',
                        'scheduled' => 'components.status.scheduled',
                    ],
                    'value' => request('status'),
                ])
            </fieldset>
            
            <div class="flex gap-2 mt-4">
                @include('components::form.button', [
                    'type' => 'submit',
                    'variant' => 'primary',
                    'label' => __('common.search'),
                    'icon' => 'fas fa-search',
                ])
            </div>
        </form>
    </section>

    <!-- ページネーションコントロール -->
    @include('components::pagination-controls', [
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
    
    @include('components::pagination', [
        'pagination' => $paginationData,
        'route' => 'dixlase-pages::admin.pages.index',
        'routeParams' => $routeParams,
    ])

    <!-- ページ一覧セクション -->
    <section class="admin-section" aria-label="{{ __('dixlase-pages::admin.pages.list_section') }}">
        <div class="admin-card">
            @if($pages->count() > 0)
                <div class="admin-table-container">
                    <table class="admin-table" role="table" aria-label="{{ __('dixlase-pages::admin.pages.table_label') }}">
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
                                <!-- 1行目: ID、タイトル、スラッグ、ステータス、公開日時、アクション -->
                                <tr class="admin-table__row" role="row">
                                    <td class="admin-table__cell" role="gridcell">
                                        <span class="text-gray-600 font-mono text-sm">{{ $page->id }}</span>
                                    </td>
                                    <td class="admin-table__cell admin-table__cell--primary" role="gridcell">
                                        <div class="admin-table__primary-content">
                                            <a href="{{ route('dixlase-pages::admin.pages.edit', $page) }}" class="hover:underline">
                                                {{ $page->title }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="admin-table__cell" role="gridcell">
                                        <code class="admin-code">{{ $page->slug }}</code>
                                    </td>
                                    <td class="admin-table__cell" role="gridcell">
                                        @include('components::status-badge', [
                                            'status' => $page->status->value,
                                            'label' => $page->status->label(),
                                            'variant' => $page->status->cssClass(),
                                        ])
                                    </td>
                                    <td class="admin-table__cell" role="gridcell">
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
                                    <td class="admin-table__cell admin-table__cell--actions" role="gridcell">
                                        <div class="admin-actions">
                                            @include('components::form.button', [
                                                'type' => 'button',
                                                'variant' => 'secondary',
                                                'size' => 'sm',
                                                'label' => __('common.edit'),
                                                'icon' => 'fas fa-edit',
                                                'onclick' => "window.location.href='" . route('dixlase-pages::admin.pages.edit', $page) . "'",
                                            ])
                                            
                                            @include('components::form.button', [
                                                'type' => 'button',
                                                'variant' => 'danger',
                                                'size' => 'sm',
                                                'label' => __('common.delete'),
                                                'icon' => 'fas fa-trash',
                                                'onclick' => "if(confirm('" . __('dixlase-pages::admin.actions.delete_confirm') . "')) { 
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
                                <!-- 2行目: URL -->
                                <tr class="admin-table__row admin-table__row--secondary" role="row">
                                    <td colspan="6" class="p-0 admin-table__cell admin-table__cell--secondary" role="gridcell">
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <a href="{{ $page->page_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1 min-w-0">
                                                <span class="truncate" title="{{ $page->page_url }}">{{ $page->page_url }}</span>
                                            </a>
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
                
                @include('components::pagination', [
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
                        {{ __('dixlase-pages::admin.messages.no_pages_found') }}
                    </h3>
                    <p class="admin-empty-state__description">
                        {{ __('dixlase-pages::admin.messages.no_pages_description') }}
                    </p>
                    @include('components::form.button', [
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
@endsection
