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
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DixlasePagesUpdatePageRequest extends FormRequest
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
        $pageId = $this->route('page')->id ?? null;

        return [
            // スラッグは必須（更新時は既存のスラッグがあるため）
            // ソフトデリートされたレコードは除外してユニークチェック
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('plg_dixlase_pages', 'slug')->ignore($pageId)->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'ogp_image_id' => ['nullable', 'exists:media,id'],
            'storage_type' => ['required', Rule::enum(ContentStorageType::class)],
            'editor_type' => ['required', Rule::enum(ContentEditorType::class)],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
        ];
    }

    /**
     * バリデーション後の追加チェック
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->title)) {
                $validator->errors()->add('title', __('dixlase-pages::admin/pages/validation.title_required'));
            }
        });
    }

    /**
     * バリデーション前の処理
     */
    protected function prepareForValidation(): void
    {
        // 日付指定以外の場合はpublished_atをクリア
        if ($this->status !== ContentStatus::SCHEDULED->value) {
            $this->merge(['published_at' => null]);
        }

        // 公開ステータスの場合は現在時刻を設定
        if ($this->status === ContentStatus::PUBLISHED->value) {
            $this->merge(['published_at' => now()]);
        }
    }

    /**
     * カスタムバリデーションメッセージ
     */
    public function messages(): array
    {
        return [
            'title.required' => __('dixlase-pages::admin/pages/validation.title_required'),
            'title.max' => __('dixlase-pages::admin/pages/validation.title_max'),
            'slug.regex' => __('dixlase-pages::admin/pages/validation.slug_format'),
            'content.required' => __('dixlase-pages::admin/pages/validation.content_required'),
            'status.required' => __('dixlase-pages::admin/pages/validation.status_required'),
            'published_at.date' => __('dixlase-pages::admin/pages/validation.published_at_date'),
            'published_at.after_or_equal' => __('dixlase-pages::admin/pages/validation.published_at_future'),
        ];
    }
}
