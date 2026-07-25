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

namespace Plugins\DixlasePages\App\Enums;

use App\Enums\ContentStatus;

/**
 * Page status
 * Alias for Core's ContentStatus (for backward compatibility)
 *
 * @deprecated Use App\Enums\ContentStatus directly in new code
 */
class PageStatus
{
    // Alias ContentStatus constants
    public const DRAFT = ContentStatus::DRAFT;

    public const PUBLISHED = ContentStatus::PUBLISHED;

    public const SCHEDULED = ContentStatus::SCHEDULED;

    /**
     * Get ContentStatus instance from slug string
     */
    public static function from(string $value): ContentStatus
    {
        return ContentStatus::fromSlug($value);
    }

    /**
     * Get ContentStatus instance from slug string (returns null on failure)
     */
    public static function tryFrom(string $value): ?ContentStatus
    {
        return ContentStatus::tryFromSlug($value);
    }

    /**
     * Get all cases
     */
    public static function cases(): array
    {
        return ContentStatus::cases();
    }

    /**
     * Get all statuses as array
     */
    public static function toArray(): array
    {
        return ContentStatus::toArray();
    }
}
