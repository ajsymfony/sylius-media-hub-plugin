<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Application\DTO;

final readonly class MediaHubStatistics
{
    public function __construct(
        public int $totalImages,
        public int $productImages,
        public int $taxonImages,
        public int $missingImages,
        public int $missingProducts,
        public int $missingTaxons,
    ) {
    }
}
