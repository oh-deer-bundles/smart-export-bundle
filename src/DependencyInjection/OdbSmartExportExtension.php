<?php


namespace Odb\SmartExportBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Odb\SmartExportBundle\Repository\SmartExportEngineRepository;
use Odb\SmartExportBundle\Repository\SmartExportColumnRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class OdbSmartExportExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('odb_smart_export.max_rows', $config['max_rows']);
        $container->setAlias('odb.smart-export.allowed_ids_cache', $config['security']['allowed_ids_cache_pool']);
        $container->setParameter('odb_smart_export.security.restricted_entities', $config['security']['restricted_entities']);
    }

}