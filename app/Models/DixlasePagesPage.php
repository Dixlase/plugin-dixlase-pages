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

namespace Plugins\DixlasePages\App\Models;

use App\Contracts\Content\DeclaresScriptCapableParts;
use App\Contracts\Content\PublishesFileContent;
use App\Contracts\PluginIntegration\SeoMetaProviderInterface;
use App\Contracts\Revisionable;
use App\Contracts\TranslationResolver;
use App\Enums\ContentEditorType;
use App\Enums\ContentPublishMode;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Services\Content\FileContentPublisher;
use App\Traits\HasRevisions;
use App\Traits\TranslatableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Plugins\DixlasePages\App\Services\DixlasePagesPageContentService;
use Plugins\DixlasePages\App\Services\ScriptAuthoringPolicy;

/**
 * @property string $lang Source locale of the row's column values.
 */
class DixlasePagesPage extends Model implements DeclaresScriptCapableParts, PublishesFileContent, Revisionable
{
    use HasFactory, HasRevisions, SoftDeletes, TranslatableTrait;

    /**
     * Translatable fields. When a TranslationResolver is bound, it reads /
     * writes these fields per locale; without one the trait silently falls
     * back to the raw column value, so existing single-locale installs keep
     * working.
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
        'parent_id',
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
     * Maximum depth of the page hierarchy. depth() of a top-level page is
     * 0; with MAX_DEPTH = 2 we accept three URL segments (/page/a/b/c).
     * Validators consult this constant to refuse assignments that would
     * push the resulting page beyond the limit.
     */
    public const MAX_DEPTH = 2;

    /**
     * The plugin setting that holds how file-stored pages are published
     * (a {@see ContentPublishMode} value)
     */
    public const PUBLISH_MODE_SETTING = 'publish_mode';

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

        // Promote children to top-level when a parent is removed (soft or
        // force). Without this, children's URLs would resolve to "parent
        // missing -> 404" because the resolver walks from the root by
        // (parent_id, slug). Restoring the parent later leaves the
        // already-promoted children at the top level; reattaching them is
        // a manual operation.
        static::deleting(function (self $page) {
            self::where('parent_id', $page->id)->update(['parent_id' => null]);
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
     * Scope to top-level pages (no parent).
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Parent page (null for top-level pages).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct children.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Walk from the root to this page, returning ancestors plus self
     * in root-to-self order. Used for URL composition and breadcrumbs.
     * Returns just [self] for top-level pages.
     *
     * @return array<int, self>
     */
    public function ancestorsAndSelf(): array
    {
        $chain = [$this];
        $current = $this;
        while ($current->parent_id !== null) {
            $parent = $current->parent()->first();
            if ($parent === null) {
                break;
            }
            array_unshift($chain, $parent);
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Number of ancestors above this page. 0 for top-level pages.
     */
    public function depth(): int
    {
        return max(0, count($this->ancestorsAndSelf()) - 1);
    }

    /**
     * Slugs from root to this page. /page/parent/child returns
     * ['parent', 'child'] for the child page.
     *
     * @return array<int, string>
     */
    public function pathSegments(): array
    {
        return array_map(static fn (self $p) => (string) $p->slug, $this->ancestorsAndSelf());
    }

    /**
     * Maximum descendant depth within this page's subtree, measured
     * relative to self. A leaf returns 0; a parent of a single child
     * returns 1. Used by validation to refuse parent assignments that
     * would push any descendant beyond MAX_DEPTH.
     */
    public function subtreeMaxDepth(): int
    {
        $max = 0;
        foreach ($this->children()->get() as $child) {
            $max = max($max, 1 + $child->subtreeMaxDepth());
        }

        return $max;
    }

    /**
     * Ids of self plus every descendant. Used by validation to refuse
     * a parent_id that points inside this page's own subtree (which
     * would create a cycle).
     *
     * @return array<int, int>
     */
    public function subtreeIds(): array
    {
        $ids = [(int) $this->id];
        foreach ($this->children()->get() as $child) {
            $ids = array_merge($ids, $child->subtreeIds());
        }

        return $ids;
    }

    /**
     * Walk the URL path segment-by-segment and return the final page.
     *
     * Each segment is matched as (slug, parent_id) where parent_id is
     * NULL for the first segment and the previously-matched page's id
     * for each subsequent segment. Returns null as soon as any segment
     * fails to resolve. Mirrors the original single-slug route's locale
     * fallback: try the current locale first, then the site primary
     * locale (so a primary-locale row with multilingual overlay still
     * resolves for non-primary visitors).
     *
     * @param  string  $path           Raw URL path, e.g. "parent/child"
     * @param  string  $locale         Current request locale
     * @param  bool    $publishedOnly  Hide draft / future-scheduled rows
     *                                 (false for admin preview)
     */
    public static function resolvePath(string $path, string $locale, bool $publishedOnly): ?self
    {
        $segments = array_values(array_filter(
            explode('/', trim($path, '/')),
            static fn (string $s) => $s !== ''
        ));
        if ($segments === []) {
            return null;
        }

        $primaryLocale = \App\Helpers\LocaleHelper::getSiteDefaultLocale();
        $parentId = null;
        $page = null;

        foreach ($segments as $segment) {
            $base = static::where('slug', $segment);
            if ($parentId === null) {
                $base->whereNull('parent_id');
            } else {
                $base->where('parent_id', $parentId);
            }
            if ($publishedOnly) {
                $base->published();
            }

            $found = (clone $base)->forLang($locale)->first();
            if ($found === null && $primaryLocale !== '' && $primaryLocale !== $locale) {
                $found = (clone $base)->forLang($primaryLocale)->first();
            }
            if ($found === null) {
                // Final fallback: the page may be authored in a locale that is
                // neither the request locale nor the site primary — e.g. a
                // Japanese-source page on an English-primary site, written in
                // Japanese first and translated to English afterwards. Match
                // the row by slug regardless of its authoring lang; the
                // multilingual overlay then supplies the right translation for
                // the request locale at render time, falling back to the
                // source-language content when no overlay exists. Deterministic
                // by oldest id for the legacy one-row-per-language layout.
                $found = (clone $base)->orderBy('id')->first();
            }

            if ($found === null) {
                return null;
            }

            $parentId = $found->id;
            $page = $found;
        }

        return $page;
    }

    /**
     * Read the page body, picking the storage backend by storage_type.
     *
     * - FILE storage: prefer a multilingual translation overlay for the
     *   current locale when one is published; only fall back to the
     *   page's published copy when no overlay exists. Core's
     *   FileContentPublisher decides what that copy is: the content saved
     *   from the admin, or the file on disk under the file save publish
     *   mode. The overlay
     *   takes priority because the central translation editor is the
     *   discoverable place to manage non-source locales, and a stale
     *   per-locale file (e.g. an `en/html.html` that was created with
     *   source-locale text and never updated) would otherwise mask the
     *   newer overlay content the operator just saved.
     * - DB storage: route through the TranslatableTrait so the
     *   bound TranslationResolver can return a per-locale translation
     *   when one is published. The trait falls back to the raw `content`
     *   column when no translation row exists or when no translation
     *   provider is installed.
     */
    public function getContentByEditorType(): ?string
    {
        if ($this->storage_type === ContentStorageType::FILE) {
            $locale = app()->getLocale();

            $overlay = $this->resolveMultilingualOverlay('content', $locale);
            if ($overlay !== null && $overlay !== '') {
                return $overlay;
            }

            // The published copy: the file itself only under the file save
            // publish mode, which takes each change in as it is seen.
            return app(FileContentPublisher::class)->resolve($this, 'content');
        }

        return $this->getTranslation('content');
    }

    /**
     * Override TranslatableTrait::getAttribute() for translatable fields so
     * the lookup only honours an overlay row whose locale matches the
     * current request locale exactly. The trait's stock implementation
     * falls back through config('app.fallback_locale') before reaching
     * the raw column, which on a site whose primary locale is, say,
     * Japanese silently lets a stale English overlay shadow the
     * Japanese source-column value. Anything off the translatable list
     * goes through Eloquent's normal getAttribute() untouched.
     */
    public function getAttribute($key): mixed
    {
        if (
            in_array($key, $this->translatable ?? [], true)
            && App::bound(TranslationResolver::class)
        ) {
            $overlay = $this->resolveMultilingualOverlay($key, App::getLocale());
            if ($overlay !== null && $overlay !== '') {
                return $overlay;
            }
            // Skip TranslatableTrait's defaultLocale fallback step and read
            // straight from the column via Eloquent's base accessor.
            return Model::getAttribute($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Same short-circuit as getAttribute() for explicit getTranslation()
     * call sites (e.g. the front-page view's @section('title')): honour
     * an overlay only when one exists for the requested locale and fall
     * through to the raw column otherwise. This intentionally diverges
     * from TranslatableTrait::getTranslation()'s defaultLocale step so
     * a non-source-locale overlay cannot shadow the source-column value.
     */
    public function getTranslation(string $field, ?string $locale = null, bool $fallback = true): mixed
    {
        if (App::bound(TranslationResolver::class)) {
            $locale = $locale ?? App::getLocale();
            $overlay = $this->resolveMultilingualOverlay($field, $locale);
            if ($overlay !== null && $overlay !== '') {
                return $overlay;
            }
        }

        return $this->getOriginalValue($field);
    }

    /**
     * Primary-locale SEO meta description, sourced from DixlaseSEO's meta
     * store. Exposed as the `seo_description` attribute so the central
     * translation manager shows it as the source reference when editing
     * the page's per-locale `seo_description` translations (declared as a
     * translatable field in plugin.json). Returns null when DixlaseSEO is
     * not installed; never persisted on this model.
     */
    public function getSeoDescriptionAttribute(): ?string
    {
        if (! App::bound(SeoMetaProviderInterface::class)) {
            return null;
        }

        try {
            $meta = App::make(SeoMetaProviderInterface::class)
                ->getMeta('dixlase-pages', (string) $this->getKey());

            return $meta?->description;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Ask the bound TranslationResolver directly for a translation
     * value, bypassing TranslatableTrait::getTranslation()'s fallback
     * chain to the default locale and the raw column.
     *
     * Returns null when no resolver is bound (no translation provider is
     * installed, or its master toggle is off) or when no published
     * translation exists for this (entity, locale, field). The FILE
     * branch of getContentByEditorType() uses that null to drop through
     * to its disk-file fallback rather than mistakenly returning the
     * raw `content` column (which would be the source-locale text).
     */
    private function resolveMultilingualOverlay(string $field, string $locale): ?string
    {
        if (! App::bound(TranslationResolver::class)) {
            return null;
        }

        $resolver = App::make(TranslationResolver::class);
        $value = $resolver->resolve($this, $field, $locale);

        return is_string($value) ? $value : null;
    }

    /**
     * Page URL accessor. Composes the full hierarchical path from the
     * root to this page (e.g. /page/parent/child for a child page).
     */
    public function getPageUrlAttribute(): string
    {
        $pagesDirectory = DixlasePagesPageSetting::getValue('route_slug', 'page');

        return url($pagesDirectory.'/'.implode('/', $this->pathSegments()));
    }

    /**
     * Specify the factory location
     */
    protected static function newFactory()
    {
        return \Plugins\DixlasePages\Database\Factories\DixlasePagesPageFactory::new();
    }

    public function usesFileStorage(): bool
    {
        return $this->storage_type === ContentStorageType::FILE;
    }

    /**
     * Whether the file save mode takes effect in this environment
     *
     * Taking file changes in without anyone publishing them is a development
     * convenience, so it applies only in a local environment (#102). Anywhere
     * else -- staging, production, a mistyped APP_ENV -- every change to the
     * files waits to be published, whatever the setting says. The setting is
     * kept as chosen, so a copy of the site in a local environment picks it
     * up again.
     */
    public static function fileSaveModeAvailable(): bool
    {
        return App::environment('local');
    }

    public function fileContentPublishMode(): ContentPublishMode
    {
        if (! self::fileSaveModeAvailable()) {
            return ContentPublishMode::ADMIN;
        }

        return ContentPublishMode::fromSetting(DixlasePagesPageSetting::getValue(self::PUBLISH_MODE_SETTING));
    }

    /**
     * The parts of a page that may carry a script
     *
     * In a local environment none are held back: whoever can write the files
     * there runs the code as well, so the file save mode takes every part in
     * at once (#102). Elsewhere the file save mode never takes effect, and
     * the answer is the true one -- the custom CSS and JS, and the body under
     * the HTML editor, where `<script>` is part of what the author writes.
     *
     * @return list<string>
     */
    public function scriptCapableFileContentParts(): array
    {
        if (self::fileSaveModeAvailable()) {
            return [];
        }

        $parts = FileContentPublisher::DEFAULT_SCRIPT_CAPABLE_PARTS;

        if ($this->editorType() === ContentEditorType::HTML) {
            $parts[] = 'content';
        }

        return $parts;
    }

    /**
     * The body, plus the custom CSS (HTML and Markdown) and JS (HTML alone)
     *
     * @return array<string, string>
     */
    public function fileContentParts(): array
    {
        $parts = ['content' => 'content'];

        if (ScriptAuthoringPolicy::carriesCustomCss($this->editorType())) {
            $parts['custom_css'] = 'custom_css';
        }
        if (ScriptAuthoringPolicy::carriesCustomJs($this->editorType())) {
            $parts['custom_js'] = 'custom_js';
        }

        return $parts;
    }

    public function fileContentPath(string $part): string
    {
        $files = app(DixlasePagesPageContentService::class);
        $locale = (string) $this->lang;

        return match ($part) {
            'custom_css' => $files->getCssFilePath($this->slug, $locale),
            'custom_js' => $files->getJsFilePath($this->slug, $locale),
            default => $files->getFilePath($this->slug, $locale, $this->editorType()->slug()),
        };
    }

    public function fileContentDisk(): string
    {
        return app(DixlasePagesPageContentService::class)->getDisk();
    }

    public function fileContentAuditPrefix(): string
    {
        return 'dixlase_pages.page';
    }

    /**
     * @return Builder<static>
     */
    public static function fileStoredQuery(): Builder
    {
        return static::query()->where('storage_type', ContentStorageType::FILE->value);
    }

    /**
     * The editor type as the enum the cast gives
     */
    private function editorType(): ContentEditorType
    {
        $editorType = $this->editor_type;

        return $editorType instanceof ContentEditorType ? $editorType : ContentEditorType::from((int) $editorType);
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
