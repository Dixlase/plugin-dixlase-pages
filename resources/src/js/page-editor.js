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
 * Page editor Alpine.js component: the page form's own state around core's
 * shared content editor, which draws the editor and the live preview.
 */

document.addEventListener('alpine:init', () => {
    // The page's own state: slug, parent, status, storage and the derived
    // URL and file path. The editor itself (type cards, field, CSS / JS
    // tabs, live preview) is core's shared <x-form-content-editor>, which
    // reports its editor type through the editor-type-changed event.
    Alpine.data('pageEditor', (config = {}) => ({
        storageType: config.storageType || 'database',
        editorType: config.editorType || 'html',
        identifier: config.identifier || '',
        status: config.status || 'draft',
        slug: config.slug || '',
        publishedAt: config.publishedAt || '',
        slugBaseUrl: config.slugBaseUrl || '',
        // Selected parent page id ('' or null = top-level). Bound to the
        // parent_id <select> via x-model so the URL preview updates live.
        parentId: config.parentId ?? '',
        // Map of [parent_id => "ancestor-slug/.../parent-slug"] used by
        // the pageUrl getter to prepend the parent path.
        parentPaths: config.parentPaths || {},
        isEditMode: config.isEditMode || false,
        fileStorageBasePath: config.fileStorageBasePath || '',
        lang: config.lang || '',
        isSimpleMode: config.isSimpleMode || false,
        isAdvancedEditor: config.isAdvancedEditor || false,

        init() {
            // Tell the layout the page has a right sidebar.
            this.$dispatch('right-sidebar-active');

            // On create, open the right sidebar on phones and tablets too.
            if (!this.isEditMode) {
                window.dispatchEvent(new CustomEvent('right-sidebar-force-open'));
            }

            this._onEditorType = (event) => {
                if (event.detail?.editorType) {
                    this.editorType = event.detail.editorType;
                }
            };
            window.addEventListener('editor-type-changed', this._onEditorType);

            // The editor's preview renders the body; the title travels
            // from the sidebar field, now and whenever the frame reloads.
            this._title = document.getElementById('title');
            this._onTitle = () => this.sendTitle();
            this._title?.addEventListener('input', this._onTitle);
            this._onMessage = (event) => {
                if (event.origin === window.location.origin && event.data?.type === 'dixlase-preview-ready') {
                    this.sendTitle();
                }
            };
            window.addEventListener('message', this._onMessage);
        },

        destroy() {
            window.removeEventListener('editor-type-changed', this._onEditorType);
            this._title?.removeEventListener('input', this._onTitle);
            window.removeEventListener('message', this._onMessage);
        },

        sendTitle() {
            const iframe = document.querySelector('iframe[x-ref="previewIframe"]');
            iframe?.contentWindow?.postMessage(
                { type: 'dixlase-preview-update', action: 'updateTitle', title: this._title?.value || '' },
                window.location.origin,
            );
        },

        /**
         * Whether the page is stored as a file.
         */
        get isFileStorage() {
            return this.storageType === 'file';
        },

        /**
         * Whether the page uses the HTML editor.
         */
        get isHtmlEditor() {
            return this.editorType === 'html';
        },

        /**
         * Path of the page's file when it is stored as a file.
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
         * The page URL preview. With a parent page chosen, the parent's
         * path (/page/parent/) comes before the page's own slug.
         */
        get pageUrl() {
            const parentPath = this.parentId
                ? (this.parentPaths[this.parentId] || '')
                : '';
            return parentPath
                ? `${this.slugBaseUrl}${parentPath}/${this.slug}`
                : `${this.slugBaseUrl}${this.slug}`;
        },
    }));
});
