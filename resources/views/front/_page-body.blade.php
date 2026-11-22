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

@php
    // A heading the surface already carries must not be repeated. A theme
    // with a hero above the body passes false (#127).
    $showHeading ??= true;

    // The chrome around the article belongs to the surface, not to the body.
    // The page's own route passes its container plus the padding that clears
    // core's admin bar; a theme's front page passes nothing, because the
    // theme has already opened a container and placed the body under its
    // hero. Defaulting to no chrome keeps the body a body: a caller that
    // forgets gets a plain article, not a second container nested in one.
    $wrapperClass ??= '';
@endphp

{{-- `.dixlase-page` is the hook a page's custom CSS is written against, so
     it stays the outermost element on both surfaces. Putting it inside the
     container instead would change what `.dixlase-page > *` selects on
     every site that has custom CSS. --}}
<div class="dixlase-page">
    {{-- No card wrapper: the article sits flat on the page background so
         the body's colour shows through. Pages that want a card or other
         styling can re-apply it via custom CSS (see the philosophy page's
         override for the inverse). --}}
    <div class="dixlase-page__inner {{ $wrapperClass }}">
        <article>
            @if ($showHeading)
                <!-- Page header -->
                <header class="pt-10 pb-5">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white">
                        {{ $page->getTranslation('title') }}
                    </h1>
                </header>
            @endif

            {{-- Page content. `prose` is applied here, by the body, because a
                 theme that places a provider's body deliberately applies none
                 of its own — a listing or a card grid must not be typeset as
                 an article. A page *is* an article, so it brings prose with
                 it. --}}
            <div>
                <div class="prose prose-lg dark:prose-invert max-w-none">
                    @if($editorType === 'markdown')
                        {{-- Markdown: rendered by core exactly as the preview renders it
                             (javascript: links stripped, same heading normalisation).
                             Raw HTML is escaped: editors below ADMIN write Markdown,
                             and HTML authoring is reserved for ADMIN and above. --}}
                        {!! shortcode_parse(app(\App\Services\ContentPreviewService::class)->render($content, \App\Enums\ContentEditorType::MARKDOWN, false)) !!}
                    @elseif($editorType === 'html')
                        {{-- HTML: output with shortcode processing --}}
                        {!! shortcode_parse($content) !!}
                    @else
                        {{-- Other: escape and output.

                             A 'blade' branch calling Blade::render($content)
                             used to sit above this one. The admin preview
                             action builds an unsaved page straight from
                             request input, including editor_type, so that
                             branch turned a POST body into executed PHP.
                             The Blade editor is not offered by this version
                             anyway -- the edit screen excludes it from the
                             editor options unconditionally -- so nothing
                             legitimate reached it. Anything already stored
                             with editor_type=blade now lands here and is
                             escaped, which is the safe direction. --}}
                        {!! nl2br(e($content)) !!}
                    @endif
                </div>
            </div>
        </article>
    </div>
</div>
