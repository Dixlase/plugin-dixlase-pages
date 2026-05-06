<?php

/**
 * This file is part of Dixlase Pages.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Plugins\DixlasePages\App\Models;

use App\Contracts\PluginIntegration\SeoMetaProviderInterface;
use App\Contracts\Revisionable;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Traits\HasRevisions;
use App\Traits\TranslatableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property string $lang Source locale of the row's column values.
 */
class DixlasePagesPage extends Model implements Revisionable
{
    use HasFactory, HasRevisions, SoftDeletes, TranslatableTrait;

    /**
     * Translatable fields. The DixlaseMultilingual plugin's
     * TranslationResolver (when bound) reads / writes these fields against
     * the polymorphic plg_dixlase_multilingual_translations table per
     * locale; without that plugin the trait silently falls back to the
     * raw column value, so existing single-locale installs keep working.
     *
     * Phase C ships title only. body / content translation lands when
     * richtext support is added to the multilingual editor.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'title',
    ];

    /**
     * テーブル名
     */
    protected $table = 'plg_dixlase_pages';

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'lang',
        'title',
        'content',
        'custom_css',
        'custom_js',
        'storage_type',
        'editor_type',
        'status',
        'published_at',
    ];

    /**
     * キャストする属性
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
        'storage_type' => ContentStorageType::class,
        'editor_type' => ContentEditorType::class,
    ];

    protected static function booted()
    {
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        // ページ強制削除時にSEOメタ情報・コンテンツファイルもカスケード削除（SoftDeletes時は消さない）
        static::forceDeleted(function (self $page) {
            // SEOメタ情報の削除
            if (app()->has(SeoMetaProviderInterface::class)) {
                app(SeoMetaProviderInterface::class)
                    ->deleteMeta('dixlase-pages', (string) $page->id);
            }

            // ファイル保存の場合、関連ファイルも削除
            if ($page->storage_type === ContentStorageType::FILE) {
                $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);
                $contentService->deleteDirectory($page->slug);
            }
        });
    }

    /**
     * ステータスのアクセサー（安全な変換）
     */
    public function getStatusAttribute($value): ContentStatus
    {
        // 古いデータの変換
        return match ($value) {
            '0', 0, 'draft', null => ContentStatus::DRAFT,
            '1', 1, 'published' => ContentStatus::PUBLISHED,
            '2', 2, 'scheduled' => ContentStatus::SCHEDULED,
            default => ContentStatus::DRAFT,
        };
    }

    /**
     * ステータスのミューテーター
     */
    public function setStatusAttribute($value): void
    {
        if ($value instanceof ContentStatus) {
            $this->attributes['status'] = $value->value;
        } elseif (is_int($value)) {
            $this->attributes['status'] = $value;
        } else {
            // スラッグ文字列の場合はenumに変換してint値を保存
            $enum = ContentStatus::tryFromSlug((string) $value);
            $this->attributes['status'] = $enum ? $enum->value : ContentStatus::DRAFT->value;
        }
    }

    /**
     * 公開されているかどうかを判定
     */
    public function isPublished(): bool
    {
        return match ($this->status) {
            ContentStatus::PUBLISHED => true,
            ContentStatus::SCHEDULED => $this->published_at && $this->published_at->isPast(),
            ContentStatus::DRAFT => false,
        };
    }

    /**
     * 公開可能なページのスコープ
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('status', ContentStatus::PUBLISHED->value)
                ->orWhere(function ($sq) {
                    $sq->where('status', ContentStatus::SCHEDULED->value)
                        ->where('published_at', '<=', now());
                });
        });
    }

    /**
     * 下書きページのスコープ
     */
    public function scopeDraft($query)
    {
        return $query->where('status', ContentStatus::DRAFT->value);
    }

    /**
     * 日付指定ページのスコープ
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', ContentStatus::SCHEDULED->value);
    }

    /**
     * 指定言語のページを取得するスコープ
     */
    public function scopeForLang(Builder $query, string $lang): Builder
    {
        return $query->where('lang', $lang);
    }

    /**
     * コンテンツを取得（エディタータイプに応じて）
     * ファイル保存の場合はファイルからコンテンツを読み込む
     * DB保存の場合は content カラムから読み込む
     */
    public function getContentByEditorType(): ?string
    {
        // ファイル保存の場合
        if ($this->storage_type === ContentStorageType::FILE) {
            $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);

            return $contentService->loadFromFile(
                $this->slug,
                app()->getLocale(),
                $this->editor_type?->slug() ?? 'html'
            );
        }

        // DB保存の場合は content カラムから読み込む
        return $this->content;
    }

    /**
     * ページURLのアクセサー
     */
    public function getPageUrlAttribute(): string
    {
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        return url($pagesDirectory.'/'.$this->slug);
    }

    /**
     * ファクトリーの場所を指定
     */
    protected static function newFactory()
    {
        return \Plugins\DixlasePages\Database\Factories\DixlasePagesPageFactory::new();
    }

    public function revisionModel(): string
    {
        return DixlasePagesPageRevision::class;
    }

    public function revisionForeignKey(): string
    {
        return 'page_id';
    }

    /**
     * @return list<string>
     */
    public function revisionableFields(): array
    {
        return [
            'slug',
            'lang',
            'title',
            'content',
            'custom_css',
            'custom_js',
            'storage_type',
            'editor_type',
            'status',
            'published_at',
        ];
    }
}
