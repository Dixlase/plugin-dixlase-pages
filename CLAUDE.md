# Dixlase Pages - Dixlase CMS Plugin

> **Type**: Dixlase Plugin | **Category**: content | **Version**: 0.1.0
> **Namespace**: `Plugins\DixlasePages`

## Plugin Overview

This plugin provides static page management functionality.

## Architecture

Plugin for **Dixlase CMS** (Laravel 12). The core application is located at `../../` relative to this plugin directory.

### Key Paths
- **Core Root**: `../../` (includes `vendor/`, `artisan`, core `app/`)
- **This Plugin**: `plugins/DixlasePages/`
- **Artisan Commands**: `docker exec -i <your-php-container> php artisan <command>`

### Plugin Features
- admin_menu
- front_routes
- settings_page

## Development Rules

### Core Integration
- **Do not import core internals directly** — use `App\Contracts\*` interfaces
- **Register views, translations and config in the ServiceProvider** — but NOT routes or migrations. Routes are auto-loaded by PluginServiceProvider. Migrations are applied by PluginMigrator and recorded in the `dls_plugin_migrations` ledger, so calling `loadMigrationsFrom()` makes a bare `php artisan migrate` try to re-create tables the installer already created (SQLSTATE 42S01)
- **Namespace isolation** — all classes under `Plugins\DixlasePages\*` 
- **Self-contained migrations** — manage plugin-specific tables

### PHP Standards
- PHP 8.3, Laravel 12, Livewire 4
- Use constructor property promotion
- Declare explicit return types for all methods
- Validate with Form Request classes (no inline validation)
- Prefer PHPDoc blocks over inline comments
- Enum keys should be TitleCase
- Use `config()` instead of `env()` directly

### Translation
- Always provide both `en/` and `ja/` translation files

### Route Naming
- Pattern: `plugin.{slug}.{resource}.{action}`
- Middleware groups: `plugin`, `plugin.web`, `plugin.admin`

### Testing
- Write feature tests with PHPUnit (not Pest)
- Use model factories (check existing states before manual setup)
- Run tests: `docker exec -i <your-php-container> php artisan test plugins/DixlasePages/tests/`
- Specific test: `docker exec -i <your-php-container> php artisan test --filter=testMethodName`

### Code Formatting
- Pint auto-runs via hook after edits


## MCP Tools (Laravel Boost)
- `search-docs`: Search Laravel ecosystem documentation
- `tinker`: Debug PHP code
- `database-query`: Read-only database queries
- `list-artisan-commands`: Check available commands before running Artisan