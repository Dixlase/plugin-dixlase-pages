<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Plugins\DixlasePages\App\Services;

use App\Enums\ContentEditorType;
use App\Enums\MemberRole;
use App\Facades\SiteSettings;
use App\Models\Member;

/**
 * Who may put script-capable content on a page.
 *
 * The HTML editor and a page's custom CSS / JS are served from the site's
 * own origin, so whatever an author writes there runs in every visitor's
 * browser -- including an administrator's, with their session. That makes
 * authoring them equivalent to acting as an administrator, so it is limited
 * to ADMIN and above. Other authors keep the GUI and Markdown editors.
 *
 * Simple mode additionally hides the HTML editor from everyone; the server
 * now enforces what the form only used to hide.
 */
final class ScriptAuthoringPolicy
{
    public const MINIMUM_ROLE = MemberRole::ADMIN;

    /** Editor types every author may use. */
    public const SAFE_EDITOR_SLUGS = ['gui', 'markdown'];

    public static function canAuthorScripts(?Member $member): bool
    {
        if ($member === null || ! $member->role instanceof MemberRole) {
            return false;
        }

        return $member->role->value >= self::MINIMUM_ROLE->value;
    }

    public static function isSimpleMode(): bool
    {
        return (int) SiteSettings::get('admin_mode', 0) === 0;
    }

    /**
     * Editor type slugs this member may choose for a new page.
     *
     * Blade is never offered: it executes on the server.
     *
     * @return list<string>
     */
    public static function creatableEditorSlugs(?Member $member): array
    {
        $slugs = self::SAFE_EDITOR_SLUGS;

        if (self::canAuthorScripts($member) && ! self::isSimpleMode()) {
            $slugs[] = ContentEditorType::HTML->slug();
        }

        return $slugs;
    }

    /**
     * Whether this member may edit a page stored with the given editor type.
     *
     * HTML (and legacy Blade) bodies are script-capable, so only members who
     * may author scripts may change them.
     */
    public static function canEditEditorType(?Member $member, ContentEditorType $editorType): bool
    {
        if (in_array($editorType->slug(), self::SAFE_EDITOR_SLUGS, true)) {
            return true;
        }

        return self::canAuthorScripts($member);
    }
}
