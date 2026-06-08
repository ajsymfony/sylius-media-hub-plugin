<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Tests\Unit;

use Ajay\SyliusMediaHubPlugin\Menu\AdminMenuListener;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListenerTest extends TestCase
{
    public function test_it_adds_media_hub_under_catalog_menu(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('catalog');

        $listener = new AdminMenuListener();
        $listener->addMenuItems(new MenuBuilderEvent($factory, $menu));

        $catalog = $menu->getChild('catalog');
        self::assertNotNull($catalog);

        $mediaHub = $catalog->getChild('ajay_sylius_media_hub');
        self::assertNotNull($mediaHub);
        self::assertSame('ajay_sylius_media_hub.ui.media_hub', $mediaHub->getLabel());
    }
}
