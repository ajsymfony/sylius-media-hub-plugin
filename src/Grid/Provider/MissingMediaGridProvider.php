<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Grid\Provider;

use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use Ajay\SyliusMediaHubPlugin\Infrastructure\Repository\MediaHubReadRepositoryInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Parameters;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class MissingMediaGridProvider implements DataProviderInterface
{
    /**
     * @param list<int> $allowedLimits
     */
    public function __construct(
        private MediaHubReadRepositoryInterface $mediaHubReadRepository,
        private RequestStack $requestStack,
        private int $defaultLimit,
        private array $allowedLimits,
        private string $fallbackLocale,
    ) {
    }

    public function getData(Grid $grid, Parameters $parameters): mixed
    {
        $criteria = MediaHubCriteria::fromArray(
            $parameters->all(),
            MediaHubCriteria::SCOPE_MISSING,
            $this->defaultLimit,
            $this->allowedLimits,
        );

        $localeCode = $this->requestStack->getCurrentRequest()?->getLocale() ?? $this->fallbackLocale;

        return $this->mediaHubReadRepository->paginateMissing($criteria, $localeCode);
    }
}
