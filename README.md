# Sylius Media Hub Plugin

Sylius Media Hub is a Sylius admin plugin for auditing Product and Taxon images from one screen. It is designed for catalog teams who need quick visibility into image coverage, missing assets, and direct navigation back to the standard Sylius edit flows.

## Scope

This plugin is intended to be:

- a catalog image audit tool
- a read-only media visibility dashboard
- a productivity layer for Sylius administrators

It is not intended to be:

- a DAM
- a CMS
- an image editor
- a replacement for native Sylius image management

## Compatibility

- PHP `^8.2`
- Symfony `^6.4 || ^7.0`
- Sylius `^2.0`

## Features

- consolidated Product and Taxon image dashboard
- statistics for total, Product, Taxon, and missing images
- dedicated Product, Taxon, and Missing views
- server-side search, sorting, and pagination
- direct links to native Sylius Product and Taxon edit pages
- read-only catalog auditing with no entity overrides or schema changes

## Architecture

The plugin reads from existing Sylius catalog models and keeps core image entities untouched.

Design principles:

- no entity overrides
- no database migrations
- no frontend asset build step
- native Symfony service wiring
- native Sylius admin menu integration
- Sylius Grid used for the missing-images table

## Installation

### 1. Add the repository

If the package is not yet published on Packagist, add the VCS repository to the host project:

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

### 2. Require the plugin

```bash
composer require ajay/sylius-media-hub-plugin:dev-master
```

### 3. Register the bundle

Add the bundle to `config/bundles.php`:

```php
return [
    // ...
    Ajay\SyliusMediaHubPlugin\SyliusMediaHubPlugin::class => ['all' => true],
];
```

### 4. Add the routes in a dedicated route file

Create `config/routes/ajay_sylius_media_hub.yaml`:

```yaml
ajay_sylius_media_hub_admin_index:
    path: /admin/media-hub
    controller: Ajay\SyliusMediaHubPlugin\Controller\MediaHubController::index
    methods: [GET]

ajay_sylius_media_hub_admin_products:
    path: /admin/media-hub/products
    controller: Ajay\SyliusMediaHubPlugin\Controller\MediaHubController::products
    methods: [GET]

ajay_sylius_media_hub_admin_taxons:
    path: /admin/media-hub/taxons
    controller: Ajay\SyliusMediaHubPlugin\Controller\MediaHubController::taxons
    methods: [GET]

ajay_sylius_media_hub_admin_missing:
    path: /admin/media-hub/missing
    controller: Ajay\SyliusMediaHubPlugin\Controller\MediaHubController::missing
    methods: [GET]
```

### 5. Clear cache

```bash
php bin/console cache:clear
```

## What the plugin adds

- bundle registration in `config/bundles.php`
- route entries in `config/routes/ajay_sylius_media_hub.yaml`
- optional configuration in `config/packages/ajay_sylius_media_hub.yaml`

## Route design

The plugin exposes fixed admin routes:

- `/admin/media-hub`
- `/admin/media-hub/products`
- `/admin/media-hub/taxons`
- `/admin/media-hub/missing`

Why this design:

- the Media Hub routes stay isolated from core Sylius admin routes
- the controller stays free of route attributes
- it avoids environment-specific prefix issues during route compilation

## Configuration

The plugin works without additional configuration. Optional configuration:

```yaml
# config/packages/ajay_sylius_media_hub.yaml
ajay_sylius_media_hub:
    default_limit: 24
    pagination_limits: [24, 48, 96]
```

Configuration reference:

- `default_limit`: default page size; must be present in `pagination_limits`
- `pagination_limits`: page sizes offered in the admin UI

## Security

- all routes are admin routes
- access is protected by `ROLE_ADMINISTRATION_ACCESS`
- the menu entry is added to the Sylius admin Catalog section
- no extra ACL tables or custom permission schema are introduced

## Verification

After installation, confirm:

```bash
php bin/console debug:router ajay_sylius_media_hub_admin_index --no-debug
php bin/console debug:container Ajay\\SyliusMediaHubPlugin\\Controller\\MediaHubController --no-debug
```

Expected route names:

- `ajay_sylius_media_hub_admin_index`
- `ajay_sylius_media_hub_admin_products`
- `ajay_sylius_media_hub_admin_taxons`
- `ajay_sylius_media_hub_admin_missing`

Expected UI behavior:

- `Catalog > Media Hub` appears in the admin menu
- `/admin/media-hub` loads for authenticated administrators
- Product and Taxon edit actions open standard Sylius admin screens

## Template overrides

To customize the UI from the host app, override templates under:

```text
templates/bundles/SyliusMediaHubPlugin/
```

## Testing

Run the plugin test suite from the project root:

```bash
vendor/bin/phpunit -c vendor/ajay/sylius-media-hub-plugin/phpunit.xml.dist
```

## Troubleshooting

If the menu entry does not appear:

- confirm the bundle is registered in `config/bundles.php`
- confirm `config/routes/ajay_sylius_media_hub.yaml` exists
- clear cache
- verify you are logged in as an administrator
<img width="1741" height="855" alt="image" src="https://github.com/user-attachments/assets/cbc25e8f-b63e-4f53-82e7-7dafe17f6e8e" />

If the route is missing:

- confirm the Media Hub routes are defined in `config/routes/ajay_sylius_media_hub.yaml`
- run `php bin/console debug:router ajay_sylius_media_hub_admin_index --no-debug`

If you are redirected to login:

- make sure you are visiting `/admin/media-hub`, not `/media-hub`
- verify the current user has `ROLE_ADMINISTRATION_ACCESS`
- verify the route is registered under `/admin/...`

If the package is lost after Composer operations:

- ensure the host project keeps `ajay/sylius-media-hub-plugin` in root `composer.json`
- install the plugin through Composer from a VCS repo, path repo, or published package rather than editing `vendor/` manually

## Future extension points

- inline image replacement or upload flows
- bulk replacement workflows
- duplicate or unused image detection
- large-image and format audits
- AI-assisted media enrichment

## Maintainer

- Ajay Singh
- `ajayplanet5@gmail.com`
