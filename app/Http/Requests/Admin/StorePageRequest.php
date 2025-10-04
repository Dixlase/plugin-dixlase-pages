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
use Illuminate\Validation\Rule;
use Plugins\DixlasePages\App\Enums\PageStatus;

class StorePageRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', 'unique:pages_plugin_pages,slug'],
            'content' => ['required', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'ogp_image' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PageStatus::class)],
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    /**
     * バリデーション前の処理
     */
    protected function prepareForValidation(): void
    {
        // 日付指定以外の場合はpublished_atをクリア
        if ($this->status !== PageStatus::SCHEDULED->value) {
            $this->merge(['published_at' => null]);
        }

        // 公開ステータスの場合は現在時刻を設定
        if ($this->status === PageStatus::PUBLISHED->value) {
            $this->merge(['published_at' => now()]);
        }
    }

    /**
     * カスタムバリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required' => __('pages-plugin::admin.validation.title_required'),
            'title.max' => __('pages-plugin::admin.validation.title_max'),
            'slug.regex' => __('pages-plugin::admin.validation.slug_format'),
            'slug.unique' => __('pages-plugin::admin.validation.slug_unique'),
            'content.required' => __('pages-plugin::admin.validation.content_required'),
            'status.required' => __('pages-plugin::admin.validation.status_required'),
            'published_at.date' => __('pages-plugin::admin.validation.published_at_date'),
            'published_at.after_or_equal' => __('pages-plugin::admin.validation.published_at_future'),
        ];
    }
}
