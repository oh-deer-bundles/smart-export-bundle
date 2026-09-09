<?php


namespace Odb\SmartExportBundle\DependencyInjection;


use Odb\SmartExportBundle\Services\NullAllowedIdsVoter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tbl_smart_export');
        $rootNode = $treeBuilder->getRootNode();
        /** Pur test not available  */
        $rootNode
            ->children()
                ->arrayNode('table_storage')
                    ->addDefaultsIfNotSet()
                    ->info('Storage to use for store 2 objects Engine ane Column.')
                    ->children()
                        ->scalarNode('table_engine')
                            ->info('The default table name of SmartExportEngine.')
                            ->defaultValue('tbl_smart_export_engine')
                            ->cannotBeEmpty()
                            ->end()
                        ->scalarNode('table_column')
                            ->info('The default table name of SmartExportColumn.')
                           ->defaultValue('tbl_smart_export_column')
                            ->cannotBeEmpty()
                            ->end()
                ->end()
                ->end()
                ->integerNode('max_rows')
                    ->info('Maximum number of rows an export is allowed to return before generation is blocked.')
                    ->defaultValue(20000)
                    ->min(1)
                ->end()
                ->arrayNode('security')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('allowed_ids_cache_pool')
                            ->info('Service id of a cache pool storing per-user allowed ids (see AllowedIdsResolver) — must support the get-or-compute contract (Symfony\Contracts\Cache\CacheInterface), like the default cache.app.')
                            ->defaultValue('cache.app')
                            ->cannotBeEmpty()
                            ->end()
                        ->integerNode('allowed_ids_ttl')
                            ->info('Seconds a restricted entity\'s allowed-ids list stays cached (per user) before AllowedIdsResolver calls security.ids_voter again. Bundle-owned, on-demand refresh: there is no event to hook, this TTL is the only staleness guarantee.')
                            ->defaultValue(600)
                            ->min(1)
                            ->end()
                        ->scalarNode('ids_voter')
                            ->info('Service id implementing Odb\SmartExportBundle\Services\AllowedIdsVoterInterface::getAllowedIds(string $className): array — called on demand by AllowedIdsResolver, never via an event. Defaults to a null voter granting no access to any restricted entity: REQUIRED to be overridden as soon as restricted_entities is non-empty.')
                            ->defaultValue(NullAllowedIdsVoter::class)
                            ->cannotBeEmpty()
                            ->end()
                        ->arrayNode('restricted_entities')
                            ->info('FQCN of entities restricted to a per-user allowed-ids list (see AllowedIdsResolver). Applies wherever the entity appears in an export\'s join graph, not just as the export\'s primary entity.')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                            ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }


}