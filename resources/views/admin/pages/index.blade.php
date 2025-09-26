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
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ __('pages-plugin::admin.features.pages.index.heading') }}</h1>
        <a href="{{ route('pages-plugin::admin.pages.create') }}" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            {{ __('admin.common.create') }}
        </a>
    </div>

    <!-- 検索・フィルター -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="タイトル、内容、スラッグで検索..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <select name="status" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">全てのステータス</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>
                        {{ __('admin.common.status.draft') }}
                    </option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>
                        {{ __('admin.common.status.published') }}
                    </option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>
                        {{ __('admin.common.status.scheduled') }}
                    </option>
                </select>
            </div>
            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                {{ __('admin.common.search') }}
            </button>
        </form>
    </div>

    <!-- ページ一覧テーブル -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if($pages->count() > 0)
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('pages-plugin::admin.features.pages.title') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('pages-plugin::admin.features.pages.slug') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('pages-plugin::admin.features.pages.status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('pages-plugin::admin.features.pages.published_at') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pages as $page)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $page->title }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $page->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $page->status->cssClass() }}">
                                    {{ $page->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $page->published_at ? $page->published_at->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('pages-plugin::admin.pages.edit', $page) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    {{ __('pages-plugin::admin.actions.edit') }}
                                </a>
                                <form method="POST" action="{{ route('pages-plugin::admin.pages.destroy', $page) }}" 
                                      class="inline" onsubmit="return confirm('{{ __('pages-plugin::admin.actions.delete_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        {{ __('pages-plugin::admin.actions.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- ページネーション -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $pages->links() }}
            </div>
        @else
            <div class="px-6 py-4 text-center text-gray-500">
                {{ __('pages-plugin::admin.messages.no_pages_found') }}
            </div>
        @endif
    </div>
</div>
@endsection
