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

namespace Plugins\DixlasePages\App\Services;

use App\Contracts\PluginIntegration\LinkableProviderInterface;
use App\DTO\PluginIntegration\LinkableDTO;
use App\Helpers\LocaleHelper;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;

class DixlasePagesPageLinkableProvider implements LinkableProviderInterface
{
    /**
     * プロバイダーの識別子を取得
     */
    public function getProviderKey(): string
    {
        return 'dixlase-pages';
    }

    /**
     * プロバイダーの表示名を取得
     */
    public function getProviderLabel(): string
    {
        return __('dixlase-pages::admin.provider.label');
    }

    /**
     * プロバイダーのアイコンクラスを取得
     */
    public function getProviderIcon(): ?string
    {
        return 'fas fa-file-alt';
    }

    /**
     * このプロバイダーが現在利用可能かどうか
     */
    public function isAvailable(): bool
    {
        return class_exists(DixlasePagesPage::class);
    }

    /**
     * 利用可能なコンテンツのリストを取得
     */
    public function getAvailableItems(int $limit = 100): array
    {
        $locale = LocaleHelper::getCurrentLocale();
        
        $pages = DixlasePagesPage::published()
            ->with(['translations' => function ($query) use ($locale) {
                $query->where('locale', $locale);
            }])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $pages->map(fn($page) => $this->pageToDTO($page, $locale))->toArray();
    }

    /**
     * 検索クエリに基づいてコンテンツを検索
     */
    public function searchItems(string $query, int $limit = 20): array
    {
        $locale = LocaleHelper::getCurrentLocale();
        
        $pages = DixlasePagesPage::published()
            ->whereHas('translations', function ($q) use ($query, $locale) {
                $q->where('locale', $locale)
                  ->where(function ($sq) use ($query) {
                      $sq->where('title', 'like', "%{$query}%")
                         ->orWhere('content', 'like', "%{$query}%");
                  });
            })
            ->with(['translations' => function ($q) use ($locale) {
                $q->where('locale', $locale);
            }])
            ->limit($limit)
            ->get();

        return $pages->map(fn($page) => $this->pageToDTO($page, $locale))->toArray();
    }

    /**
     * 特定のIDからコンテンツを取得
     */
    public function getItemById(string $id): ?LinkableDTO
    {
        $locale = LocaleHelper::getCurrentLocale();
        
        $page = DixlasePagesPage::with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])->find($id);

        return $page ? $this->pageToDTO($page, $locale) : null;
    }

    /**
     * PageモデルをLinkableDTOに変換
     */
    protected function pageToDTO(DixlasePagesPage $page, string $locale): LinkableDTO
    {
        $translation = $page->translations->first();
        $title = $translation?->title ?? $page->slug;
        
        return new LinkableDTO(
            id: (string) $page->id,
            title: $title,
            url: route('dixlase-pages.show', ['slug' => $page->slug]),
            type: 'page',
            source: 'dixlase-pages',
            sourceTable: 'plg_dixlase_pages',
            locale: $locale,
        );
    }
}
