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
 * フォーム状態管理を担当する（右サイドバー状態はコア adminLayout() で管理）
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('pageEditor', (config = {}) => ({
        // フォーム状態
        storageType: config.storageType || 'database',
        editorType: config.editorType || 'html',
        content: config.content || '',
        identifier: config.identifier || '',
        status: config.status || 'draft',
        slug: config.slug || '',
        publishedAt: config.publishedAt || '',
        slugBaseUrl: config.slugBaseUrl || '',

        init() {
            // ストレージタイプ変更時にエディタータイプを連動更新
            this.$watch('storageType', () => {
                this.updateEditorType();
            });
        },

        /**
         * 現在のストレージタイプで利用可能なエディター一覧を返す
         */
        get availableEditors() {
            const editors = {
                'database': ['gui', 'markdown', 'html'],
                'file': ['blade', 'markdown', 'html'],
            };
            return editors[this.storageType] || [];
        },

        /**
         * ファイル保存モードかどうかを返す
         */
        get isFileStorage() {
            return this.storageType === 'file';
        },

        /**
         * ファイル保存時のファイルパスを返す
         */
        get filePath() {
            if (!this.isFileStorage || !this.identifier) {
                return '';
            }
            const extensions = {
                'blade': 'blade.php',
                'markdown': 'md',
                'html': 'html',
                'gui': 'json',
            };
            const ext = extensions[this.editorType] || 'txt';
            return `storage/app/pages/${this.identifier}.${ext}`;
        },

        /**
         * ページURLプレビューを返す
         */
        get pageUrl() {
            return this.slugBaseUrl + this.slug;
        },

        /**
         * ストレージタイプ変更時にエディタータイプを更新する
         * 現在選択中のエディターが利用不可の場合、先頭のエディターに切り替える
         */
        updateEditorType() {
            if (!this.availableEditors.includes(this.editorType)) {
                this.editorType = this.availableEditors[0] || 'html';
            }
        },
    }));
});
