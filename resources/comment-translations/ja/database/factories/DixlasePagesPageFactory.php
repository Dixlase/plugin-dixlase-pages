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
     * Generate content with mixed Japanese and English
     */' => '/**
     * 日本語と英語を混同したコンテンツを生成
     */',
    '// Get the language from basic settings' => '// 基本設定の言語を取得',
    '// Select primary language with 70% probability, secondary language with 30% probability' => '// プライマリ言語を70%、セカンダリ言語を30%の確率で選択',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '/**
     * Generate content with mixed Japanese and English
     */' => 'machine',
        '// Get the language from basic settings' => 'machine',
        '// Select primary language with 70% probability, secondary language with 30% probability' => 'machine',
    ],
];
