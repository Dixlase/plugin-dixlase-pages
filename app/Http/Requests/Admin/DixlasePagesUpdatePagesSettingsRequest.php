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

namespace Plugins\DixlasePages\App\Http\Requests\Admin;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Rules\UniqueRouteSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DixlasePagesUpdatePagesSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'route_slug' => ['required', 'string', 'max:255', 'alpha_dash', UniqueRouteSlug::for('dixlase-pages:route_slug')],
            'default_status' => 'required|in:published,draft,scheduled',
            'default_editor_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentEditorType::cases()))],
            'default_storage_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStorageType::cases()))],
            'publish_min_role' => ['required', 'integer', Rule::in(array_map(fn ($case) => $case->value, MemberRole::cases()))],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'route_slug' => __('dixlase-pages::admin/pages/settings.basic.route_slug'),
            'default_status' => __('dixlase-pages::admin/pages/settings.basic.default_status'),
            'default_editor_type' => __('dixlase-pages::admin/pages/settings.basic.default_editor_type'),
            'default_storage_type' => __('dixlase-pages::admin/pages/settings.basic.default_storage_type'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'route_slug.required' => __('dixlase-pages::admin/pages/validation.route_slug_required'),
            'route_slug.alpha_dash' => __('dixlase-pages::admin/pages/validation.route_slug_alpha_dash'),
            'default_status.required' => __('dixlase-pages::admin/pages/validation.default_status_required'),
            'default_status.in' => __('dixlase-pages::admin/pages/validation.default_status_in'),
        ];
    }
}
