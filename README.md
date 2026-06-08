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

This is the exact setup used in the host project.

### 1. Add The GitHub Repository To The Main Project

If the plugin is not published on Packagist yet, add it as a VCS repository in the main project `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/ajsymfony/sylius-media-hub-plugin.git"
    }
  ]
}
```

### 2. Require The Plugin In The Main Project

Add the package to `require`:

```json
{
  "require": {
    "ajay/sylius-media-hub-plugin": "dev-master"
  }
}
```

### 3. Install it with Composer:

```bash
composer require ajay/sylius-media-hub-plugin:dev-master
```

### 4. Register The Bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Ajay\SyliusMediaHubPlugin\SyliusMediaHubPlugin::class => ['all' => true],
];
```

### 5. Import The Admin Routes

Create `config/routes/ajay_sylius_media_hub.yaml`:

```yaml
ajay_sylius_media_hub:
    resource: "@SyliusMediaHubPlugin/config/routes/admin.yaml"
    prefix: '/%sylius_admin.path_name%'
```

### 6. Clear Cache

```bash
php bin/console cache:clear
```

### Notes

- Package name stays lowercase: `ajay/sylius-media-hub-plugin`
- PHP namespace stays capitalized: `Ajay\SyliusMediaHubPlugin`
- The plugin adds no migrations, entity overrides, or asset build requirements
- If you update the plugin source, run `composer update ajay/sylius-media-hub-plugin`

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
## Maintainer

- Ajay Singh
- Email: `ajayplanet5@gmail.com`

Feedback, suggestions, bug reports, and collaboration inquiries are welcome at `ajayplanet5@gmail.com`.
