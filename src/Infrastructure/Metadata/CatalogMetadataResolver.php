<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Infrastructure\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

final class CatalogMetadataResolver
{
    private ?CatalogEntityMetadata $productMetadata = null;

    private ?CatalogEntityMetadata $taxonMetadata = null;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly string $productClass,
        private readonly string $productImageClass,
        private readonly string $taxonClass,
        private readonly string $taxonImageClass,
    ) {
    }

    public function resolveProductMetadata(): CatalogEntityMetadata
    {
        return $this->productMetadata ??= $this->buildMetadata($this->productClass, $this->productImageClass, 'code');
    }

    public function resolveTaxonMetadata(): CatalogEntityMetadata
    {
        return $this->taxonMetadata ??= $this->buildMetadata($this->taxonClass, $this->taxonImageClass, 'code');
    }

    private function buildMetadata(string $ownerClass, string $imageClass, string $identifierField): CatalogEntityMetadata
    {
        $entityManager = $this->getEntityManager($ownerClass);

        $ownerMetadata = $entityManager->getClassMetadata($ownerClass);
        $translationClass = $ownerMetadata->getAssociationTargetClass('translations');
        $translationMetadata = $entityManager->getClassMetadata($translationClass);
        $imageMetadata = $entityManager->getClassMetadata($imageClass);

        return new CatalogEntityMetadata(
            entityTable: $ownerMetadata->getTableName(),
            entityIdColumn: $ownerMetadata->getSingleIdentifierColumnName(),
            identifierColumn: $ownerMetadata->getColumnName($identifierField),
            updatedAtColumn: $ownerMetadata->hasField('updatedAt') ? $ownerMetadata->getColumnName('updatedAt') : null,
            createdAtColumn: $ownerMetadata->hasField('createdAt') ? $ownerMetadata->getColumnName('createdAt') : null,
            translationTable: $translationMetadata->getTableName(),
            translationOwnerJoinColumn: $this->resolveJoinColumnName($translationMetadata, 'translatable', $ownerClass),
            translationLocaleColumn: $translationMetadata->getColumnName('locale'),
            translationNameColumn: $translationMetadata->hasField('name') ? $translationMetadata->getColumnName('name') : $ownerMetadata->getColumnName($identifierField),
            translationSlugColumn: $translationMetadata->hasField('slug') ? $translationMetadata->getColumnName('slug') : null,
            imageTable: $imageMetadata->getTableName(),
            imageIdColumn: $imageMetadata->getSingleIdentifierColumnName(),
            imageOwnerJoinColumn: $this->resolveJoinColumnName($imageMetadata, 'owner', $ownerClass),
            imagePathColumn: $imageMetadata->getColumnName('path'),
        );
    }

    private function getEntityManager(string $class): EntityManagerInterface
    {
        $entityManager = $this->managerRegistry->getManagerForClass($class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException(sprintf('No entity manager found for "%s".', $class));
        }

        return $entityManager;
    }

    private function resolveJoinColumnName(ClassMetadata $metadata, string $associationName, string $targetClass): string
    {
        if ($metadata->hasAssociation($associationName)) {
            return $metadata->getSingleAssociationJoinColumnName($associationName);
        }

        foreach ($metadata->associationMappings as $mapping) {
            if (($mapping['targetEntity'] ?? null) !== $targetClass) {
                continue;
            }

            if (!(bool) ($mapping['isOwningSide'] ?? false)) {
                continue;
            }

            $joinColumns = $mapping['joinColumns'] ?? [];
            if (isset($joinColumns[0]['name'])) {
                return (string) $joinColumns[0]['name'];
            }
        }

        throw new \RuntimeException(sprintf('Unable to resolve join column for "%s" on "%s".', $targetClass, $metadata->name));
    }
}
