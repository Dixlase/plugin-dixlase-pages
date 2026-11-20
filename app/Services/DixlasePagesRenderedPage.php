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

namespace Plugins\DixlasePages\App\Services;

use Plugins\DixlasePages\App\Models\DixlasePagesPage;

/**
 * Everything the front needs in order to show one page, prepared.
 *
 * The page route builds one of these through DixlasePagesPageRenderer and
 * hands it to the front view. It carries no HTTP concern of its own beyond
 * $storable, so a caller that is not a route — a front-page body provider,
 * for instance — can use the same values without a response to put them in.
 */
final class DixlasePagesRenderedPage
{
    /**
     * @param  string  $editorType  The editor the content should be read as.
     *                              A GUI page arrives here as 'html',
     *                              because its JSON has already been
     *                              rendered.
     * @param  bool  $storable  False when the page is not published. Such a
     *                          page is served only to a signed-in member,
     *                          so a shared cache must never keep it and
     *                          hand it to a visitor.
     */
    public function __construct(
        public readonly DixlasePagesPage $page,
        public readonly string $editorType,
        public readonly string $content,
        public readonly bool $hasCustomCss,
        public readonly bool $hasCustomJs,
        public readonly int $customAssetVersion,
        public readonly ?string $adminEditUrl,
        public readonly bool $storable,
    ) {}

    /**
     * The variables 'dixlase-pages::front.page' expects.
     *
     * Kept here so the view's contract is named in one place rather than
     * spelled out again by every caller that renders that view.
     *
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'page' => $this->page,
            'editorType' => $this->editorType,
            'content' => $this->content,
            'hasCustomCss' => $this->hasCustomCss,
            'hasCustomJs' => $this->hasCustomJs,
            'customAssetVersion' => $this->customAssetVersion,
            'adminEditUrl' => $this->adminEditUrl,
        ];
    }
}
