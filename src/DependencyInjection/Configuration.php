<?php


namespace Odb\SmartExportBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tbl_smart_export');
        $rootNode = $treeBuilder->getRootNode();
        /** pur test not available  */
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
        ;

        return $treeBuilder;
    }


}