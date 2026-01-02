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

namespace Plugins\DixlasePages\App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'pages_directory' => 'required|string|max:255|alpha_dash',
            'default_status' => 'required|in:published,draft',
            'enable_comments' => 'boolean',
            'seo_enabled' => 'boolean',
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
            'pages_directory' => __('dixlase-pages::admin.settings.basic.pages_directory'),
            'default_status' => __('dixlase-pages::admin.settings.basic.default_status'),
            'enable_comments' => __('dixlase-pages::admin.settings.basic.enable_comments'),
            'seo_enabled' => __('dixlase-pages::admin.settings.basic.seo_enabled'),
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
            'pages_directory.required' => __('dixlase-pages::admin.settings.validation.pages_directory_required'),
            'pages_directory.alpha_dash' => __('dixlase-pages::admin.settings.validation.pages_directory_alpha_dash'),
            'default_status.required' => __('dixlase-pages::admin.settings.validation.default_status_required'),
            'default_status.in' => __('dixlase-pages::admin.settings.validation.default_status_in'),
        ];
    }
}
