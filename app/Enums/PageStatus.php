<?php

/**
 * This file is part of DixlasePages.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\DixlasePages\App\Enums;

enum PageStatus: string
{
    case DRAFT = 'draft';           // 下書き
    case PUBLISHED = 'published';   // 公開
    case SCHEDULED = 'scheduled';   // 日付指定

    /**
     * ステータスの表示名を取得
     */
    public function label(): string
    {
        return match($this) {
            self::DRAFT => __('admin.common.status.draft'),
            self::PUBLISHED => __('admin.common.status.published'),
            self::SCHEDULED => __('admin.common.status.scheduled'),
        };
    }

    /**
     * ステータスの説明を取得
     */
    public function description(): string
    {
        return match($this) {
            self::DRAFT => __('admin.common.status.draft_description'),
            self::PUBLISHED => __('admin.common.status.published_description'),
            self::SCHEDULED => __('admin.common.status.scheduled_description'),
        };
    }

    /**
     * CSSクラスを取得（ステータスバッジ用）
     */
    public function cssClass(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::SCHEDULED => 'yellow',
        };
    }

    /**
     * 全てのステータスを配列で取得
     */
    public static function toArray(): array
    {
        return [
            self::DRAFT->value => self::DRAFT->label(),
            self::PUBLISHED->value => self::PUBLISHED->label(),
            self::SCHEDULED->value => self::SCHEDULED->label(),
        ];
    }

    /**
     * 公開可能なステータスかどうか
     */
    public function isPublishable(): bool
    {
        return match($this) {
            self::PUBLISHED, self::SCHEDULED => true,
            self::DRAFT => false,
        };
    }
}
