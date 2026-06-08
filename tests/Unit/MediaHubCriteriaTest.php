<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Tests\Unit;

use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use PHPUnit\Framework\TestCase;

final class MediaHubCriteriaTest extends TestCase
{
    public function test_it_normalizes_invalid_values(): void
    {
        $criteria = MediaHubCriteria::fromArray(
            [
                'search' => '  alpine  ',
                'sort' => 'invalid',
                'page' => '-10',
                'limit' => '999',
                'missingScope' => 'unknown',
            ],
            MediaHubCriteria::SCOPE_MISSING,
            24,
            [24, 48, 96],
        );

        self::assertSame(MediaHubCriteria::SCOPE_MISSING, $criteria->scope);
        self::assertSame('alpine', $criteria->search);
        self::assertSame(MediaHubCriteria::SORT_NEWEST, $criteria->sort);
        self::assertSame(1, $criteria->page);
        self::assertSame(24, $criteria->limit);
        self::assertSame(MediaHubCriteria::MISSING_SCOPE_ALL, $criteria->missingScope);
    }

    public function test_it_keeps_valid_values(): void
    {
        $criteria = MediaHubCriteria::fromArray(
            [
                'sort' => MediaHubCriteria::SORT_NAME_DESC,
                'page' => '4',
                'limit' => '48',
                'missingScope' => MediaHubCriteria::MISSING_SCOPE_TAXONS,
            ],
            MediaHubCriteria::SCOPE_MISSING,
            24,
            [24, 48, 96],
        );

        self::assertSame(MediaHubCriteria::SORT_NAME_DESC, $criteria->sort);
        self::assertSame(4, $criteria->page);
        self::assertSame(48, $criteria->limit);
        self::assertSame(MediaHubCriteria::MISSING_SCOPE_TAXONS, $criteria->missingScope);
    }
}
