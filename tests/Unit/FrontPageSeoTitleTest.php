<?php

/**
 * This file is part of the Dixlase Pages plugin.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 */

namespace Plugins\DixlasePages\Tests\Unit;

use Tests\TestCase;

/**
 * The front page hands its own title to DixlaseSEO.
 *
 * DixlaseSEO 0.2.7 gave dls_seo_set_page_meta() a third parameter and
 * SeoContext a setTitle(), so a page can override the site-wide
 * default_title. This view did not pass one, so every page's og:title and
 * twitter:title carried the site default and two different pages shared one
 * share card (#115).
 *
 * Source inspection, the same choice BladeExecutionPathRemovedTest makes:
 * what matters is the shape of the call the template makes, and rendering
 * the page would mean standing up the theme layout, the translation
 * resolver and a stand-in for the SEO helper to observe one argument.
 */
class FrontPageSeoTitleTest extends TestCase
{
    private function frontPageView(): string
    {
        return (string) file_get_contents(
            dirname(__DIR__, 2).'/resources/views/front/page.blade.php'
        );
    }

    public function test_the_page_title_is_handed_to_the_seo_helper(): void
    {
        $this->assertStringContainsString(
            "dls_seo_set_page_meta(\$page, 'dixlase-pages', \$pageTitle)",
            $this->frontPageView(),
            'The front page must pass its own title to DixlaseSEO; without it og:title falls back to the site default.'
        );
    }

    public function test_the_title_passed_is_the_translated_one(): void
    {
        $this->assertStringContainsString(
            "\$pageTitle = \$page->getTranslation('title');",
            $this->frontPageView(),
            'The title handed over must be the locale-aware one, the same value the <title> tag uses.'
        );
    }

    public function test_the_call_stays_positional(): void
    {
        // A named argument is a fatal Error on DixlaseSEO 0.1.x, which
        // declares only the first two parameters, while an extra positional
        // argument is ignored there. Blog's view carries the same note.
        $this->assertStringNotContainsString(
            'dls_seo_set_page_meta($page, \'dixlase-pages\', title:',
            $this->frontPageView(),
            'Keep the call positional so the page still renders against DixlaseSEO 0.1.x.'
        );
    }

    public function test_the_helper_call_is_still_guarded(): void
    {
        $this->assertStringContainsString(
            "function_exists('dls_seo_set_page_meta')",
            $this->frontPageView(),
            'The helper only exists when DixlaseSEO is installed.'
        );
    }
}
