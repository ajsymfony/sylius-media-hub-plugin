<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Infrastructure\Metadata;

final readonly class CatalogEntityMetadata
{
    public function __construct(
        public string $entityTable,
        public string $entityIdColumn,
        public string $identifierColumn,
        public ?string $updatedAtColumn,
        public ?string $createdAtColumn,
        public string $translationTable,
        public string $translationOwnerJoinColumn,
        public string $translationLocaleColumn,
        public string $translationNameColumn,
        public ?string $translationSlugColumn,
        public string $imageTable,
        public string $imageIdColumn,
        public string $imageOwnerJoinColumn,
        public string $imagePathColumn,
    ) {
    }
}
