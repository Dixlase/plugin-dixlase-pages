/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * ページエディタ Alpine.js コンポーネント
 * フォーム状態管理 + スプリットペイン + ライブプレビューを担当する。
 * コア共通ミックスイン（preview-mixin, split-pane-mixin）を
 * window.Dixlase.mixins ランタイム API 経由で使用する。
 */

const DEBOUNCE_DELAYS = {
    html: 150,
    markdown: 200,
    blade: 400,
    gui: 400,
};

document.addEventListener('alpine:init', () => {
    const { previewMixin, splitPaneMixin, mergeMixins } = window.Dixlase.mixins;

    Alpine.data('pageEditor', (config = {}) => {
        // プレビューフレームURLが設定されている場合のみミックスインを適用
        const hasPreview = !!config.previewFrameUrl;

        const editorState = {
            // フォーム状態
            storageType: config.storageType || 'database',
            editorType: config.editorType || 'html',
            content: config.content || '',
            identifier: config.identifier || '',
            status: config.status || 'draft',
            slug: config.slug || '',
            publishedAt: config.publishedAt || '',
            slugBaseUrl: config.slugBaseUrl || '',
            isEditMode: config.isEditMode || false,
            fileStorageBasePath: config.fileStorageBasePath || '',
            // CSS/JSタブ
            activeTab: 'content',
            customCss: config.customCss || '',
            customJs: config.customJs || '',
            // 言語
            lang: config.lang || '',
            // Simple mode
            isSimpleMode: config.isSimpleMode || false,
            isAdvancedEditor: config.isAdvancedEditor || false,

            // 内部状態
            _debounceTimer: null,
            _cssDebounceTimer: null,
            _abortController: null,

            init() {
                // 右サイドバーの有効化をレイアウトに通知
                this.$dispatch('right-sidebar-active');

                // プレビューミックスイン初期化（編集モードのみ）
                if (hasPreview) {
                    this.initPreview();
                    this.initSplitPane();
                }

                // ストレージタイプ変更時にエディタータイプを連動更新（新規作成時のみ）
                this.$watch('storageType', () => {
                    if (!this.isEditMode) {
                        this.updateEditorType();
                    }
                });

                if (hasPreview) {
                    this.$nextTick(() => {
                        this.initAutoResizeTextareas();
                        this.watchContentChanges();
                    });
                }
            },

            /**
             * 現在のストレージタイプで利用可能なエディター一覧を返す
             */
            get availableEditors() {
                if (this.isEditMode) {
                    return [this.editorType];
                }
                const editors = {
                    'database': ['gui', 'markdown', 'html'],
                    'file': ['gui', 'markdown', 'html'],
                };
                let available = editors[this.storageType] || [];
                if (this.isSimpleMode && !this.isAdvancedEditor) {
                    available = available.filter(e => ['gui', 'markdown'].includes(e));
                }
                return available;
            },

            /**
             * ファイル保存モードかどうかを返す
             */
            get isFileStorage() {
                return this.storageType === 'file';
            },

            /**
             * HTMLエディタかどうかを返す
             */
            get isHtmlEditor() {
                return this.editorType === 'html';
            },

            /**
             * ファイル保存時のファイルパスを返す
             */
            get filePath() {
                if (!this.isFileStorage || !this.slug) {
                    return '';
                }
                const extensions = {
                    'blade': 'blade.php',
                    'markdown': 'md',
                    'html': 'html',
                    'gui': 'json',
                };
                const ext = extensions[this.editorType] || 'txt';
                return `${this.fileStorageBasePath}/${this.slug}.${ext}`;
            },

            /**
             * ページURLプレビューを返す
             */
            get pageUrl() {
                return this.slugBaseUrl + this.slug;
            },

            /**
             * ストレージタイプ変更時にエディタータイプを更新する
             */
            updateEditorType() {
                if (!this.availableEditors.includes(this.editorType)) {
                    this.editorType = this.availableEditors[0] || 'html';
                }
            },


            // --- コンテンツ監視（プレビュー用） ---
            watchContentChanges() {
                const titleEl = document.getElementById('title');
                if (titleEl) {
                    titleEl.addEventListener('input', () => this.onTitleChange());
                }

                const contentEl = document.getElementById('content');
                if (contentEl) {
                    contentEl.addEventListener('input', () => this.onContentChange());
                }

                const cssEl = document.getElementById('custom_css');
                if (cssEl) {
                    cssEl.addEventListener('input', () => this.onCssChange());
                }
            },

            onTitleChange() {
                const title = document.getElementById('title')?.value || '';
                if (this.previewReady) {
                    this.postToIframe('updateTitle', { title });
                }
            },

            onContentChange() {
                clearTimeout(this._debounceTimer);
                const delay = DEBOUNCE_DELAYS[this.editorType] ?? 400;
                this._debounceTimer = setTimeout(() => this.renderAndSend(), delay);
            },

            onCssChange() {
                clearTimeout(this._cssDebounceTimer);
                this._cssDebounceTimer = setTimeout(() => {
                    const css = document.getElementById('custom_css')?.value || '';
                    this.postToIframe('updateCustomCss', { css });
                }, 200);
            },

            // --- プレビューレンダリング ---
            renderAndSend() {
                const content = document.getElementById('content')?.value || '';
                if (!this.previewReady) return;

                if (this.editorType === 'html') {
                    this.postToIframe('updateContent', { html: content });
                } else if (this.editorType === 'markdown') {
                    const html = window.marked ? window.marked.parse(content) : content;
                    this.postToIframe('updateContent', { html });
                } else {
                    this.serverRenderAndSend(content);
                }
            },

            async serverRenderAndSend(content) {
                if (this._abortController) {
                    this._abortController.abort();
                }
                this._abortController = new AbortController();

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    const response = await fetch(config.previewRenderUrl || '', {
                        method: 'POST',
                        signal: this._abortController.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken?.content || '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            content: content,
                            editor_type: this.editorType,
                        }),
                    });

                    if (!response.ok) return;
                    const data = await response.json();
                    this.postToIframe('updateContent', { html: data.html || '' });
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error('Preview render failed:', error);
                    }
                }
            },

            sendContentToPreview() {
                this.renderAndSend();
                if (this.isHtmlEditor) {
                    const css = document.getElementById('custom_css')?.value || '';
                    if (css) {
                        this.postToIframe('updateCustomCss', { css });
                    }
                }
            },

            // --- textarea自動リサイズ ---
            initAutoResizeTextareas() {
                const MIN_HEIGHT = 150;
                const MAX_HEIGHT_VH = 0.6;
                ['content', 'custom_css', 'custom_js'].forEach((id) => {
                    const textarea = document.getElementById(id);
                    if (!textarea) return;
                    textarea.style.overflow = 'hidden';
                    textarea.style.resize = 'none';
                    textarea.style.minHeight = MIN_HEIGHT + 'px';
                    const resize = () => {
                        textarea.style.height = 'auto';
                        const maxH = Math.max(MIN_HEIGHT, window.innerHeight * MAX_HEIGHT_VH);
                        const h = Math.min(Math.max(textarea.scrollHeight, MIN_HEIGHT), maxH);
                        textarea.style.height = h + 'px';
                        textarea.style.overflow = h >= maxH ? 'auto' : 'hidden';
                    };
                    textarea.addEventListener('input', resize);
                    resize();
                });
            },
        };

        // プレビュー有効時はミックスインをマージ、無効時はそのまま返す
        if (hasPreview) {
            const base = mergeMixins(splitPaneMixin(), previewMixin(config));
            return mergeMixins(base, editorState);
        }

        return editorState;
    });
});
