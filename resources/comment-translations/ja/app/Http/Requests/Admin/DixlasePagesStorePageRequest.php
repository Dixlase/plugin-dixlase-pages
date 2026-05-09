<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase Pages is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU General Public License version 3 or later, as published
 *       by the Free Software Foundation; or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the GPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [
    '/**
     * Additional checks after validation
     */' => '/**
     * バリデーション後の追加チェック
     */',
    '/**
     * Convert string to slug
     */' => '/**
     * 文字列をスラッグに変換
     */',
    '/**
     * Custom validation messages
     */' => '/**
     * カスタムバリデーションメッセージ
     */',
    '/**
     * Pre-validation processing
     */' => '/**
     * バリデーション前の処理
     */',
    '// Clear published_at if not date-specified' => '// 日付指定以外の場合はpublished_atをクリア',
    '// Collapse consecutive hyphens to one' => '// 連続するハイフンを1つに',
    '// Convert hiragana and katakana to romaji' => '// ひらがな・カタカナをローマ字に変換',
    '// Convert spaces and underscores to hyphens' => '// 空白、アンダースコアをハイフンに変換',
    '// If language is not specified, set site settings language as default' => '// 言語が未指定の場合、サイト設定の言語をデフォルトで設定',
    '// If slug is empty, auto-generate from title' => '// スラッグが空の場合、タイトルから自動生成',
    '// Public permission check: force status to draft for members without permission' => '// 公開権限チェック: 権限がないメンバーはステータスを強制的にdraftに',
    '// Remove anything other than alphanumeric characters and hyphens' => '// 英数字とハイフン以外を削除',
    '// Remove leading and trailing hyphens' => '// 先頭と末尾のハイフンを削除',
    '// Remove non-ASCII characters' => '// 非ASCII文字を削除',
    '// Romaji conversion map' => '// ローマ字変換マップ',
    '// SEO meta (optional dependency, validation passes even if SEO plugin is disabled)' => '// SEOメタ（optional依存、SEOプラグインが無効でも検証は通過）',
    '// Set current time if status is public' => '// 公開ステータスの場合は現在時刻を設定',
    '// Slug is optional (auto-generated from title if empty)' => '// スラッグは任意（空の場合はタイトルから自動生成）',
    '// Slug must be unique across all languages (to prevent URL duplication)' => '// スラッグは言語に関わらず全体でユニーク（URL重複を防ぐ）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Additional checks after validation
     */' => 'machine',
        '/**
     * Convert string to slug
     */' => 'machine',
        '/**
     * Custom validation messages
     */' => 'machine',
        '/**
     * Pre-validation processing
     */' => 'machine',
        '// Clear published_at if not date-specified' => 'machine',
        '// Collapse consecutive hyphens to one' => 'machine',
        '// Convert hiragana and katakana to romaji' => 'machine',
        '// Convert spaces and underscores to hyphens' => 'machine',
        '// If language is not specified, set site settings language as default' => 'machine',
        '// If slug is empty, auto-generate from title' => 'machine',
        '// Public permission check: force status to draft for members without permission' => 'machine',
        '// Remove anything other than alphanumeric characters and hyphens' => 'machine',
        '// Remove leading and trailing hyphens' => 'machine',
        '// Remove non-ASCII characters' => 'machine',
        '// Romaji conversion map' => 'machine',
        '// SEO meta (optional dependency, validation passes even if SEO plugin is disabled)' => 'machine',
        '// Set current time if status is public' => 'machine',
        '// Slug is optional (auto-generated from title if empty)' => 'machine',
        '// Slug must be unique across all languages (to prevent URL duplication)' => 'machine',
    ],
];
