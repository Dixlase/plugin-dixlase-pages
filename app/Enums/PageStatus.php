<?php

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
 * ページステータス
 * コアのContentStatusのエイリアス（後方互換性のため）
 * 
 * @deprecated 新規コードではApp\Enums\ContentStatusを直接使用してください
 */
class PageStatus
{
    // ContentStatusの定数をエイリアス
    public const DRAFT = ContentStatus::DRAFT;
    public const PUBLISHED = ContentStatus::PUBLISHED;
    public const SCHEDULED = ContentStatus::SCHEDULED;

    /**
     * スラッグ文字列からContentStatusインスタンスを取得
     */
    public static function from(string $value): ContentStatus
    {
        return ContentStatus::fromSlug($value);
    }

    /**
     * スラッグ文字列からContentStatusインスタンスを取得（失敗時はnull）
     */
    public static function tryFrom(string $value): ?ContentStatus
    {
        return ContentStatus::tryFromSlug($value);
    }

    /**
     * 全てのケースを取得
     */
    public static function cases(): array
    {
        return ContentStatus::cases();
    }

    /**
     * 全てのステータスを配列で取得
     */
    public static function toArray(): array
    {
        return ContentStatus::toArray();
    }
}
