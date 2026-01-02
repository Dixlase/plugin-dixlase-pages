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

namespace Plugins\DixlasePages\Database\Seeders;

use Illuminate\Database\Seeder;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

class DixlasePagesPagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 基本設定の言語を取得
        $appLocale = config('app.locale', 'ja');
        $isJapaneseLocale = in_array($appLocale, ['ja', 'ja_JP']);

        // 言語に応じた固定ページを定義
        $pages = $isJapaneseLocale ? $this->getJapanesePages() : $this->getEnglishPages();

        DixlasePagesPage::truncate();

        foreach ($pages as $pageData) {
            DixlasePagesPage::create(array_merge($pageData, [
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 開発環境でのみファクトリーデータを生成
        if (app()->environment('local', 'development')) {
            DixlasePagesPage::factory()->count(20)->create();
        }
    }

    /**
     * 日本語の固定ページを取得
     */
    private function getJapanesePages(): array
    {
        return [
            [
                'title' => 'プライバシーポリシー',
                'slug' => 'privacy-policy',
                'content' => "## プライバシーポリシー\n\n当サイトでは、お客様の個人情報保護を重要な責務と考え、以下のプライバシーポリシーを定めております。\n\n### 個人情報の収集について\n\n当サイトでは、お客様により良いサービスを提供するため、必要最小限の個人情報を収集させていただく場合があります。\n\n### 個人情報の利用目的\n\n収集した個人情報は、以下の目的で利用いたします：\n- サービスの提供・運営\n- お客様からのお問い合わせへの対応\n- サービス改善のための分析\n\n### 個人情報の第三者提供\n\n当サイトでは、法令に基づく場合を除き、お客様の同意なく個人情報を第三者に提供することはありません。",
            ],
            [
                'title' => '利用規約',
                'slug' => 'terms-of-service',
                'content' => "## 利用規約\n\n本利用規約は、当サイトが提供するサービスの利用条件を定めるものです。\n\n### 第1条（適用）\n\n本規約は、ユーザーと当サイトとの間の本サービスの利用に関わる一切の関係に適用されるものとします。\n\n### 第2条（利用登録）\n\n本サービスにおいて、登録希望者が当サイトの定める方法によって利用登録を申請し、当サイトがこれを承認することによって、利用登録が完了するものとします。\n\n### 第3条（禁止事項）\n\nユーザーは、本サービスの利用にあたり、以下の行為をしてはなりません：\n- 法令または公序良俗に違反する行為\n- 犯罪行為に関連する行為\n- 当サイト、本サービスの他のユーザー、または第三者のサーバーまたはネットワークの機能を破壊したり、妨害したりする行為\n\n### 第4条（本サービスの提供の停止等）\n\n当サイトは、以下のいずれかの事由があると判断した場合、ユーザーに事前に通知することなく本サービスの全部または一部の提供を停止または中断することができるものとします。",
            ],
            [
                'title' => 'お問い合わせ',
                'slug' => 'contact',
                'content' => "## お問い合わせ\n\nサービスに関するご質問やご不明な点がございましたら、お気軽にお問い合わせください。\n\n### お問い合わせ方法\n\n以下の方法でお問い合わせいただけます：\n\n**メールでのお問い合わせ**\nメールアドレス: info@example.com\n\n**お問い合わせフォーム**\n専用のお問い合わせフォームからもご連絡いただけます。\n\n### 対応時間\n\n平日 9:00〜18:00（土日祝日を除く）\n\n※お問い合わせの内容によっては、回答までにお時間をいただく場合がございます。あらかじめご了承ください。",
            ],
        ];
    }

    /**
     * 英語の固定ページを取得
     */
    private function getEnglishPages(): array
    {
        return [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "## Privacy Policy\n\nWe consider the protection of our customers' personal information to be an important responsibility and have established the following privacy policy.\n\n### Collection of Personal Information\n\nOur website may collect minimal personal information necessary to provide better services to our customers.\n\n### Purpose of Use of Personal Information\n\nThe personal information we collect will be used for the following purposes:\n- Providing and operating services\n- Responding to customer inquiries\n- Analysis for service improvement\n\n### Provision of Personal Information to Third Parties\n\nWe will not provide personal information to third parties without customer consent, except as required by law.",
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "## Terms of Service\n\nThese Terms of Service define the conditions for using the services provided by our website.\n\n### Article 1 (Application)\n\nThese terms shall apply to all relationships regarding the use of this service between users and our website.\n\n### Article 2 (User Registration)\n\nIn this service, user registration is completed when a registration applicant applies for user registration by the method specified by our website and our website approves this.\n\n### Article 3 (Prohibited Acts)\n\nUsers shall not engage in any of the following acts when using this service:\n- Acts that violate laws or public order and morals\n- Acts related to criminal activities\n- Acts that destroy or interfere with the functions of our website, other users of this service, or third-party servers or networks\n\n### Article 4 (Suspension of Service Provision)\n\nOur website may suspend or interrupt the provision of all or part of this service without prior notice to users if we determine that any of the following reasons exist.",
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact',
                'content' => "## Contact Us\n\nIf you have any questions or concerns about our service, please feel free to contact us.\n\n### Contact Methods\n\nYou can contact us through the following methods:\n\n**Email Contact**\nEmail Address: info@example.com\n\n**Contact Form**\nYou can also contact us through our dedicated contact form.\n\n### Business Hours\n\nWeekdays 9:00 AM - 6:00 PM (excluding weekends and holidays)\n\n*Please note that depending on the nature of your inquiry, it may take some time to respond. Thank you for your understanding.",
            ],
        ];
    }
}
