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

namespace Plugins\DixlasePages\App\Services;

use App\Contracts\PluginIntegration\LinkableProviderInterface;
use App\DTO\PluginIntegration\LinkableDTO;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Plugins\DixlasePages\App\Models\DixlasePagesPageSetting;

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
        $pages = DixlasePagesPage::published()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $pages->map(fn ($page) => $this->pageToDTO($page))->toArray();
    }

    /**
     * 検索クエリに基づいてコンテンツを検索
     */
    public function searchItems(string $query, int $limit = 20): array
    {
        $pages = DixlasePagesPage::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return $pages->map(fn ($page) => $this->pageToDTO($page))->toArray();
    }

    /**
     * 特定のIDからコンテンツを取得
     */
    public function getItemById(string $id): ?LinkableDTO
    {
        $page = DixlasePagesPage::find($id);

        return $page ? $this->pageToDTO($page) : null;
    }

    /**
     * PageモデルをLinkableDTOに変換
     */
    protected function pageToDTO(DixlasePagesPage $page): LinkableDTO
    {
        return new LinkableDTO(
            id: (string) $page->id,
            title: $page->title ?? $page->slug,
            url: $this->generatePageUrl($page),
            type: 'page',
            source: 'dixlase-pages',
            sourceTable: 'plg_dixlase_pages',
        );
    }

    /**
     * Generate a relative URL for the page
     */
    protected function generatePageUrl(DixlasePagesPage $page): string
    {
        $directory = DixlasePagesPageSetting::getValue('route_slug', 'pages');

        return '/'.$directory.'/'.$page->slug;
    }
}
