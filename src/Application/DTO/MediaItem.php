<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Application\DTO;

final readonly class MediaItem
{
    public function __construct(
        public string $kind,
        public int $ownerId,
        public string $name,
        public string $identifier,
        public string $imagePath,
        public string $filename,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        $updatedAt = null;
        if (\is_string($row['updated_at'] ?? null) && $row['updated_at'] !== '') {
            $updatedAt = new \DateTimeImmutable((string) $row['updated_at']);
        }

        $imagePath = (string) $row['image_path'];

        return new self(
            kind: (string) $row['media_kind'],
            ownerId: (int) $row['owner_id'],
            name: trim((string) $row['item_name']) !== '' ? (string) $row['item_name'] : (string) $row['item_identifier'],
            identifier: (string) $row['item_identifier'],
            imagePath: $imagePath,
            filename: basename($imagePath),
            updatedAt: $updatedAt,
        );
    }

    public function isProduct(): bool
    {
        return $this->kind === 'product';
    }

    public function getKindLabel(): string
    {
        return $this->isProduct() ? 'Product' : 'Taxon';
    }

    public function getEditRoute(): string
    {
        return $this->isProduct() ? 'sylius_admin_product_update' : 'sylius_admin_taxon_update';
    }

    /** @return array{id: int} */
    public function getEditRouteParameters(): array
    {
        return ['id' => $this->ownerId];
    }
}
