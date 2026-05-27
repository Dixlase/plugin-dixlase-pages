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
    'title_required' => 'タイトルは必須です。',
    'title_max' => 'タイトルは255文字以内で入力してください。',
    'slug_format' => 'スラッグは半角英数字とハイフンのみ使用できます。',
    'slug_unique' => 'このスラッグは既に使用されています。',
    'content_required' => 'コンテンツは必須です。',
    'status_required' => 'ステータスは必須です。',
    'published_at_required' => '日付指定の場合、公開日時は必須です。',
    'published_at_date' => '公開日時は正しい日付形式で入力してください。',
    'published_at_future' => '公開日時は現在時刻以降を指定してください。',
    'route_slug_required' => 'URLスラッグは必須です。',
    'route_slug_alpha_dash' => 'URLスラッグは半角英数字とハイフン、アンダースコアのみ使用できます。',
    'default_status_required' => 'デフォルトステータスは必須です。',
    'default_status_in' => 'デフォルトステータスは公開、下書き、または日付指定を選択してください。',
    'at_least_one_title_required' => 'タイトルは必須です。',
    'parent_self' => 'ページを自分自身の親に設定することはできません。',
    'parent_cycle' => '親ページにこのページの子孫を指定することはできません。',
    'parent_depth_exceeded' => 'ページ階層は 3 階層までです。',
];
