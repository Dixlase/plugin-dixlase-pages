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
     * Custom validation messages
     */' => '/**
     * カスタムバリデーションメッセージ
     */',
    '/**
     * Processing before validation
     */' => '/**
     * バリデーション前の処理
     */',
    '// Clear published_at if not date-specified' => '// 日付指定以外の場合はpublished_atをクリア',
    '// Exclude soft-deleted records from unique check' => '// ソフトデリートされたレコードは除外してユニークチェック',
    '// If language is not specified, set the site settings language as default' => '// 言語が未指定の場合、サイト設定の言語をデフォルトで設定',
    '// Public permission check: force status to draft for members without permission' => '// 公開権限チェック: 権限がないメンバーはステータスを強制的にdraftに',
    '// SEO meta (optional dependency, validation passes even if SEO plugin is disabled)' => '// SEOメタ（optional依存、SEOプラグインが無効でも検証は通過）',
    '// Save format is locked after creation: overwrite with existing page value during edit' => '// 保存形式は作成後ロック：編集時は既存のページの値で上書き',
    '// Set current time if status is public' => '// 公開ステータスの場合は現在時刻を設定',
    '// Slug is required (existing slug is present during updates)' => '// スラッグは必須（更新時は既存のスラッグがあるため）',
    '// Slug must be unique across all languages (to prevent URL duplication)' => '// スラッグは言語に関わらず全体でユニーク（URL重複を防ぐ）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Additional checks after validation
     */' => 'machine',
        '/**
     * Custom validation messages
     */' => 'machine',
        '/**
     * Processing before validation
     */' => 'machine',
        '// Clear published_at if not date-specified' => 'machine',
        '// Exclude soft-deleted records from unique check' => 'machine',
        '// If language is not specified, set the site settings language as default' => 'machine',
        '// Public permission check: force status to draft for members without permission' => 'machine',
        '// SEO meta (optional dependency, validation passes even if SEO plugin is disabled)' => 'machine',
        '// Save format is locked after creation: overwrite with existing page value during edit' => 'machine',
        '// Set current time if status is public' => 'machine',
        '// Slug is required (existing slug is present during updates)' => 'machine',
        '// Slug must be unique across all languages (to prevent URL duplication)' => 'machine',
    ],
];
