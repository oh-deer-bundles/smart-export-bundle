<?php


namespace Odb\SmartExportBundle\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');


        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

    }

}