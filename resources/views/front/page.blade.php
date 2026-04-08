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

@extends('themes::layouts.app')

@section('title', $page->title)

@if(!empty($hasCustomCss))
    @push('styles')
        <link rel="stylesheet" href="{{ route('dixlase-pages::page.custom-style', ['slug' => $page->slug]) }}?v={{ $customAssetVersion }}">
    @endpush
@endif

@if(!empty($hasCustomJs))
    @push('scripts')
        <script src="{{ route('dixlase-pages::page.custom-script', ['slug' => $page->slug]) }}?v={{ $customAssetVersion }}" defer></script>
    @endpush
@endif

@section('content')
    <div class="dixlase-page">
        <!-- Preview mode banner -->
        @auth('member')
            @if($page->status->slug() !== 'published' || ($page->status->slug() === 'scheduled' && $page->published_at && $page->published_at->isFuture()))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                    <div class="max-w-7xl mx-auto flex items-center">
                        <i class="fas fa-eye mr-3"></i>
                        <div>
                            <p class="font-bold">
                                {{ __('dixlase-pages::front/page.preview_mode') }}
                            </p>
                            <p class="text-sm">
                                {{ __('dixlase-pages::front/page.preview_description', ['status' => $page->status->label()]) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        <!-- Main content -->
        <div class="container mx-auto pt-32 pb-24 px-6 sm:px-8 lg:px-12">
            <article class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
                <!-- Page header -->
                <header class="px-8 sm:px-10 lg:px-12 pt-10 pb-14 border-b border-gray-200 dark:border-gray-700">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                        {{ $page->title }}
                    </h1>
                </header>

                <!-- Page content -->
                <div class="px-8 sm:px-10 lg:px-12 py-10">
                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        @if($editorType === 'markdown')
                            {{-- Markdown: parse and output with shortcode processing --}}
                            {!! shortcode_parse(\Illuminate\Support\Str::markdown($content)) !!}
                        @elseif($editorType === 'html')
                            {{-- HTML: output with shortcode processing --}}
                            {!! shortcode_parse($content) !!}
                        @elseif($editorType === 'blade')
                            {{-- Blade: render as Blade template with shortcode processing --}}
                            {!! shortcode_parse(\Illuminate\Support\Facades\Blade::render($content, ['page' => $page])) !!}
                        @else
                            {{-- Other: escape and output --}}
                            {!! nl2br(e($content)) !!}
                        @endif
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
