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
--}}

@extends('themes::layouts.preview')

@section('title', ' - ' . $page->title)

@section('content')
<div class="dixlase-page">
    <div class="container mx-auto pt-24 pb-12 px-4 sm:px-6 lg:px-8">
        <article class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden">
            <header class="px-6 py-8 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4" id="preview-title-area">
                    {{ $page->title }}
                </h1>
            </header>
            <div class="px-6 py-8">
                <div class="prose prose-lg dark:prose-invert max-w-none" id="preview-content-area">
                    {!! $initialRenderedContent !!}
                </div>
            </div>
        </article>
    </div>
</div>
@endsection

@push('scripts')
<script @cspNonce>
/**
 * プレビューフレーム postMessage リスナー
 * 親ウィンドウ（管理画面エディタ）からのメッセージを受信し、コンテンツを更新する。
 */
(function() {
    var contentArea = document.getElementById('preview-content-area');

    window.addEventListener('message', function(event) {
        if (event.origin !== window.location.origin) return;
        if (!event.data || event.data.type !== 'dixlase-preview-update') return;

        switch (event.data.action) {
            case 'updateContent':
                if (contentArea) {
                    contentArea.innerHTML = event.data.html || '';
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
