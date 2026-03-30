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

use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Rules\UniqueContentSlug;
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
                UniqueContentSlug::for('plg_dixlase_pages')->ignore($pageId),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'storage_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStorageType::cases()))],
            'status' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStatus::cases()))],
            'published_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after_or_equal:now'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
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
        // 公開権限チェック: 権限がないメンバーはステータスを強制的にdraftに
        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);
        $member = Auth::guard('member')->user();
        if ($member && $member->role->value < $publishMinRole) {
            $this->merge([
                'status' => ContentStatus::DRAFT->slug(),
                'published_at' => null,
            ]);
        }

        // 日付指定以外の場合はpublished_atをクリア
        if ($this->status !== ContentStatus::SCHEDULED->slug()) {
            $this->merge(['published_at' => null]);
        }

        // 公開ステータスの場合は現在時刻を設定
        if ($this->status === ContentStatus::PUBLISHED->slug()) {
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
            'published_at.required_if' => __('dixlase-pages::admin/pages/validation.published_at_required'),
            'published_at.after_or_equal' => __('dixlase-pages::admin/pages/validation.published_at_future'),
        ];
    }
}
