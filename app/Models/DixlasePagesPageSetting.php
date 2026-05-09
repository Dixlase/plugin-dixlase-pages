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

namespace Plugins\DixlasePages\App\Models;

use Illuminate\Database\Eloquent\Model;

class DixlasePagesPageSetting extends Model
{
    protected $table = 'plg_dixlase_pages_settings';

    protected $fillable = [
        'name',
        'value',
    ];

    /**
     * Get settings value
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue($name, $default = null)
    {
        $setting = self::where('name', $name)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Save settings value
     *
     * @param  string  $name
     * @param  mixed  $value
     * @return void
     */
    public static function setValue($name, $value)
    {
        self::updateOrCreate(
            ['name' => $name],
            ['value' => $value]
        );
    }

    /**
     * Bulk save multiple settings values
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value);
        }
    }
}
