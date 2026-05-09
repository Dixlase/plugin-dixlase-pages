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
     * Attributes to cast
     *
     * @var array
     */' => '/**
     * キャストする属性
     *
     * @var array
     */',
    '/**
     * Determine if published
     */' => '/**
     * 公開されているかどうかを判定
     */',
    '/**
     * Mass assignable attributes
     *
     * @var array<int, string>
     */' => '/**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */',
    '/**
     * Page URL accessor
     */' => '/**
     * ページURLのアクセサー
     */',
    '/**
     * Retrieve content (according to editor type)
     * When saved as file, load content from file
     * When saved in DB, load from content column
     */' => '/**
     * コンテンツを取得（エディタータイプに応じて）
     * ファイル保存の場合はファイルからコンテンツを読み込む
     * DB保存の場合は content カラムから読み込む
     */',
    '/**
     * Scope for draft pages
     */' => '/**
     * 下書きページのスコープ
     */',
    '/**
     * Scope for public pages
     */' => '/**
     * 公開可能なページのスコープ
     */',
    '/**
     * Scope for scheduled pages
     */' => '/**
     * 日付指定ページのスコープ
     */',
    '/**
     * Scope to retrieve pages of specified language
     */' => '/**
     * 指定言語のページを取得するスコープ
     */',
    '/**
     * Specify the factory location
     */' => '/**
     * ファクトリーの場所を指定
     */',
    '/**
     * Status accessor (safe conversion)
     */' => '/**
     * ステータスのアクセサー（安全な変換）
     */',
    '/**
     * Status mutator
     */' => '/**
     * ステータスのミューテーター
     */',
    '/**
     * Table name
     */' => '/**
     * テーブル名
     */',
    '/**
 * @property string $lang Language code
 */' => '/**
 * @property string $lang 言語コード
 */',
    '// Convert old data' => '// 古いデータの変換',
    '// Delete SEO meta information' => '// SEOメタ情報の削除',
    '// If slug string, convert to enum and save int value' => '// スラッグ文字列の場合はenumに変換してint値を保存',
    '// When force deleting a page, also cascade delete SEO meta information and content files (not deleted on SoftDeletes)' => '// ページ強制削除時にSEOメタ情報・コンテンツファイルもカスケード削除（SoftDeletes時は消さない）',
    '// When saved as file' => '// ファイル保存の場合',
    '// When saved as file, also delete related files' => '// ファイル保存の場合、関連ファイルも削除',
    '// When saved in DB, load from content column' => '// DB保存の場合は content カラムから読み込む',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Attributes to cast
     *
     * @var array
     */' => 'machine',
        '/**
     * Determine if published
     */' => 'machine',
        '/**
     * Mass assignable attributes
     *
     * @var array<int, string>
     */' => 'machine',
        '/**
     * Page URL accessor
     */' => 'machine',
        '/**
     * Retrieve content (according to editor type)
     * When saved as file, load content from file
     * When saved in DB, load from content column
     */' => 'machine',
        '/**
     * Scope for draft pages
     */' => 'machine',
        '/**
     * Scope for public pages
     */' => 'machine',
        '/**
     * Scope for scheduled pages
     */' => 'machine',
        '/**
     * Scope to retrieve pages of specified language
     */' => 'machine',
        '/**
     * Specify the factory location
     */' => 'machine',
        '/**
     * Status accessor (safe conversion)
     */' => 'machine',
        '/**
     * Status mutator
     */' => 'machine',
        '/**
     * Table name
     */' => 'machine',
        '/**
 * @property string $lang Language code
 */' => 'machine',
        '// Convert old data' => 'machine',
        '// Delete SEO meta information' => 'machine',
        '// If slug string, convert to enum and save int value' => 'machine',
        '// When force deleting a page, also cascade delete SEO meta information and content files (not deleted on SoftDeletes)' => 'machine',
        '// When saved as file' => 'machine',
        '// When saved as file, also delete related files' => 'machine',
        '// When saved in DB, load from content column' => 'machine',
    ],
];
