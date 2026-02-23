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
 * フォーム状態管理と右サイドバー開閉を担当する
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

        // 右サイドバー状態
        rightSidebarCollapsed: false,
        rightSidebarReady: false,

        init() {
            // localStorageからサイドバー状態を復元
            this.rightSidebarCollapsed = localStorage.getItem('pageEditorRightSidebarCollapsed') === 'true';

            // 次のティックでトランジションを有効化（初期表示時のアニメーション防止）
            this.$nextTick(() => {
                this.rightSidebarReady = true;
            });

            // サイドバー状態をlocalStorageに永続化
            this.$watch('rightSidebarCollapsed', (value) => {
                localStorage.setItem('pageEditorRightSidebarCollapsed', value);
            });

            // ストレージタイプ変更時にエディタータイプを連動更新
            this.$watch('storageType', () => {
                this.updateEditorType();
            });
        },

        /**
         * 右サイドバーの開閉をトグルする
         */
        toggleRightSidebar() {
            this.rightSidebarCollapsed = !this.rightSidebarCollapsed;
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
