<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Infrastructure\Repository;

use Ajay\SyliusMediaHubPlugin\Application\DTO\MediaHubStatistics;
use Ajay\SyliusMediaHubPlugin\Application\DTO\MediaItem;
use Ajay\SyliusMediaHubPlugin\Application\DTO\MissingItem;
use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use Ajay\SyliusMediaHubPlugin\Infrastructure\Metadata\CatalogEntityMetadata;
use Ajay\SyliusMediaHubPlugin\Infrastructure\Metadata\CatalogMetadataResolver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;

final readonly class DoctrineMediaHubReadRepository implements MediaHubReadRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private CatalogMetadataResolver $metadataResolver,
        private string $fallbackLocale,
    ) {
    }

    public function getStatistics(): MediaHubStatistics
    {
        $productMetadata = $this->metadataResolver->resolveProductMetadata();
        $taxonMetadata = $this->metadataResolver->resolveTaxonMetadata();

        $productImages = $this->countImages($productMetadata);
        $taxonImages = $this->countImages($taxonMetadata);
        $missingProducts = $this->countMissing($productMetadata);
        $missingTaxons = $this->countMissing($taxonMetadata);

        return new MediaHubStatistics(
            totalImages: $productImages + $taxonImages,
            productImages: $productImages,
            taxonImages: $taxonImages,
            missingImages: $missingProducts + $missingTaxons,
            missingProducts: $missingProducts,
            missingTaxons: $missingTaxons,
        );
    }

    public function paginateMedia(MediaHubCriteria $criteria, string $localeCode): Pagerfanta
    {
        [$baseSql, $params, $types] = $this->buildMediaBaseSql($criteria, $localeCode);
        $total = $this->countWrappedQuery($baseSql, $params, $types);

        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM (%s) media_items ORDER BY %s LIMIT :limit OFFSET :offset',
                $baseSql,
                $this->resolveSortOrder($criteria),
            ),
            $params + [
                'limit' => $criteria->limit,
                'offset' => ($criteria->page - 1) * $criteria->limit,
            ],
            $types + [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
        );

        $items = array_map(static fn (array $row): MediaItem => MediaItem::fromRow($row), $rows);

        return $this->createPager($items, $total, $criteria->page, $criteria->limit);
    }

    public function paginateMissing(MediaHubCriteria $criteria, string $localeCode): Pagerfanta
    {
        [$baseSql, $params, $types] = $this->buildMissingBaseSql($criteria, $localeCode);
        $total = $this->countWrappedQuery($baseSql, $params, $types);

        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM (%s) missing_items ORDER BY %s LIMIT :limit OFFSET :offset',
                $baseSql,
                $this->resolveSortOrder($criteria),
            ),
            $params + [
                'limit' => $criteria->limit,
                'offset' => ($criteria->page - 1) * $criteria->limit,
            ],
            $types + [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ],
        );

        $items = array_map(static fn (array $row): MissingItem => MissingItem::fromRow($row), $rows);

        return $this->createPager($items, $total, $criteria->page, $criteria->limit);
    }

    private function countImages(CatalogEntityMetadata $metadata): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s image WHERE %s',
            $this->qi($metadata->imageTable),
            $this->nonEmptyPathClause('image', $metadata),
        );

        return (int) $this->connection->fetchOne($sql);
    }

    private function countMissing(CatalogEntityMetadata $metadata): int
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s owner WHERE NOT EXISTS (%s)',
            $this->qi($metadata->entityTable),
            $this->existingImageSubquery('owner', $metadata),
        );

        return (int) $this->connection->fetchOne($sql);
    }

    /**
     * @return array{0:string,1:array<string, mixed>,2:array<string, int>}
     */
    private function buildMediaBaseSql(MediaHubCriteria $criteria, string $localeCode): array
    {
        $params = [
            'locale' => $localeCode,
            'fallbackLocale' => $this->resolveFallbackLocale($localeCode),
        ];
        $types = [
            'locale' => ParameterType::STRING,
            'fallbackLocale' => ParameterType::STRING,
        ];

        if ($criteria->scope === MediaHubCriteria::SCOPE_PRODUCTS) {
            return [$this->buildProductMediaSelect($criteria, $params, $types), $params, $types];
        }

        if ($criteria->scope === MediaHubCriteria::SCOPE_TAXONS) {
            return [$this->buildTaxonMediaSelect($criteria, $params, $types), $params, $types];
        }

        return [
            sprintf(
                '%s UNION ALL %s',
                $this->buildProductMediaSelect($criteria, $params, $types),
                $this->buildTaxonMediaSelect($criteria, $params, $types),
            ),
            $params,
            $types,
        ];
    }

    /**
     * @return array{0:string,1:array<string, mixed>,2:array<string, int>}
     */
    private function buildMissingBaseSql(MediaHubCriteria $criteria, string $localeCode): array
    {
        $params = [
            'locale' => $localeCode,
            'fallbackLocale' => $this->resolveFallbackLocale($localeCode),
        ];
        $types = [
            'locale' => ParameterType::STRING,
            'fallbackLocale' => ParameterType::STRING,
        ];

        if ($criteria->missingScope === MediaHubCriteria::MISSING_SCOPE_PRODUCTS) {
            return [$this->buildProductMissingSelect($criteria, $params, $types), $params, $types];
        }

        if ($criteria->missingScope === MediaHubCriteria::MISSING_SCOPE_TAXONS) {
            return [$this->buildTaxonMissingSelect($criteria, $params, $types), $params, $types];
        }

        return [
            sprintf(
                '%s UNION ALL %s',
                $this->buildProductMissingSelect($criteria, $params, $types),
                $this->buildTaxonMissingSelect($criteria, $params, $types),
            ),
            $params,
            $types,
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildProductMediaSelect(MediaHubCriteria $criteria, array &$params, array &$types): string
    {
        return $this->buildMediaSelect(
            kind: 'product',
            criteria: $criteria,
            metadata: $this->metadataResolver->resolveProductMetadata(),
            ownerAlias: 'product',
            imageAlias: 'image',
            translationAlias: 'product_translation',
            fallbackTranslationAlias: 'product_translation_fallback',
            params: $params,
            types: $types,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildTaxonMediaSelect(MediaHubCriteria $criteria, array &$params, array &$types): string
    {
        return $this->buildMediaSelect(
            kind: 'taxon',
            criteria: $criteria,
            metadata: $this->metadataResolver->resolveTaxonMetadata(),
            ownerAlias: 'taxon',
            imageAlias: 'image',
            translationAlias: 'taxon_translation',
            fallbackTranslationAlias: 'taxon_translation_fallback',
            params: $params,
            types: $types,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildProductMissingSelect(MediaHubCriteria $criteria, array &$params, array &$types): string
    {
        return $this->buildMissingSelect(
            kind: 'product',
            criteria: $criteria,
            metadata: $this->metadataResolver->resolveProductMetadata(),
            ownerAlias: 'product',
            translationAlias: 'product_translation',
            fallbackTranslationAlias: 'product_translation_fallback',
            params: $params,
            types: $types,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildTaxonMissingSelect(MediaHubCriteria $criteria, array &$params, array &$types): string
    {
        return $this->buildMissingSelect(
            kind: 'taxon',
            criteria: $criteria,
            metadata: $this->metadataResolver->resolveTaxonMetadata(),
            ownerAlias: 'taxon',
            translationAlias: 'taxon_translation',
            fallbackTranslationAlias: 'taxon_translation_fallback',
            params: $params,
            types: $types,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildMediaSelect(
        string $kind,
        MediaHubCriteria $criteria,
        CatalogEntityMetadata $metadata,
        string $ownerAlias,
        string $imageAlias,
        string $translationAlias,
        string $fallbackTranslationAlias,
        array &$params,
        array &$types,
    ): string {
        $displayNameExpression = $this->displayNameExpression($metadata, $ownerAlias, $translationAlias, $fallbackTranslationAlias);
        $identifierExpression = $this->identifierExpression($metadata, $ownerAlias, $translationAlias, $fallbackTranslationAlias);
        $timestampExpression = $this->timestampExpression($metadata, $ownerAlias);
        $searchClause = $this->buildSearchClause($criteria, $displayNameExpression, $identifierExpression, $params, $types, $kind);

        return sprintf(
            "SELECT '%s' AS media_kind, %s.%s AS owner_id, %s AS item_name, %s AS item_identifier, %s.%s AS image_path, %s AS updated_at
            FROM %s %s
            INNER JOIN %s %s ON %s.%s = %s.%s
            LEFT JOIN %s %s ON %s.%s = %s.%s AND %s.%s = :locale
            LEFT JOIN %s %s ON %s.%s = %s.%s AND %s.%s = :fallbackLocale
            WHERE %s%s",
            $kind,
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $displayNameExpression,
            $identifierExpression,
            $imageAlias,
            $this->qi($metadata->imagePathColumn),
            $timestampExpression,
            $this->qi($metadata->imageTable),
            $imageAlias,
            $this->qi($metadata->entityTable),
            $ownerAlias,
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $imageAlias,
            $this->qi($metadata->imageOwnerJoinColumn),
            $this->qi($metadata->translationTable),
            $translationAlias,
            $translationAlias,
            $this->qi($metadata->translationOwnerJoinColumn),
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $translationAlias,
            $this->qi($metadata->translationLocaleColumn),
            $this->qi($metadata->translationTable),
            $fallbackTranslationAlias,
            $fallbackTranslationAlias,
            $this->qi($metadata->translationOwnerJoinColumn),
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $fallbackTranslationAlias,
            $this->qi($metadata->translationLocaleColumn),
            $this->nonEmptyPathClause($imageAlias, $metadata),
            $searchClause,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildMissingSelect(
        string $kind,
        MediaHubCriteria $criteria,
        CatalogEntityMetadata $metadata,
        string $ownerAlias,
        string $translationAlias,
        string $fallbackTranslationAlias,
        array &$params,
        array &$types,
    ): string {
        $displayNameExpression = $this->displayNameExpression($metadata, $ownerAlias, $translationAlias, $fallbackTranslationAlias);
        $identifierExpression = $this->identifierExpression($metadata, $ownerAlias, $translationAlias, $fallbackTranslationAlias);
        $timestampExpression = $this->timestampExpression($metadata, $ownerAlias);
        $searchClause = $this->buildSearchClause($criteria, $displayNameExpression, $identifierExpression, $params, $types, $kind);

        return sprintf(
            "SELECT '%s' AS media_kind, %s.%s AS owner_id, %s AS item_name, %s AS item_identifier, %s AS updated_at
            FROM %s %s
            LEFT JOIN %s %s ON %s.%s = %s.%s AND %s.%s = :locale
            LEFT JOIN %s %s ON %s.%s = %s.%s AND %s.%s = :fallbackLocale
            WHERE NOT EXISTS (%s)%s",
            $kind,
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $displayNameExpression,
            $identifierExpression,
            $timestampExpression,
            $this->qi($metadata->entityTable),
            $ownerAlias,
            $this->qi($metadata->translationTable),
            $translationAlias,
            $translationAlias,
            $this->qi($metadata->translationOwnerJoinColumn),
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $translationAlias,
            $this->qi($metadata->translationLocaleColumn),
            $this->qi($metadata->translationTable),
            $fallbackTranslationAlias,
            $fallbackTranslationAlias,
            $this->qi($metadata->translationOwnerJoinColumn),
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $fallbackTranslationAlias,
            $this->qi($metadata->translationLocaleColumn),
            $this->existingImageSubquery($ownerAlias, $metadata),
            $searchClause,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, int> $types
     */
    private function buildSearchClause(
        MediaHubCriteria $criteria,
        string $displayNameExpression,
        string $identifierExpression,
        array &$params,
        array &$types,
        string $parameterPrefix,
    ): string {
        if ($criteria->search === '') {
            return '';
        }

        $parameter = $parameterPrefix . '_search';
        $params[$parameter] = '%' . mb_strtolower($criteria->search) . '%';
        $types[$parameter] = ParameterType::STRING;

        return sprintf(
            ' AND (LOWER(%s) LIKE :%s OR LOWER(%s) LIKE :%s)',
            $displayNameExpression,
            $parameter,
            $identifierExpression,
            $parameter,
        );
    }

    private function resolveSortOrder(MediaHubCriteria $criteria): string
    {
        return match ($criteria->sort) {
            MediaHubCriteria::SORT_OLDEST => 'CASE WHEN updated_at IS NULL THEN 0 ELSE 1 END ASC, updated_at ASC, item_name ASC, media_kind ASC, owner_id ASC',
            MediaHubCriteria::SORT_NAME_ASC => 'item_name ASC, CASE WHEN updated_at IS NULL THEN 1 ELSE 0 END ASC, updated_at DESC, media_kind ASC, owner_id ASC',
            MediaHubCriteria::SORT_NAME_DESC => 'item_name DESC, CASE WHEN updated_at IS NULL THEN 1 ELSE 0 END ASC, updated_at DESC, media_kind ASC, owner_id ASC',
            default => 'CASE WHEN updated_at IS NULL THEN 1 ELSE 0 END ASC, updated_at DESC, item_name ASC, media_kind ASC, owner_id ASC',
        };
    }

    private function countWrappedQuery(string $baseSql, array $params, array $types): int
    {
        return (int) $this->connection->fetchOne(
            sprintf('SELECT COUNT(*) FROM (%s) counted_rows', $baseSql),
            $params,
            $types,
        );
    }

    /** @param list<object> $items */
    private function createPager(array $items, int $total, int $page, int $limit): Pagerfanta
    {
        $pager = new Pagerfanta(new FixedAdapter($total, $items));
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);

        return $pager;
    }

    private function displayNameExpression(
        CatalogEntityMetadata $metadata,
        string $ownerAlias,
        string $translationAlias,
        string $fallbackTranslationAlias,
    ): string {
        return sprintf(
            'COALESCE(NULLIF(%s.%s, \'\'), NULLIF(%s.%s, \'\'), %s.%s)',
            $translationAlias,
            $this->qi($metadata->translationNameColumn),
            $fallbackTranslationAlias,
            $this->qi($metadata->translationNameColumn),
            $ownerAlias,
            $this->qi($metadata->identifierColumn),
        );
    }

    private function identifierExpression(
        CatalogEntityMetadata $metadata,
        string $ownerAlias,
        string $translationAlias,
        string $fallbackTranslationAlias,
    ): string {
        if (null === $metadata->translationSlugColumn) {
            return sprintf('%s.%s', $ownerAlias, $this->qi($metadata->identifierColumn));
        }

        return sprintf(
            'COALESCE(NULLIF(%s.%s, \'\'), NULLIF(%s.%s, \'\'), %s.%s)',
            $translationAlias,
            $this->qi($metadata->translationSlugColumn),
            $fallbackTranslationAlias,
            $this->qi($metadata->translationSlugColumn),
            $ownerAlias,
            $this->qi($metadata->identifierColumn),
        );
    }

    private function timestampExpression(CatalogEntityMetadata $metadata, string $ownerAlias): string
    {
        if (null !== $metadata->updatedAtColumn && null !== $metadata->createdAtColumn) {
            return sprintf(
                'COALESCE(%s.%s, %s.%s)',
                $ownerAlias,
                $this->qi($metadata->updatedAtColumn),
                $ownerAlias,
                $this->qi($metadata->createdAtColumn),
            );
        }

        if (null !== $metadata->updatedAtColumn) {
            return sprintf('%s.%s', $ownerAlias, $this->qi($metadata->updatedAtColumn));
        }

        if (null !== $metadata->createdAtColumn) {
            return sprintf('%s.%s', $ownerAlias, $this->qi($metadata->createdAtColumn));
        }

        return 'NULL';
    }

    private function existingImageSubquery(string $ownerAlias, CatalogEntityMetadata $metadata): string
    {
        return sprintf(
            'SELECT 1 FROM %s existing_image WHERE existing_image.%s = %s.%s AND %s',
            $this->qi($metadata->imageTable),
            $this->qi($metadata->imageOwnerJoinColumn),
            $ownerAlias,
            $this->qi($metadata->entityIdColumn),
            $this->nonEmptyPathClause('existing_image', $metadata),
        );
    }

    private function nonEmptyPathClause(string $imageAlias, CatalogEntityMetadata $metadata): string
    {
        return sprintf(
            '%s.%s IS NOT NULL AND %s.%s <> \'\'',
            $imageAlias,
            $this->qi($metadata->imagePathColumn),
            $imageAlias,
            $this->qi($metadata->imagePathColumn),
        );
    }

    private function resolveFallbackLocale(string $localeCode): string
    {
        return $this->fallbackLocale !== '' ? $this->fallbackLocale : $localeCode;
    }

    private function qi(string $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier);
    }
}
