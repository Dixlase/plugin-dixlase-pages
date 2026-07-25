<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * Website: https://exc-d.com
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
 */

return [
    'heading' => 'ページゴミ箱',
    'description' => '削除済みのページを管理します。復元または完全削除が可能です。',
    'back_to_index' => 'ページ一覧に戻る',
    'search_placeholder' => 'タイトル、スラッグで検索',
    'total_count' => 'ゴミ箱内: :count件',
    'empty_state' => 'ゴミ箱は空です。',

    'col_title' => 'タイトル',
    'col_slug' => 'スラッグ',
    'col_lang' => '言語',
    'col_deleted_at' => '削除日時',
    'col_actions' => '操作',

    'restore_button' => '復元',
    'restore_confirm_title' => 'ページを復元',
    'restore_confirm' => 'このページを復元してページ一覧に戻します。よろしいですか？',
    'restore_success' => 'ページを復元しました。',
    'restore_slug_conflict' => 'スラッグ ":slug" は既に使用されています。先に他のページのスラッグを変更してから復元してください。',

    'force_delete_button' => '完全削除',
    'force_delete_confirm_title' => 'ページを完全に削除',
    'force_delete_confirm' => 'このページを完全に削除します。<br><strong class="text-red-600 dark:text-red-400">この操作は取り消せません。</strong>削除されたページは二度と復元できなくなります。よろしいですか？',
    'force_delete_success' => 'ページを完全に削除しました。',

    'empty_button' => 'ゴミ箱を空にする',
    'empty_confirm_title' => 'ゴミ箱を空にする',
    'empty_confirm' => 'ゴミ箱内の全ページを完全に削除します。<br><strong class="text-red-600 dark:text-red-400">この操作は取り消せません。</strong>削除されたページは二度と復元できなくなります。よろしいですか？',
    'empty_success' => ':count件のページを完全に削除しました。',
];
