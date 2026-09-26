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

namespace Plugins\DixlasePages\Tests\Unit;

use App\Enums\MemberRole;
use App\Services\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Core's EnsurePluginAdminAccess derives a permission key from the route name
 * and falls back to ADMIN when the plugin declares nothing. Only `index` and
 * `create` were declared, so an editor could open the page list and the create
 * form and then take a 403 on save: the screens were reachable and useless.
 *
 * Both directions matter here, so both are asserted.
 *
 *   - Too strict and authoring breaks with no error in the logs, only a 403
 *     the editor sees.
 *   - Too loose and an editor empties the trash. Permanent deletion and
 *     revision protection stay with ADMIN for that reason.
 *
 * A wrong value is invisible in review -- the config still parses, the screens
 * still render, and only the role boundary moves.
 */
class RolesConfigResolvesTest extends TestCase
{
    // getPluginEffective() consults role_permission_overrides, so the schema
    // has to exist even though the values under test come from config.
    use RefreshDatabase;

    private const DIRECTORY = 'DixlasePages';

    /**
     * Every admin route this plugin registers, with the role its endpoint is
     * meant to require. Keys are what EnsurePluginAdminAccess resolves from
     * the route name `dixlase-pages::admin.pages.*`.
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function routePermissions(): array
    {
        $editor = MemberRole::EDITOR->value;
        $admin = MemberRole::ADMIN->value;
        $superAdmin = MemberRole::SUPER_ADMIN->value;

        return [
            // Authoring.
            'list' => ['pages.index', $editor],
            'create form' => ['pages.create', $editor],
            'create' => ['pages.store', $editor],
            'show' => ['pages.show', $editor],
            'edit form' => ['pages.edit', $editor],
            'update' => ['pages.update', $editor],

            // Preview no longer executes Blade (see the Blade execution path
            // removal), so it is ordinary authoring.
            'preview' => ['pages.preview', $editor],
            'preview render' => ['pages.preview-render', $editor],
            'preview frame' => ['pages.preview-frame', $editor],
            'preview frame (new)' => ['pages.preview-frame-new', $editor],

            // Soft delete: recoverable from the trash.
            'soft delete' => ['pages.destroy', $editor],
            'trash screen' => ['pages.trash', $editor],
            'restore from trash' => ['pages.trash.restore', $editor],

            // Permanent deletion. Nothing brings these back.
            'empty trash' => ['pages.trash.empty', $admin],
            'delete permanently' => ['pages.trash.force-destroy', $admin],

            // Revision history is authoring; protection decides what may be
            // pruned later, so it sits with the deletion group.
            'revision list' => ['pages.revisions.index', $editor],
            'revision view' => ['pages.revisions.show', $editor],
            'revision restore' => ['pages.revisions.restore', $admin],
            'revision note' => ['pages.revisions.note', $admin],
            'revision protect' => ['pages.revisions.protect', $admin],

            // publish_min_role and route_slug live here, so a delegated admin
            // must not be able to widen publish rights or change the site's
            // page URL scheme. `settings.update` inherits from `settings`.
            'settings screen' => ['pages.settings', $superAdmin],
            'settings save' => ['pages.settings.update', $superAdmin],
        ];
    }

    #[DataProvider('routePermissions')]
    public function test_each_route_resolves_to_the_role_it_is_meant_to_require(string $key, int $expected): void
    {
        $effective = PermissionRegistry::getPluginEffective(self::DIRECTORY, $key);

        $this->assertSame(
            $expected,
            $effective['access_roles'],
            "{$key} must require ".MemberRole::from($expected)->name.'. A wrong value here either blocks authoring or hands out deletion.'
        );
    }

    /**
     * The permanent-delete actions sit under `trash`, which an editor may
     * open. PermissionRegistry used to return a node the moment it carried
     * access_roles, so these two inherited EDITOR from their parent while the
     * config read as if it restricted them. Called out separately because it
     * is the one pair where the config and the resolved value disagreed.
     */
    public function test_permanent_deletion_is_not_inherited_from_the_trash_screen(): void
    {
        $trash = PermissionRegistry::getPluginEffective(self::DIRECTORY, 'pages.trash');

        $this->assertSame(
            MemberRole::EDITOR->value,
            $trash['access_roles'],
            'An editor is meant to reach the trash screen.'
        );

        foreach (['pages.trash.empty', 'pages.trash.force-destroy'] as $key) {
            $this->assertSame(
                MemberRole::ADMIN->value,
                PermissionRegistry::getPluginEffective(self::DIRECTORY, $key)['access_roles'],
                "{$key} destroys data with no way back and must not inherit the trash screen's role."
            );
        }
    }

    /**
     * Every key above except `pages.settings.update`, which has no entry of
     * its own and inherits from `pages.settings`.
     *
     * Yields the key only: PHPUnit 12 refuses to run a data set that passes
     * more arguments than the test method accepts.
     *
     * @return array<string, array{0: string}>
     */
    public static function keysWithTheirOwnEntry(): array
    {
        return array_map(
            static fn (array $row): array => [$row[0]],
            array_filter(
                self::routePermissions(),
                static fn (array $row): bool => $row[0] !== 'pages.settings.update'
            )
        );
    }

    #[DataProvider('keysWithTheirOwnEntry')]
    public function test_every_declared_key_is_actually_found(string $key): void
    {
        $this->assertTrue(
            PermissionRegistry::hasPluginDefinition(self::DIRECTORY, $key),
            "roles.php declares {$key}, but PermissionRegistry cannot find it -- the declaration is being ignored and the route falls back to ADMIN."
        );
    }

    /**
     * The shape is the point. A flat dotted key looks identical in a diff and
     * behaves completely differently: PermissionRegistry walks one level at a
     * time, so 'trash.empty' at this level never matches and the route
     * silently takes whatever the parent says.
     */
    public function test_no_key_is_written_as_a_flat_dotted_string(): void
    {
        $config = require dirname(__DIR__, 2).'/config/admin/roles.php';

        $walk = function (array $node, string $path) use (&$walk) {
            foreach ($node as $key => $value) {
                if ($key === 'children' && is_array($value)) {
                    $walk($value, $path);

                    continue;
                }

                if (is_string($key) && ! in_array($key, ['access_roles', 'view_roles'], true)) {
                    $this->assertStringNotContainsString(
                        '.',
                        $key,
                        "Key '{$key}' under '{$path}' contains a dot. PermissionRegistry walks levels, so a dotted key never matches -- nest it with 'children' instead."
                    );
                }

                if (is_array($value) && isset($value['children'])) {
                    $walk($value['children'], $path.'/'.$key);
                }
            }
        };

        $walk($config['permissions'] ?? [], 'permissions');
    }
}
