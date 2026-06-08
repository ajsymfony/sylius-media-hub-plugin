<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusMediaHubPluginExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ajay_sylius_media_hub.default_limit', $config['default_limit']);
        $container->setParameter('ajay_sylius_media_hub.pagination_limits', $config['pagination_limits']);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');
        $loader->load('grids.yaml');
    }
}
