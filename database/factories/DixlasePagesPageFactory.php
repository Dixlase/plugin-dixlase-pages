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


namespace Plugins\DixlasePages\Database\Factories;


use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Plugins\DixlasePages\App\Models\DixlasePagesPage>
 */
class DixlasePagesPageFactory extends Factory
{

    protected $model = DixlasePagesPage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 基本設定の言語を取得
        $appLocale = config('app.locale', 'ja');
        $isJapaneseLocale = in_array($appLocale, ['ja', 'ja_JP']);

        // 日本語と英語のタイトル候補（利用規約・プライバシーポリシーは除外）
        $japaneseTitles = [
            'お知らせ',
            'サービス紹介',
            '会社概要',
            'よくある質問',
            'お問い合わせ',
            'ニュース',
            'イベント情報',
            '製品情報',
            'サポート',
            'ブログ記事',
            '採用情報',
            'メディア掲載',
        ];

        $englishTitles = [
            'About Us',
            'Contact Information',
            'News & Updates',
            'Product Features',
            'Getting Started',
            'FAQ Section',
            'Support Center',
            'Company Profile',
            'Service Overview',
            'Event Information',
            'Career Opportunities',
            'Media Coverage',
        ];

        // 基本設定の言語に応じてタイトルを選択（80%の確率でメイン言語、20%で他言語）
        $isJapanese = $isJapaneseLocale 
            ? $this->faker->boolean(80) 
            : $this->faker->boolean(20);
        
        $title = $isJapanese 
            ? $this->faker->randomElement($japaneseTitles)
            : $this->faker->randomElement($englishTitles);

        // コンテンツも言語に応じて生成
        $content = $this->generateMixedContent($isJapanese);

        return [
            'title' => $title,
            'slug' => Str::slug($title . '-' . $this->faker->unique()->numberBetween(1, 9999)), // 重複を避けるため番号追加
            'lang' => config('language.default', 'en'), // 言語コード
            'status' => $this->faker->randomElement(['draft', 'published', 'scheduled']), // Enumに対応
            'published_at' => $this->faker->boolean(70) ? now() : null, // 70%の確率で公開日時を設定
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * 日本語と英語を混同したコンテンツを生成
     */
    private function generateMixedContent(bool $primaryJapanese = true): string
    {
        $japaneseParagraphs = [
            'このページでは、弊社のサービスについて詳しくご紹介いたします。お客様のニーズに合わせた最適なソリューションを提供しております。',
            '私たちは、革新的な技術と豊富な経験を活かして、お客様の課題解決をサポートいたします。',
            'ご不明な点がございましたら、お気軽にお問い合わせください。専門スタッフが丁寧にご対応いたします。',
            '品質の高いサービスを通じて、お客様の成功をお手伝いすることが私たちの使命です。',
            '最新の情報については、定期的にこちらのページをご確認ください。',
        ];

        $englishParagraphs = [
            'Welcome to our comprehensive service platform. We provide innovative solutions tailored to meet your specific business requirements.',
            'Our team of experienced professionals is dedicated to delivering exceptional results and outstanding customer satisfaction.',
            'For more information about our services, please feel free to contact our support team. We are here to help you succeed.',
            'We continuously strive to improve our offerings and stay at the forefront of industry developments.',
            'Thank you for choosing our services. We look forward to building a long-lasting partnership with you.',
        ];

        $paragraphs = [];
        $totalParagraphs = $this->faker->numberBetween(2, 4);

        for ($i = 0; $i < $totalParagraphs; $i++) {
            // プライマリ言語を70%、セカンダリ言語を30%の確率で選択
            $useJapanese = $primaryJapanese 
                ? $this->faker->boolean(70) 
                : $this->faker->boolean(30);

            if ($useJapanese) {
                $paragraphs[] = $this->faker->randomElement($japaneseParagraphs);
            } else {
                $paragraphs[] = $this->faker->randomElement($englishParagraphs);
            }
        }

        return implode("\n\n", $paragraphs);
    }

    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the page is draft.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the page is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'scheduled',
            'published_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Generate Japanese content primarily.
     */
    public function japanese(): static
    {
        return $this->state(function (array $attributes) {
            $japaneseTitles = [
                'お知らせ',
                'サービス紹介', 
                '会社概要',
                'よくある質問',
                'お問い合わせ',
                'ニュース',
                'イベント情報',
                '製品情報',
                'サポート',
                'ブログ記事',
                '採用情報',
                'メディア掲載',
            ];
            
            $title = $this->faker->randomElement($japaneseTitles);
            
            return [
                'title' => $title,
                'slug' => Str::slug($title . '-' . $this->faker->unique()->numberBetween(1, 9999)),
                'content' => $this->generateMixedContent(true),
            ];
        });
    }

    /**
     * Generate English content primarily.
     */
    public function english(): static
    {
        return $this->state(function (array $attributes) {
            $englishTitles = [
                'About Us',
                'Contact Information',
                'News & Updates',
                'Product Features',
                'Getting Started',
                'FAQ Section',
                'Support Center',
                'Company Profile',
                'Service Overview',
                'Event Information',
                'Career Opportunities',
                'Media Coverage',
            ];
            
            $title = $this->faker->randomElement($englishTitles);
            
            return [
                'title' => $title,
                'slug' => Str::slug($title . '-' . $this->faker->unique()->numberBetween(1, 9999)),
                'content' => $this->generateMixedContent(false),
            ];
        });
    }
}
