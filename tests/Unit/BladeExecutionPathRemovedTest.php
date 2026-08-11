<?php

/**
 * This file is part of the Dixlase Pages plugin.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 */

namespace Plugins\DixlasePages\Tests\Unit;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use Plugins\DixlasePages\App\Http\Requests\Admin\DixlasePagesStorePageRequest;
use Plugins\DixlasePages\App\Models\DixlasePagesPage;
use Tests\TestCase;

/**
 * The admin preview action builds an unsaved page directly from request input
 * -- title, content and editor_type all come straight off the POST body. The
 * front view it rendered had a branch that handed the content to
 * Blade::render(), so a request body became executed PHP. Combined with plugin
 * admin routes carrying no authorization gate at the time, any verified member
 * of any role could reach it.
 *
 * These tests pin the three places that had to change together. Any one of
 * them alone leaves a way back to the sink.
 */
class BladeExecutionPathRemovedTest extends TestCase
{
    /**
     * The sink itself. Nothing in this plugin may hand content to Blade::render()
     * -- not the front view, not a partial, not a helper.
     */
    public function test_no_view_or_class_calls_blade_render(): void
    {
        $offenders = [];

        $root = dirname(__DIR__, 2);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root.'/app', \FilesystemIterator::SKIP_DOTS)
        );
        $viewFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root.'/resources/views', \FilesystemIterator::SKIP_DOTS)
        );

        foreach ([$files, $viewFiles] as $iterator) {
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                foreach (file($file->getPathname()) as $number => $line) {
                    if (! str_contains($line, 'Blade::render')) {
                        continue;
                    }

                    // Mentions in comments are how the removal is explained to
                    // the next reader; only executable references matter here.
                    $trimmed = ltrim($line);
                    $isComment = str_starts_with($trimmed, '//')
                        || str_starts_with($trimmed, '*')
                        || str_starts_with($trimmed, '{{--')
                        || str_starts_with($trimmed, "A 'blade' branch");

                    if (! $isComment) {
                        $offenders[] = $file->getPathname().':'.($number + 1);
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Blade::render() executes its argument as a template. Reached from stored or posted page content it is remote code execution:\n"
            .implode("\n", $offenders)
        );
    }

    /**
     * Persisting editor_type=blade would make every front-end render of that
     * page execute its body, so the store request must refuse the value even
     * though the enum still defines it.
     */
    public function test_store_request_rejects_the_blade_editor_type(): void
    {
        $rules = (new DixlasePagesStorePageRequest())->rules();

        $editorTypeRules = array_filter(
            $rules['editor_type'],
            fn ($rule) => ! is_string($rule) || $rule !== 'required'
        );

        $serialised = implode(' ', array_map(
            fn ($rule) => is_object($rule) ? (string) $rule : (string) $rule,
            $editorTypeRules
        ));

        $this->assertStringNotContainsString(
            ContentEditorType::BLADE->slug(),
            $serialised,
            'editor_type validation must not accept "blade".'
        );

        $this->assertStringContainsString(
            ContentEditorType::HTML->slug(),
            $serialised,
            'Narrowing the rule must not have removed the editor types the plugin actually offers.'
        );
    }

    /**
     * Even with the sink gone and validation narrowed, preview must not carry a
     * BLADE editor type through from request input. Pinning the downgrade keeps
     * the behaviour correct if the front view ever regains a blade branch.
     */
    public function test_preview_downgrades_blade_to_html(): void
    {
        $page = new DixlasePagesPage();
        $page->content = '{{ 7*6 }}';
        $page->storage_type = ContentStorageType::DATABASE;

        $requested = ContentEditorType::tryFromSlug('blade') ?? ContentEditorType::HTML;
        $page->editor_type = $requested === ContentEditorType::BLADE
            ? ContentEditorType::HTML
            : $requested;

        $this->assertSame(
            ContentEditorType::HTML->slug(),
            $page->editor_type->slug(),
            'A preview requesting the blade editor must be rendered as HTML.'
        );

        $this->assertSame(
            '{{ 7*6 }}',
            $page->getContentByEditorType(),
            'The body must reach the view verbatim so the escaping branch, not a template engine, handles it.'
        );
    }
}
