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

namespace Plugins\DixlasePages\App\Http\Requests\Admin;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
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
            'pages_directory' => 'required|string|max:255|alpha_dash',
            'default_status' => 'required|in:published,draft',
            'default_editor_type' => ['required', Rule::enum(ContentEditorType::class)],
            'default_storage_type' => ['required', Rule::enum(ContentStorageType::class)],
            'blade_enabled' => 'boolean',
            'scheduled_publish_enabled' => 'boolean',
        ];
    }

    /**
     * バリデーション前の処理
     */
    protected function prepareForValidation(): void
    {
        // トグルが未送信の場合は false を設定
        if (! $this->has('blade_enabled')) {
            $this->merge(['blade_enabled' => false]);
        }
        if (! $this->has('scheduled_publish_enabled')) {
            $this->merge(['scheduled_publish_enabled' => false]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pages_directory' => __('dixlase-pages::admin/pages/settings.basic.pages_directory'),
            'default_status' => __('dixlase-pages::admin/pages/settings.basic.default_status'),
            'default_editor_type' => __('dixlase-pages::admin/pages/settings.basic.default_editor_type'),
            'default_storage_type' => __('dixlase-pages::admin/pages/settings.basic.default_storage_type'),
            'blade_enabled' => __('dixlase-pages::admin/pages/settings.features.blade_enabled'),
            'scheduled_publish_enabled' => __('dixlase-pages::admin/pages/settings.features.scheduled_publish_enabled'),
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
            'pages_directory.required' => __('dixlase-pages::admin/pages/validation.pages_directory_required'),
            'pages_directory.alpha_dash' => __('dixlase-pages::admin/pages/validation.pages_directory_alpha_dash'),
            'default_status.required' => __('dixlase-pages::admin/pages/validation.default_status_required'),
            'default_status.in' => __('dixlase-pages::admin/pages/validation.default_status_in'),
        ];
    }
}
