<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addMenuItems(MenuBuilderEvent $event): void
    {
        $catalog = $event->getMenu()->getChild('catalog');
        $parent = $catalog ?? $event->getMenu();

        $mediaHub = $parent->addChild('ajay_sylius_media_hub', [
            'route' => 'ajay_sylius_media_hub_admin_index',
            'extras' => [
                'routes' => [
                    ['route' => 'ajay_sylius_media_hub_admin_products'],
                    ['route' => 'ajay_sylius_media_hub_admin_taxons'],
                    ['route' => 'ajay_sylius_media_hub_admin_missing'],
                ],
            ],
        ]);

        $mediaHub->setLabel('ajay_sylius_media_hub.ui.media_hub');
        $mediaHub->setLabelAttribute('icon', 'tabler:photo-search');
    }
}
