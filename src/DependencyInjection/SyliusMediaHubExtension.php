<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class SyliusMediaHubExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ajay_sylius_media_hub.default_limit', $config['default_limit']);
        $container->setParameter('ajay_sylius_media_hub.pagination_limits', $config['pagination_limits']);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('sylius_grid')) {
            return;
        }

        $gridConfiguration = Yaml::parseFile(\dirname(__DIR__, 2) . '/config/grids.yaml');
        if (!\is_array($gridConfiguration) || !isset($gridConfiguration['sylius_grid']) || !\is_array($gridConfiguration['sylius_grid'])) {
            return;
        }

        $container->prependExtensionConfig('sylius_grid', $gridConfiguration['sylius_grid']);
    }
}
