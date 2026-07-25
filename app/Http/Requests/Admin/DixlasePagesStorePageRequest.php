<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\MemberRole;
use App\Facades\SiteSettings;
use App\Rules\UniqueContentSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

class DixlasePagesStorePageRequest extends FormRequest
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
            // Slug is optional (auto-generated from title if empty).
            // Uniqueness is scoped by (parent_id, slug) so siblings cannot
            // collide but the same slug may appear under different parents
            // (and at the top level too — see withValidator for the
            // top-level safety net the DB unique key cannot enforce on
            // MySQL because NULL parent_id values are not equal).
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]*$/',
                UniqueContentSlug::for('plg_dixlase_pages')
                    ->where('parent_id', $this->input('parent_id')),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('plg_dixlase_pages', 'id')->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'storage_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStorageType::cases()))],
            'editor_type' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentEditorType::cases()))],
            'status' => ['required', Rule::in(array_map(fn ($case) => $case->slug(), ContentStatus::cases()))],
            'published_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after_or_equal:now'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'lang' => ['required', 'string', 'max:10'],
            // SEO meta (optional dependency, validation passes even if SEO plugin is disabled)
            'seo_meta' => ['nullable', 'array'],
            'seo_meta.description' => ['nullable', 'string', 'max:300'],
            'seo_meta.ogp_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }

    /**
     * Additional checks after validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (empty($this->title)) {
                $validator->errors()->add('title', __('dixlase-pages::admin/pages/validation.title_required'));
            }

            // Hierarchy depth limit: a new page becomes parent.depth() + 1
            // deep. Refuse parent assignments that would put the new page
            // beyond DixlasePagesPage::MAX_DEPTH.
            $parentId = $this->input('parent_id');
            if ($parentId !== null && $parentId !== '') {
                $parent = \Plugins\DixlasePages\App\Models\DixlasePagesPage::find($parentId);
                if ($parent !== null && $parent->depth() + 1 > \Plugins\DixlasePages\App\Models\DixlasePagesPage::MAX_DEPTH) {
                    $validator->errors()->add(
                        'parent_id',
                        __('dixlase-pages::admin/pages/validation.parent_depth_exceeded')
                    );
                }
            }
        });
    }

    /**
     * Pre-validation processing
     */
    protected function prepareForValidation(): void
    {
        // The parent_id <select> emits "" for the "Top-level page" option.
        // Coerce that to null before validation so the `nullable|integer`
        // rule chain skips cleanly and $validated['parent_id'] holds null
        // (not ""), which the controller then passes straight to MySQL.
        // Empty strings here would otherwise produce "set parent_id = "
        // and trigger SQLSTATE 22007 on save.
        if ($this->input('parent_id') === '') {
            $this->merge(['parent_id' => null]);
        }

        // <x-media.picker /> (from DixlaseSEO's <x-seo::meta-fields />)
        // submits 0 / "0" / "" when nothing is selected. The
        // `exists:media,id` rule then refuses 0 as a valid id and the
        // form rejects every save with "選択された seo meta.ogp media id は
        // 無効です", even though the field is supposed to be optional.
        // Normalise the "no selection" forms to null so the nullable
        // chain skips exists cleanly.
        $ogpId = $this->input('seo_meta.ogp_media_id');
        if ($ogpId === '' || $ogpId === '0' || $ogpId === 0) {
            $seoMeta = (array) $this->input('seo_meta', []);
            $seoMeta['ogp_media_id'] = null;
            $this->merge(['seo_meta' => $seoMeta]);
        }

        // If language is not specified (e.g. multilingual disabled, so the
        // language picker is not rendered), default to the site-wide locale
        // setting rather than the admin's current UI locale.
        if (empty($this->lang)) {
            $this->merge(['lang' => SiteSettings::get('locale', app()->getLocale())]);
        }

        // If slug is empty, auto-generate from title
        if (empty($this->slug) && ! empty($this->title)) {
            $this->merge(['slug' => $this->convertToSlug($this->title)]);
        }

        // Public permission check: force status to draft for members without permission
        $publishMinRole = (int) DixlasePagesPageSetting::getValue('publish_min_role', MemberRole::EDITOR->value);
        $member = Auth::guard('member')->user();
        if ($member && $member->role->value < $publishMinRole) {
            $this->merge([
                'status' => ContentStatus::DRAFT->slug(),
                'published_at' => null,
            ]);
        }

        // Clear published_at if not date-specified
        if ($this->status !== ContentStatus::SCHEDULED->slug()) {
            $this->merge(['published_at' => null]);
        }

        // Set current time if status is public
        if ($this->status === ContentStatus::PUBLISHED->slug()) {
            $this->merge(['published_at' => now()]);
        }
    }

    /**
     * Convert string to slug
     */
    protected function convertToSlug(string $text): string
    {
        // Romaji conversion map
        $romajiMap = [
            'あ' => 'a', 'い' => 'i', 'う' => 'u', 'え' => 'e', 'お' => 'o',
            'か' => 'ka', 'き' => 'ki', 'く' => 'ku', 'け' => 'ke', 'こ' => 'ko',
            'さ' => 'sa', 'し' => 'shi', 'す' => 'su', 'せ' => 'se', 'そ' => 'so',
            'た' => 'ta', 'ち' => 'chi', 'つ' => 'tsu', 'て' => 'te', 'と' => 'to',
            'な' => 'na', 'に' => 'ni', 'ぬ' => 'nu', 'ね' => 'ne', 'の' => 'no',
            'は' => 'ha', 'ひ' => 'hi', 'ふ' => 'fu', 'へ' => 'he', 'ほ' => 'ho',
            'ま' => 'ma', 'み' => 'mi', 'む' => 'mu', 'め' => 'me', 'も' => 'mo',
            'や' => 'ya', 'ゆ' => 'yu', 'よ' => 'yo',
            'ら' => 'ra', 'り' => 'ri', 'る' => 'ru', 'れ' => 're', 'ろ' => 'ro',
            'わ' => 'wa', 'を' => 'wo', 'ん' => 'n',
            'が' => 'ga', 'ぎ' => 'gi', 'ぐ' => 'gu', 'げ' => 'ge', 'ご' => 'go',
            'ざ' => 'za', 'じ' => 'ji', 'ず' => 'zu', 'ぜ' => 'ze', 'ぞ' => 'zo',
            'だ' => 'da', 'ぢ' => 'di', 'づ' => 'du', 'で' => 'de', 'ど' => 'do',
            'ば' => 'ba', 'び' => 'bi', 'ぶ' => 'bu', 'べ' => 'be', 'ぼ' => 'bo',
            'ぱ' => 'pa', 'ぴ' => 'pi', 'ぷ' => 'pu', 'ぺ' => 'pe', 'ぽ' => 'po',
            'っ' => '',
            'ア' => 'a', 'イ' => 'i', 'ウ' => 'u', 'エ' => 'e', 'オ' => 'o',
            'カ' => 'ka', 'キ' => 'ki', 'ク' => 'ku', 'ケ' => 'ke', 'コ' => 'ko',
            'サ' => 'sa', 'シ' => 'shi', 'ス' => 'su', 'セ' => 'se', 'ソ' => 'so',
            'タ' => 'ta', 'チ' => 'chi', 'ツ' => 'tsu', 'テ' => 'te', 'ト' => 'to',
            'ナ' => 'na', 'ニ' => 'ni', 'ヌ' => 'nu', 'ネ' => 'ne', 'ノ' => 'no',
            'ハ' => 'ha', 'ヒ' => 'hi', 'フ' => 'fu', 'ヘ' => 'he', 'ホ' => 'ho',
            'マ' => 'ma', 'ミ' => 'mi', 'ム' => 'mu', 'メ' => 'me', 'モ' => 'mo',
            'ヤ' => 'ya', 'ユ' => 'yu', 'ヨ' => 'yo',
            'ラ' => 'ra', 'リ' => 'ri', 'ル' => 'ru', 'レ' => 're', 'ロ' => 'ro',
            'ワ' => 'wa', 'ヲ' => 'wo', 'ン' => 'n',
            'ガ' => 'ga', 'ギ' => 'gi', 'グ' => 'gu', 'ゲ' => 'ge', 'ゴ' => 'go',
            'ザ' => 'za', 'ジ' => 'ji', 'ズ' => 'zu', 'ゼ' => 'ze', 'ゾ' => 'zo',
            'ダ' => 'da', 'ヂ' => 'di', 'ヅ' => 'du', 'デ' => 'de', 'ド' => 'do',
            'バ' => 'ba', 'ビ' => 'bi', 'ブ' => 'bu', 'ベ' => 'be', 'ボ' => 'bo',
            'パ' => 'pa', 'ピ' => 'pi', 'プ' => 'pu', 'ペ' => 'pe', 'ポ' => 'po',
            'ッ' => '',
        ];

        $result = mb_strtolower($text);

        // Convert hiragana and katakana to romaji
        foreach ($romajiMap as $kana => $romaji) {
            $result = str_replace($kana, $romaji, $result);
        }

        // Remove non-ASCII characters
        $result = preg_replace('/[^\x00-\x7F]/u', '', $result);

        // Convert spaces and underscores to hyphens
        $result = preg_replace('/[\s_]+/', '-', $result);

        // Remove anything other than alphanumeric characters and hyphens
        $result = preg_replace('/[^a-z0-9-]/', '', $result);

        // Collapse consecutive hyphens to one
        $result = preg_replace('/-+/', '-', $result);

        // Remove leading and trailing hyphens
        $result = trim($result, '-');

        return $result;
    }

    /**
     * Custom validation messages
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
