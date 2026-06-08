<?php

declare(strict_types=1);

namespace Ajay\SyliusMediaHubPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ajay_sylius_media_hub');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->integerNode('default_limit')
                    ->min(1)
                    ->defaultValue(24)
                ->end()
                ->arrayNode('pagination_limits')
                    ->integerPrototype()->min(1)->end()
                    ->defaultValue([24, 48, 96])
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static fn (array $config): bool => !\in_array($config['default_limit'], $config['pagination_limits'], true))
                ->thenInvalid('The "default_limit" must be one of the configured "pagination_limits".')
            ->end()
        ;

        return $treeBuilder;
    }
}
