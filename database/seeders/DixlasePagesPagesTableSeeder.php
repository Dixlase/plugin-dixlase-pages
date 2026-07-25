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

namespace Plugins\DixlasePages\Database\Seeders;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use Illuminate\Database\Seeder;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

class DixlasePagesPagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = array_merge(
            $this->getJapanesePages(),
            $this->getEnglishPages(),
        );

        foreach ($pages as $pageData) {
            DixlasePagesPage::firstOrCreate(
                [
                    'slug' => $pageData['slug'],
                    'lang' => $pageData['lang'],
                ],
                array_merge($pageData, [
                    'status' => 'published',
                    'published_at' => now(),
                ]),
            );
        }
    }

    /**
     * Get Japanese sample pages.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getJapanesePages(): array
    {
        return [
            [
                'title' => 'Markdownサンプルページ',
                'slug' => 'markdown-sample-ja',
                'lang' => 'ja',
                'editor_type' => ContentEditorType::MARKDOWN,
                'storage_type' => ContentStorageType::DATABASE,
                'content' => $this->getJapaneseMarkdownContent(),
            ],
            [
                'title' => 'HTMLサンプルページ',
                'slug' => 'html-sample-ja',
                'lang' => 'ja',
                'editor_type' => ContentEditorType::HTML,
                'storage_type' => ContentStorageType::DATABASE,
                'content' => $this->getJapaneseHtmlContent(),
            ],
        ];
    }

    /**
     * Get English sample pages.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getEnglishPages(): array
    {
        return [
            [
                'title' => 'Markdown Sample Page',
                'slug' => 'markdown-sample-en',
                'lang' => 'en',
                'editor_type' => ContentEditorType::MARKDOWN,
                'storage_type' => ContentStorageType::DATABASE,
                'content' => $this->getEnglishMarkdownContent(),
            ],
            [
                'title' => 'HTML Sample Page',
                'slug' => 'html-sample-en',
                'lang' => 'en',
                'editor_type' => ContentEditorType::HTML,
                'storage_type' => ContentStorageType::DATABASE,
                'content' => $this->getEnglishHtmlContent(),
            ],
        ];
    }

    /**
     * Get Japanese Markdown sample content.
     */
    private function getJapaneseMarkdownContent(): string
    {
        return <<<'MARKDOWN'
## Markdownサンプルページへようこそ

このページは **Markdownエディタ** で作成されたサンプルです。Markdownの基本的な書き方をご紹介します。

---

### テキストの装飾

**太字テキスト** はアスタリスク2つで囲みます。

*斜体テキスト* はアスタリスク1つで囲みます。

~~取り消し線~~ はチルダ2つで囲みます。

### リスト

#### 箇条書きリスト
- りんご
- みかん
- バナナ
  - 甘いバナナ
  - 青いバナナ

#### 番号付きリスト
1. 最初の項目
2. 次の項目
3. 最後の項目

### リンクと画像

[Dixlase公式サイト](https://example.com) のようにリンクを作成できます。

### 引用

> Markdownはシンプルで読みやすい記法です。
> HTMLを知らなくても、簡単に美しいページを作成できます。

### コードブロック

インラインコードは `このように` バッククォートで囲みます。

```
複数行のコードブロックは
バッククォート3つで囲みます。
```

### テーブル

| 機能 | 説明 |
|------|------|
| 見出し | `#` の数で見出しレベルを指定 |
| リスト | `-` で箇条書き、`1.` で番号付き |
| リンク | `[テキスト](URL)` の形式 |
| 画像 | `![代替テキスト](画像URL)` の形式 |

---

*このページは自由に編集できます。Markdownの練習にお使いください。*
MARKDOWN;
    }

    /**
     * Get Japanese HTML sample content.
     */
    private function getJapaneseHtmlContent(): string
    {
        return <<<'HTML'
<h2>HTMLサンプルページへようこそ</h2>

<p>このページは <strong>HTMLエディタ</strong> で作成されたサンプルです。HTMLタグを直接記述して、自由にページをデザインできます。</p>

<hr>

<h3>セクション構成</h3>

<section style="background-color: #f0f9ff; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    <h4 style="color: #1e40af; margin-top: 0;">お知らせ</h4>
    <p>HTMLエディタでは、スタイル属性やセクション要素を使って、視覚的に豊かなレイアウトを作成できます。</p>
</section>

<section style="background-color: #f0fdf4; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    <h4 style="color: #166534; margin-top: 0;">機能紹介</h4>
    <ul>
        <li>見出しとテキストの装飾</li>
        <li>セクションのレイアウト</li>
        <li>テーブルの作成</li>
        <li>リンクやボタンの設置</li>
    </ul>
</section>

<h3>テーブルの例</h3>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
    <thead>
        <tr style="background-color: #f1f5f9;">
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">エディタ</th>
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">特徴</th>
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">おすすめ</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Markdown</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">シンプルな記法で素早く執筆</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">ブログ・ドキュメント</td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">HTML</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">自由度の高いデザインが可能</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">ランディングページ・特設ページ</td>
        </tr>
    </tbody>
</table>

<h3>ボタンの例</h3>

<p>
    <a href="#" style="display: inline-block; padding: 0.5rem 1.5rem; background-color: #2563eb; color: #ffffff; border-radius: 0.375rem; text-decoration: none; font-weight: 500;">詳細を見る</a>
    <a href="#" style="display: inline-block; padding: 0.5rem 1.5rem; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; font-weight: 500; margin-left: 0.5rem;">キャンセル</a>
</p>

<hr>

<p><em>このページは自由に編集できます。HTMLの練習にお使いください。</em></p>
HTML;
    }

    /**
     * Get English Markdown sample content.
     */
    private function getEnglishMarkdownContent(): string
    {
        return <<<'MARKDOWN'
## Welcome to the Markdown Sample Page

This page was created with the **Markdown editor**. Here is a guide to basic Markdown syntax.

---

### Text Formatting

**Bold text** is wrapped with double asterisks.

*Italic text* is wrapped with single asterisks.

~~Strikethrough~~ is wrapped with double tildes.

### Lists

#### Unordered List
- Apple
- Orange
- Banana
  - Sweet banana
  - Green banana

#### Ordered List
1. First item
2. Second item
3. Third item

### Links and Images

You can create links like [Dixlase Official Site](https://example.com).

### Blockquotes

> Markdown is a simple and readable syntax.
> You can create beautiful pages without knowing HTML.

### Code Blocks

Inline code is wrapped with `backticks` like this.

```
Multi-line code blocks are
wrapped with triple backticks.
```

### Tables

| Feature | Description |
|---------|-------------|
| Headings | Use `#` to specify heading levels |
| Lists | Use `-` for bullets, `1.` for numbered |
| Links | Use `[text](URL)` format |
| Images | Use `![alt text](image URL)` format |

---

*Feel free to edit this page. Use it to practice Markdown.*
MARKDOWN;
    }

    /**
     * Get English HTML sample content.
     */
    private function getEnglishHtmlContent(): string
    {
        return <<<'HTML'
<h2>Welcome to the HTML Sample Page</h2>

<p>This page was created with the <strong>HTML editor</strong>. You can design pages freely by writing HTML tags directly.</p>

<hr>

<h3>Section Layout</h3>

<section style="background-color: #f0f9ff; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    <h4 style="color: #1e40af; margin-top: 0;">Announcements</h4>
    <p>With the HTML editor, you can create visually rich layouts using style attributes and section elements.</p>
</section>

<section style="background-color: #f0fdf4; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1rem;">
    <h4 style="color: #166534; margin-top: 0;">Features</h4>
    <ul>
        <li>Headings and text decoration</li>
        <li>Section layouts</li>
        <li>Table creation</li>
        <li>Links and button placement</li>
    </ul>
</section>

<h3>Table Example</h3>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 1rem;">
    <thead>
        <tr style="background-color: #f1f5f9;">
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">Editor</th>
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">Characteristics</th>
            <th style="padding: 0.75rem; border: 1px solid #e2e8f0; text-align: left;">Recommended For</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Markdown</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Quick writing with simple syntax</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Blogs &amp; documentation</td>
        </tr>
        <tr>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">HTML</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Highly flexible design</td>
            <td style="padding: 0.75rem; border: 1px solid #e2e8f0;">Landing pages &amp; special pages</td>
        </tr>
    </tbody>
</table>

<h3>Button Examples</h3>

<p>
    <a href="#" style="display: inline-block; padding: 0.5rem 1.5rem; background-color: #2563eb; color: #ffffff; border-radius: 0.375rem; text-decoration: none; font-weight: 500;">Learn More</a>
    <a href="#" style="display: inline-block; padding: 0.5rem 1.5rem; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; font-weight: 500; margin-left: 0.5rem;">Cancel</a>
</p>

<hr>

<p><em>Feel free to edit this page. Use it to practice HTML.</em></p>
HTML;
    }
}
