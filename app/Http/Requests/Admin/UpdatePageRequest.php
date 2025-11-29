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

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
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
            'storage_type' => ['required', Rule::enum(ContentStorageType::class)],
            'editor_type' => ['required', Rule::enum(ContentEditorType::class)],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
            
            // 翻訳データ
            'translations' => ['required', 'array'],
            // タイトルは各言語で任意だが、少なくとも1つは必須（カスタムバリデーションで対応）
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.meta_description' => ['nullable', 'string', 'max:500'],
            'translations.*.ogp_image_id' => ['nullable', 'exists:media,id'],
        ];
    }
    
    /**
     * バリデーション後の追加チェック
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 少なくとも1つの言語でタイトルが入力されているかチェック
            $translations = $this->translations ?? [];
            $hasTitle = false;
            
            foreach ($translations as $locale => $data) {
                if (!empty($data['title'])) {
                    $hasTitle = true;
                    break;
                }
            }
            
            if (!$hasTitle) {
                $validator->errors()->add('translations', __('dixlase-pages::admin.validation.at_least_one_title_required'));
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
            'title.required' => __('pages-plugin::admin.validation.title_required'),
            'title.max' => __('pages-plugin::admin.validation.title_max'),
            'slug.regex' => __('pages-plugin::admin.validation.slug_format'),
            'content.required' => __('pages-plugin::admin.validation.content_required'),
            'status.required' => __('pages-plugin::admin.validation.status_required'),
            'published_at.date' => __('pages-plugin::admin.validation.published_at_date'),
            'published_at.after_or_equal' => __('pages-plugin::admin.validation.published_at_future'),
        ];
    }
}
