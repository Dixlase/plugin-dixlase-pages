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
     * 設定値の取得
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
     * 設定値の保存
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
     * 複数の設定値を一括保存
     */
    public static function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::setValue($key, $value);
        }
    }
}
