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

@extends('themes::layouts.app')

@section('title', $page->title)

@push('meta')
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
    
    @if($page->ogp_image)
        <meta property="og:image" content="{{ $page->ogp_image }}">
    @endif
    
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ url()->current() }}">
@endpush

@section('content')
    <div class="dixlase-page">
        <!-- プレビューモード表示 -->
        @auth('member')
            @if($page->status->value !== 'published' || ($page->status->value === 'scheduled' && $page->published_at && $page->published_at->isFuture()))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                    <div class="max-w-7xl mx-auto flex items-center">
                        <i class="fas fa-eye mr-3"></i>
                        <div>
                            <p class="font-bold">
                                @if(app()->getLocale() === 'ja')
                                    プレビューモード
                                @else
                                    Preview Mode
                                @endif
                            </p>
                            <p class="text-sm">
                                @if(app()->getLocale() === 'ja')
                                    このページは管理者のみ閲覧可能です（ステータス: {{ $page->status->label() }}）
                                @else
                                    This page is only visible to administrators (Status: {{ $page->status->label() }})
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endauth
        
        <!-- メインコンテンツ -->
        <div class="container mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <article class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <!-- ページヘッダー -->
                <header class="px-6 py-8 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                        {{ $page->title }}
                    </h1>
                    @if($page->published_at)
                        <time datetime="{{ $page->published_at->toISOString() }}" class="text-sm text-gray-600 dark:text-gray-400">
                            @if(app()->getLocale() === 'ja')
                                公開日: {{ $page->published_at->format('Y年m月d日') }}
                            @else
                                Published: {{ $page->published_at->format('F d, Y') }}
                            @endif
                        </time>
                    @endif
                </header>
                
                <!-- ページコンテンツ -->
                <div class="px-6 py-8">
                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        {!! nl2br(e($page->content)) !!}
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
