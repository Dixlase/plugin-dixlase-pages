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
<main class="admin-main">
    <!-- ページヘッダー -->
    <header class="admin-header">
        <div class="admin-header__content">
            <h1 class="admin-title">{{ __('dixlase-pages::admin.features.pages.index.heading') }}</h1>
            
            @include('components::form.button', [
                'type' => 'button',
                'variant' => 'primary',
                'label' => __('common.create'),
                'icon' => 'fas fa-plus',
                'onclick' => "window.location.href='" . route('dixlase-pages::admin.pages.create') . "'",
            ])
        </div>
    </header>

    <!-- 検索・フィルターセクション -->
    <section class="admin-section" aria-label="{{ __('dixlase-pages::admin.features.pages.search_section') }}">
        <div class="admin-card">
            <form method="GET" class="admin-form admin-form--horizontal" role="search">
                <div class="admin-form__group admin-form__group--flex-1">
                    @include('components::form.text', [
                        'name' => 'search',
                        'value' => request('search'),
                        'placeholder' => __('dixlase-pages::admin.features.pages.search_placeholder'),
                        'label' => __('dixlase-pages::admin.features.pages.search_label'),
                        'hideLabel' => true,
                        'aria-label' => __('dixlase-pages::admin.features.pages.search_label'),
                    ])
                </div>
                
                <div class="admin-form__group">
                    @include('components::form.select', [
                        'name' => 'status',
                        'options' => [
                            '' => __('dixlase-pages::admin.features.pages.all_status'),
                            'draft' => __('admin.common.status.draft'),
                            'published' => __('admin.common.status.published'),
                            'scheduled' => __('admin.common.status.scheduled'),
                        ],
                        'value' => request('status'),
                        'label' => __('dixlase-pages::admin.features.pages.status_filter'),
                        'hideLabel' => true,
                        'aria-label' => __('dixlase-pages::admin.features.pages.status_filter'),
                    ])
                </div>
                
                @include('components::form.button', [
                    'type' => 'submit',
                    'variant' => 'secondary',
                    'label' => __('common.search'),
                    'icon' => 'fas fa-search',
                ])
            </form>
        </div>
    </section>

    <!-- ページネーションコントロール -->
    @include('components::pagination-controls', [
        'paginator' => $pages,
        'currentPerPage' => request('per_page', 25),
        'totalLabel' => 'components.pagination.total_pages',
        'perPageLabel' => 'components.pagination.per_page_label',
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
    <section class="admin-section" aria-label="{{ __('dixlase-pages::admin.features.pages.list_section') }}">
        <div class="admin-card">
            @if($pages->count() > 0)
                <div class="admin-table-container">
                    <table class="admin-table" role="table" aria-label="{{ __('dixlase-pages::admin.features.pages.table_label') }}">
                        <thead class="admin-table__head">
                            <tr class="admin-table__row">
                                <th class="admin-table__header" scope="col">
                                    {{ __('dixlase-pages::admin.features.pages.title') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('dixlase-pages::admin.features.pages.slug') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('dixlase-pages::admin.features.pages.status') }}
                                </th>
                                <th class="admin-table__header" scope="col">
                                    {{ __('dixlase-pages::admin.features.pages.published_at') }}
                                </th>
                                <th class="admin-table__header admin-table__header--actions" scope="col">
                                    {{ __('admin.common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="admin-table__body">
                            @foreach($pages as $page)
                                <tr class="admin-table__row" role="row">
                                    <td class="admin-table__cell admin-table__cell--primary" role="gridcell">
                                        <div class="admin-table__primary-content">
                                            {{ $page->title }}
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
                                            {{ $page->published_at ? $page->published_at->format('Y-m-d H:i') : '—' }}
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
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- ページネーションコントロール -->
                @include('components::pagination-controls', [
                    'paginator' => $pages,
                    'currentPerPage' => request('per_page', 25),
                    'totalLabel' => 'components.pagination.total_pages',
                    'perPageLabel' => 'components.pagination.per_page_label',
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
</main>
@endsection
