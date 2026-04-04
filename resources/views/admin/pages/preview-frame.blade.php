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

iframe用プレビューフレーム。
管理画面のページ編集画面でiframeとして読み込まれ、
テーマの実際のレイアウトでコンテンツをリアルタイムプレビューする。
postMessage でコンテンツ・カスタムCSSの更新を受け取る。
軽量プレビューレイアウト（layouts.preview）を使用し、JSバンドルを読み込まない。
ページプラグインのフロント表示テンプレート（front/page.blade.php）と同じ構造を使用。
--}}

@extends('themes::layouts.preview')

@section('title', ' - ' . ($page->title ?? ''))

@section('content')
<div class="dixlase-page">
    <div class="container mx-auto pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <article class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            {{-- ページヘッダー --}}
            <header class="px-6 py-8 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4" id="preview-title-area">
                    {{ $page->title }}
                </h1>
            </header>

            {{-- ページコンテンツ --}}
            <div class="px-6 py-8">
                <div class="prose prose-lg dark:prose-invert max-w-none" id="preview-content-area">
                    {!! $initialRenderedContent !!}
                </div>
            </div>
        </article>
    </div>
</div>

{{-- お問い合わせフォームセクション（プラグイン有効時のみ） --}}
@if(function_exists('dls_inquiry_enabled') && dls_inquiry_enabled())
<section class="inquiry-section py-16 bg-gray-100 dark:bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            @php $inquirySettings = function_exists('dls_inquiry_settings') ? dls_inquiry_settings() : null; @endphp
            @if(!empty($inquirySettings->form_heading))
                <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-3">{{ $inquirySettings->form_heading }}</h2>
            @endif
            @if(!empty($inquirySettings->form_description))
                <p class="text-center text-gray-600 dark:text-gray-400 mb-8 max-w-lg mx-auto">{{ $inquirySettings->form_description }}</p>
            @endif
            {!! dls_inquiry_form() !!}
        </div>
    </div>
</section>
@endif
@endsection

@push('styles')
<style @cspNonce>
/* CAPTCHAウィジェットを非表示 */
.captcha-container,
.g-recaptcha,
.grecaptcha-badge,
.cf-turnstile,
#recaptcha-container,
iframe[src*="recaptcha"],
iframe[src*="turnstile"] {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script @cspNonce>
/**
 * プレビューフレーム postMessage リスナー
 * 親ウィンドウ（管理画面エディタ）からのメッセージを受信し、コンテンツを更新する。
 */
(function() {
    var contentArea = document.getElementById('preview-content-area');
    var titleArea = document.getElementById('preview-title-area');

    window.addEventListener('message', function(event) {
        if (event.origin !== window.location.origin) return;
        if (!event.data || event.data.type !== 'dixlase-preview-update') return;

        switch (event.data.action) {
            case 'updateContent':
                if (contentArea) {
                    contentArea.innerHTML = event.data.html || '';
                }
                break;

            case 'updateTitle':
                if (titleArea) {
                    titleArea.textContent = event.data.title || '';
                }
                break;

            case 'updateCustomCss':
                var styleEl = document.getElementById('preview-custom-css');
                if (!styleEl) {
                    styleEl = document.createElement('style');
                    styleEl.id = 'preview-custom-css';
                    document.head.appendChild(styleEl);
                }
                styleEl.textContent = event.data.css || '';
                break;
        }
    });

    // 親ウィンドウに準備完了を通知
    if (window.parent !== window) {
        window.parent.postMessage({ type: 'dixlase-preview-ready' }, window.location.origin);
    }
})();
</script>
@endpush
