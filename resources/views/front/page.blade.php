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

@extends('themes::layouts.app')

@php
    // Hand this page's (locale-aware) SEO meta description to DixlaseSEO so
    // it renders in the <head> instead of the site-wide default. Guarded:
    // the helper only exists when the DixlaseSEO plugin is installed.
    if (function_exists('dls_seo_set_page_meta')) {
        dls_seo_set_page_meta($page, 'dixlase-pages');
    }
@endphp

@section('title', $page->getTranslation('title'))

@php
    // The route param is the page's full hierarchical path, not just its
    // own slug, so a child page's asset URLs resolve under /page/parent/child/.
    $pageAssetPath = implode('/', $page->pathSegments());
@endphp

@if(!empty($hasCustomCss))
    @push('styles')
        <link rel="stylesheet" href="{{ route('dixlase-pages::page.custom-style', ['path' => $pageAssetPath]) }}?v={{ $customAssetVersion }}">
    @endpush
@endif

@if(!empty($hasCustomJs))
    @push('scripts')
        <script src="{{ route('dixlase-pages::page.custom-script', ['path' => $pageAssetPath]) }}?v={{ $customAssetVersion }}" defer></script>
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

        @php
            $hasAdminBar = auth('member')->check();
        @endphp
        <!-- Main content -->
        {{-- No card wrapper: the article sits flat on the page background so
             the body's colour shows through. Pages that want a card or
             other styling can re-apply it via custom CSS (see the
             philosophy page's override for the inverse). --}}
        <div class="container mx-auto {{ $hasAdminBar ? 'pt-14' : 'pt-12' }} px-6 sm:px-8 lg:px-12">
            <article>
                <!-- Page header -->
                <header class="pt-10 pb-5">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                        {{ $page->getTranslation('title') }}
                    </h1>
                </header>

                <!-- Page content -->
                <div>
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
