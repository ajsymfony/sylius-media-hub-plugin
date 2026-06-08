# Sylius Media Hub Plugin

Sylius Media Hub is a lightweight Sylius admin plugin that centralizes Product and Taxon image visibility in one place.

Tagline: Manage all Product and Taxon images from a single screen.

## Purpose

This plugin is designed as:

- A media visibility tool
- A catalog image audit tool
- A productivity tool for Sylius administrators

This plugin is not a DAM, CMS, image editor, or replacement for Sylius image management.

## Compatibility

- Sylius `^2.0`
- Symfony `^6.4 || ^7.0`
- PHP `^8.2`

## Features

- Centralized dashboard for Product and Taxon images
- Statistics cards for total, product, taxon, and missing images
- Missing-image badges with direct drill-down links
- Server-side search across product name/code and taxon name/slug
- Server-side sorting and pagination
- Dedicated Product, Taxon, and Missing views
- Direct links back to the standard Sylius Product and Taxon edit pages
- Read-only catalog auditing with no entity overrides

## Architecture

The plugin keeps Sylius core image entities untouched and reads from the existing Product, ProductImage, Taxon, and TaxonImage models.

Key design choices:

- Read-only DBAL projections for fast list and audit queries
- No entity decoration or schema changes
- Native Symfony services and Dependency Injection
- Native Sylius admin layout and menu integration
- GridBundle used for the missing-images table

## Installation

### Required Host Project Wiring

For the plugin to work in a Sylius application, the host project needs:

1. The Composer package installed
2. The bundle registered
3. The admin routes imported
4. Cache rebuilt

This plugin does not require:

- Doctrine migrations
- Entity overrides
- Asset compilation
- JavaScript SPA setup

### Option A: Install From Packagist Or A VCS Repository

1. Require the package:

```bash
composer require ajay/sylius-media-hub-plugin
```

2. Register the bundle if Flex does not do it for you:

```php
// config/bundles.php
return [
    // ...
    Ajay\SyliusMediaHubPlugin\SyliusMediaHubPlugin::class => ['all' => true],
];
```

3. Import the admin routes:

```yaml
# config/routes/ajay_sylius_media_hub.yaml
ajay_sylius_media_hub:
    resource: "@SyliusMediaHubPlugin/config/routes/admin.yaml"
    prefix: '/%sylius_admin.path_name%'
```

4. Clear cache and rebuild autoload files:

```bash
composer dump-autoload
php bin/console cache:clear
```

### Option B: Install As A Local Path Repository During Development

If the plugin source lives outside `vendor/`, for example in `plugins/sylius-media-hub-plugin/` or in a sibling repository, wire it through Composer as a path repository.

Example root `composer.json` changes:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "plugins/sylius-media-hub-plugin",
      "options": {
        "symlink": false
      }
    }
  ],
  "require": {
    "ajay/sylius-media-hub-plugin": "*@dev"
  }
}
```

Then run:

```bash
composer update ajay/sylius-media-hub-plugin
php bin/console cache:clear
```

`symlink: false` is the safer option in mixed Windows/WSL environments.

### Important Note About `vendor/`

Do not treat `vendor/ajay/sylius-media-hub-plugin` as the source of truth for development.

`vendor/` is Composer-managed install output:

- it can be replaced on install or update
- changes there are not a reliable distribution workflow
- it should receive the plugin from Composer, not be hand-maintained as the primary source

The correct source of truth is:

- a separate Git repository for the plugin, or
- a local path repository outside `vendor/`

### Current Repository State

At runtime, the plugin appears to be autoloaded and the bundle can be registered, but the host project is only properly reproducible if the root `composer.json` also declares the package source and dependency. If that is missing, the next clean Composer install can remove the plugin.

## Plugin Configuration

The plugin works without any required custom configuration.

Optional configuration:

```yaml
# config/packages/ajay_sylius_media_hub.yaml
ajay_sylius_media_hub:
    default_limit: 24
    pagination_limits: [24, 48, 96]
```

### Configuration Reference

- `default_limit`
  Default results per page
  Must be one of the configured pagination limits

- `pagination_limits`
  Allowed page sizes shown in the admin UI

## Security Integration

- Routes are meant to be mounted under the existing Sylius admin prefix
- Access is guarded by `ROLE_ADMINISTRATION_ACCESS`
- The menu entry is added to the Sylius admin Catalog section
- No extra ACL schema or permissions table is introduced

## What Gets Added To The Host Project

- Bundle registration in `config/bundles.php`
- Admin route import in `config/routes/ajay_sylius_media_hub.yaml`
- Optional configuration in `config/packages/ajay_sylius_media_hub.yaml`

## Verification Checklist

After installation, verify with:

```bash
php bin/console debug:router | grep media-hub
php bin/console debug:container | grep SyliusMediaHubPlugin
php bin/console cache:clear
```

Expected routes:

- `ajay_sylius_media_hub_admin_index`
- `ajay_sylius_media_hub_admin_products`
- `ajay_sylius_media_hub_admin_taxons`
- `ajay_sylius_media_hub_admin_missing`

Expected UI behavior:

- `Catalog > Media Hub` appears in the admin menu
- `/admin/media-hub` loads for administrators
- Product and Taxon edit buttons point to standard Sylius admin edit screens

## Routes

- `/admin/media-hub`
- `/admin/media-hub/products`
- `/admin/media-hub/taxons`
- `/admin/media-hub/missing`

## Security

The routes are protected for Sylius administrators and live under the existing admin firewall. The controller also enforces `ROLE_ADMINISTRATION_ACCESS`.

## Template Overrides

If a project wants to customize the plugin UI, override its templates in the host app just like any other Symfony bundle templates:

```text
templates/bundles/SyliusMediaHubPlugin/...
```

## Testing

Run the plugin test suite from the project root:

```bash
vendor/bin/phpunit -c vendor/ajay/sylius-media-hub-plugin/phpunit.xml.dist
```

## Troubleshooting

If the menu entry does not appear:

- confirm the bundle is in `config/bundles.php`
- confirm the admin route import exists
- clear cache
- ensure you are logged in as an administrator

If routes are missing:

- confirm `config/routes/ajay_sylius_media_hub.yaml` exists
- run `php bin/console debug:router | grep media-hub`

If the package disappears after Composer operations:

- the host project is missing a proper root `composer.json` dependency declaration
- move the plugin source out of `vendor/` and install it through Composer from a path repo, VCS repo, or published package

## Future Extension Points

The current implementation intentionally leaves room for future modules such as:

- Direct image replacement/upload from the gallery
- Bulk upload and replacement flows
- Duplicate or unused image detection
- Large-image and format audits
- AI-assisted SEO/media enrichment
