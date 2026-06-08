<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Tests\Functional;

use Ajay\SyliusMediaHubPlugin\Application\DTO\MediaHubStatistics;
use Ajay\SyliusMediaHubPlugin\Application\DTO\MediaItem;
use Ajay\SyliusMediaHubPlugin\Application\DTO\MissingItem;
use Ajay\SyliusMediaHubPlugin\Application\Query\MediaHubCriteria;
use Ajay\SyliusMediaHubPlugin\Infrastructure\Repository\MediaHubReadRepositoryInterface;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class MediaHubControllerTest extends WebTestCase
{
    public function test_it_redirects_unauthenticated_users_to_admin_login(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/media-hub');

        self::assertResponseRedirects('/admin/login');
    }

    public function test_it_renders_the_media_hub_for_admin_users(): void
    {
        $client = static::createClient();
        static::getContainer()->set(MediaHubReadRepositoryInterface::class, new StubMediaHubReadRepository());

        $client->loginUser(new InMemoryUser('media-hub-admin', null, ['ROLE_ADMINISTRATION_ACCESS']), 'admin');
        $client->request('GET', '/admin/media-hub');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1.page-title', 'Media Hub');
        self::assertSelectorTextContains('body', 'Alpine Bike');
        self::assertSelectorTextContains('body', 'hero-bike.jpg');
    }

    public function test_it_renders_the_missing_view_for_admin_users(): void
    {
        $client = static::createClient();
        static::getContainer()->set(MediaHubReadRepositoryInterface::class, new StubMediaHubReadRepository());

        $client->loginUser(new InMemoryUser('media-hub-admin', null, ['ROLE_ADMINISTRATION_ACCESS']), 'admin');
        $client->request('GET', '/admin/media-hub/missing?missingScope=taxons');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'No Image Taxon');
        self::assertSelectorTextContains('body', 'mountain-bikes');
    }
}

final class StubMediaHubReadRepository implements MediaHubReadRepositoryInterface
{
    public function getStatistics(): MediaHubStatistics
    {
        return new MediaHubStatistics(3, 2, 1, 2, 1, 1);
    }

    public function paginateMedia(MediaHubCriteria $criteria, string $localeCode): Pagerfanta
    {
        $items = [
            new MediaItem(
                kind: 'product',
                ownerId: 10,
                name: 'Alpine Bike',
                identifier: 'ALP-BIKE',
                imagePath: 'hero-bike.jpg',
                filename: 'hero-bike.jpg',
                updatedAt: new \DateTimeImmutable('2026-06-01 10:00:00'),
            ),
        ];

        return $this->createPager($items);
    }

    public function paginateMissing(MediaHubCriteria $criteria, string $localeCode): Pagerfanta
    {
        $items = [
            new MissingItem(
                kind: 'taxon',
                ownerId: 20,
                name: 'No Image Taxon',
                identifier: 'mountain-bikes',
                updatedAt: new \DateTimeImmutable('2026-06-02 09:30:00'),
            ),
        ];

        return $this->createPager($items);
    }

    /** @param list<object> $items */
    private function createPager(array $items): Pagerfanta
    {
        $pager = new Pagerfanta(new FixedAdapter(\count($items), $items));
        $pager->setMaxPerPage(24);
        $pager->setCurrentPage(1);

        return $pager;
    }
}
