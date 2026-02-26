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
            // スラッグは任意（空の場合はタイトルから自動生成）
            // ソフトデリートされたレコードは除外してユニークチェック
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]*$/',
                Rule::unique('plg_dixlase_pages', 'slug')->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'storage_type' => ['required', Rule::enum(ContentStorageType::class)],
            'editor_type' => ['required', Rule::enum(ContentEditorType::class)],
            'status' => ['required', Rule::enum(ContentStatus::class)],
            'published_at' => ['required_if:status,scheduled', 'nullable', 'date', 'after_or_equal:now'],
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
        // スラッグが空の場合、タイトルから自動生成
        if (empty($this->slug) && !empty($this->title)) {
            $this->merge(['slug' => $this->convertToSlug($this->title)]);
        }

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
     * 文字列をスラッグに変換
     */
    protected function convertToSlug(string $text): string
    {
        // ローマ字変換マップ
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

        // ひらがな・カタカナをローマ字に変換
        foreach ($romajiMap as $kana => $romaji) {
            $result = str_replace($kana, $romaji, $result);
        }

        // 非ASCII文字を削除
        $result = preg_replace('/[^\x00-\x7F]/u', '', $result);

        // 空白、アンダースコアをハイフンに変換
        $result = preg_replace('/[\s_]+/', '-', $result);

        // 英数字とハイフン以外を削除
        $result = preg_replace('/[^a-z0-9-]/', '', $result);

        // 連続するハイフンを1つに
        $result = preg_replace('/-+/', '-', $result);

        // 先頭と末尾のハイフンを削除
        $result = trim($result, '-');

        return $result;
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
            'slug.unique' => __('dixlase-pages::admin/pages/validation.slug_unique'),
            'content.required' => __('dixlase-pages::admin/pages/validation.content_required'),
            'status.required' => __('dixlase-pages::admin/pages/validation.status_required'),
            'published_at.date' => __('dixlase-pages::admin/pages/validation.published_at_date'),
            'published_at.required_if' => __('dixlase-pages::admin/pages/validation.published_at_required'),
            'published_at.after_or_equal' => __('dixlase-pages::admin/pages/validation.published_at_future'),
        ];
    }
}
