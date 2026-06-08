<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Application\Query;

use Symfony\Component\HttpFoundation\Request;

final readonly class MediaHubCriteria
{
    public const SCOPE_ALL = 'all';
    public const SCOPE_PRODUCTS = 'products';
    public const SCOPE_TAXONS = 'taxons';
    public const SCOPE_MISSING = 'missing';

    public const SORT_NEWEST = 'newest';
    public const SORT_OLDEST = 'oldest';
    public const SORT_NAME_ASC = 'name_asc';
    public const SORT_NAME_DESC = 'name_desc';

    public const MISSING_SCOPE_ALL = 'all';
    public const MISSING_SCOPE_PRODUCTS = 'products';
    public const MISSING_SCOPE_TAXONS = 'taxons';

    /** @param list<int> $allowedLimits */
    public static function fromRequest(
        Request $request,
        string $scope,
        int $defaultLimit,
        array $allowedLimits,
    ): self {
        return self::fromArray($request->query->all(), $scope, $defaultLimit, $allowedLimits);
    }

    /**
     * @param array<string, mixed> $parameters
     * @param list<int> $allowedLimits
     */
    public static function fromArray(
        array $parameters,
        string $scope,
        int $defaultLimit,
        array $allowedLimits,
    ): self {
        $search = trim((string) ($parameters['search'] ?? ''));
        $sort = (string) ($parameters['sort'] ?? self::SORT_NEWEST);
        $sort = \in_array($sort, self::allowedSorts(), true) ? $sort : self::SORT_NEWEST;

        $limit = (int) ($parameters['limit'] ?? $defaultLimit);
        if (!\in_array($limit, $allowedLimits, true)) {
            $limit = $defaultLimit;
        }

        $page = max(1, (int) ($parameters['page'] ?? 1));
        $missingScope = (string) ($parameters['missingScope'] ?? self::MISSING_SCOPE_ALL);
        $missingScope = \in_array($missingScope, self::allowedMissingScopes(), true) ? $missingScope : self::MISSING_SCOPE_ALL;

        return new self($scope, $search, $sort, $page, $limit, $missingScope);
    }

    public function __construct(
        public string $scope,
        public string $search,
        public string $sort,
        public int $page,
        public int $limit,
        public string $missingScope = self::MISSING_SCOPE_ALL,
    ) {
    }

    /** @return list<string> */
    public static function allowedSorts(): array
    {
        return [
            self::SORT_NEWEST,
            self::SORT_OLDEST,
            self::SORT_NAME_ASC,
            self::SORT_NAME_DESC,
        ];
    }

    /** @return list<string> */
    public static function allowedMissingScopes(): array
    {
        return [
            self::MISSING_SCOPE_ALL,
            self::MISSING_SCOPE_PRODUCTS,
            self::MISSING_SCOPE_TAXONS,
        ];
    }

    public function isMissingView(): bool
    {
        return $this->scope === self::SCOPE_MISSING;
    }
}
