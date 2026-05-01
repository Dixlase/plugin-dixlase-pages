<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
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
    'heading' => 'Page Trash',
    'description' => 'Manage deleted pages. You can restore or permanently delete them.',
    'back_to_index' => 'Back to Page List',
    'search_placeholder' => 'Search by title or slug',
    'total_count' => 'In trash: :count pages',
    'empty_state' => 'The trash is empty.',

    'col_title' => 'Title',
    'col_slug' => 'Slug',
    'col_lang' => 'Language',
    'col_deleted_at' => 'Deleted At',
    'col_actions' => 'Actions',

    'restore_button' => 'Restore',
    'restore_success' => 'Page has been restored.',
    'restore_slug_conflict' => 'The slug ":slug" is already in use. Change the conflicting page slug before restoring.',

    'force_delete_button' => 'Delete Permanently',
    'force_delete_confirm' => 'This will permanently delete the page. This action cannot be undone. Are you sure?',
    'force_delete_success' => 'Page has been permanently deleted.',

    'empty_button' => 'Empty Trash',
    'empty_confirm' => 'This will permanently delete all pages in the trash. This action cannot be undone. Are you sure?',
    'empty_success' => ':count pages have been permanently deleted.',
];
