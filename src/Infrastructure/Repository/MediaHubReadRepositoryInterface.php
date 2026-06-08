<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Infrastructure\Repository;

use Ajay\SyliusMediaHubPlugin\Application\DTO\MediaHubStatistics;
use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use Pagerfanta\Pagerfanta;

interface MediaHubReadRepositoryInterface
{
    public function getStatistics(): MediaHubStatistics;

    /** @return Pagerfanta<object> */
    public function paginateMedia(MediaHubCriteria $criteria, string $localeCode): Pagerfanta;

    /** @return Pagerfanta<object> */
    public function paginateMissing(MediaHubCriteria $criteria, string $localeCode): Pagerfanta;
}
