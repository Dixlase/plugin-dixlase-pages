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
     * Phase E adds the content body alongside the title. The content is
     * stored as the same source format (markdown / HTML / Blade) the
     * primary-locale row uses; the front-end renderer evaluates the
     * value verbatim. File-stored content keeps using the locale-aware
     * path under getContentByEditorType() rather than this resolver.
     *
     * @var list<string>
     */
    protected array $translatable = [
        'title',
        'content',
    ];

    /**
     * Table name
     */
    protected $table = 'plg_dixlase_pages';

    /**
     * Mass assignable attributes
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
     * Attributes to cast
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

        // When force deleting a page, also cascade delete SEO meta information and content files (not deleted on SoftDeletes)
        static::forceDeleted(function (self $page) {
            // Delete SEO meta information
            if (app()->has(SeoMetaProviderInterface::class)) {
                app(SeoMetaProviderInterface::class)
                    ->deleteMeta('dixlase-pages', (string) $page->id);
            }

            // When saved as file, also delete related files
            if ($page->storage_type === ContentStorageType::FILE) {
                $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);
                $contentService->deleteDirectory($page->slug);
            }
        });
    }

    /**
     * Status accessor (safe conversion)
     */
    public function getStatusAttribute($value): ContentStatus
    {
        // Convert old data
        return match ($value) {
            '0', 0, 'draft', null => ContentStatus::DRAFT,
            '1', 1, 'published' => ContentStatus::PUBLISHED,
            '2', 2, 'scheduled' => ContentStatus::SCHEDULED,
            default => ContentStatus::DRAFT,
        };
    }

    /**
     * Status mutator
     */
    public function setStatusAttribute($value): void
    {
        if ($value instanceof ContentStatus) {
            $this->attributes['status'] = $value->value;
        } elseif (is_int($value)) {
            $this->attributes['status'] = $value;
        } else {
            // If slug string, convert to enum and save int value
            $enum = ContentStatus::tryFromSlug((string) $value);
            $this->attributes['status'] = $enum ? $enum->value : ContentStatus::DRAFT->value;
        }
    }

    /**
     * Determine if published
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
     * Scope for public pages
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
     * Scope for draft pages
     */
    public function scopeDraft($query)
    {
        return $query->where('status', ContentStatus::DRAFT->value);
    }

    /**
     * Scope for scheduled pages
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', ContentStatus::SCHEDULED->value);
    }

    /**
     * Scope to retrieve pages of specified language
     */
    public function scopeForLang(Builder $query, string $lang): Builder
    {
        return $query->where('lang', $lang);
    }

    /**
     * Read the page body, picking the storage backend by storage_type.
     *
     * - FILE storage: load from disk under app()->getLocale(), so each
     *   locale already has its own file. The multilingual plugin is not
     *   consulted for file-stored content.
     * - DB storage: route through the TranslatableTrait so the
     *   multilingual plugin's resolver can return a per-locale
     *   translation when one is published. The trait falls back to the
     *   raw `content` column when no translation row exists or when the
     *   multilingual plugin is not installed.
     */
    public function getContentByEditorType(): ?string
    {
        if ($this->storage_type === ContentStorageType::FILE) {
            $contentService = app(\Plugins\DixlasePages\App\Services\DixlasePagesPageContentService::class);

            return $contentService->loadFromFile(
                $this->slug,
                app()->getLocale(),
                $this->editor_type?->slug() ?? 'html'
            );
        }

        return $this->getTranslation('content');
    }

    /**
     * Page URL accessor
     */
    public function getPageUrlAttribute(): string
    {
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        return url($pagesDirectory.'/'.$this->slug);
    }

    /**
     * Specify the factory location
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
