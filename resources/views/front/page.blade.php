{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
    $pageTitle = $page->getTranslation('title');

    // Hand this page's (locale-aware) SEO meta description and title to
    // DixlaseSEO so they render in the <head> instead of the site-wide
    // defaults. Guarded: the helper only exists when the DixlaseSEO plugin
    // is installed.
    if (function_exists('dls_seo_set_page_meta')) {
        // Positional, not named: DixlaseSEO 0.1.x declares only the first two
        // parameters, and PHP ignores extra positional arguments while an
        // unknown named argument is a fatal Error. Keep this call positional
        // so the page still renders on a site running 0.1.x.
        dls_seo_set_page_meta($page, 'dixlase-pages', $pageTitle);
    }
@endphp

@section('title', $pageTitle)

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

{{-- Preview-mode banner: push into Core's front-banner-stack so it sits
     above the admin bar instead of clashing with the theme header. The
     stack falls through to a no-op on themes that pre-date
     <x-ui-front-banner-stack /> — older themes will simply not show the
     banner, which is preferable to having it tangled in the theme
     navigation. --}}
@php
    $signedIn = app(\App\Services\Auth\SignedInMemberResolver::class)->check();
@endphp
@if($signedIn)
    @if($page->status->slug() !== 'published' || ($page->status->slug() === 'scheduled' && $page->published_at && $page->published_at->isFuture()))
        @push('front-banners')
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
        @endpush
    @endif
@endif

{{-- An edit link in core's admin bar, for a member who may edit pages
     (the front route decides; #109). --}}
@if ($adminEditUrl ?? null)
    @section('admin_bar_edit_url', $adminEditUrl)
    @section('admin_bar_edit_label', __('dixlase-pages::front/page.admin_bar_edit'))
@endif

@section('content')
    {{-- The container and the top padding are this route's own chrome: the
         padding clears core's admin bar, which only this surface sits
         directly beneath. The article itself lives in the partial, so a
         theme's front page renders the same body without them (#127). --}}
    @include('dixlase-pages::front._page-body', [
        'wrapperClass' => 'container mx-auto '.($signedIn ? 'pt-14' : 'pt-12').' px-6 sm:px-8 lg:px-12',
    ])
@endsection
