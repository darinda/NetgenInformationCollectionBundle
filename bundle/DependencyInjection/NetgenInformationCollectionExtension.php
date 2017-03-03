<?php

namespace Netgen\Bundle\InformationCollectionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NetgenInformationCollectionExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('parameters.yml');

        $processor = new ConfigurationProcessor($container, ConfigurationConstants::SETTINGS_ROOT);
        $configArrays = [
            ConfigurationConstants::ACTIONS,
            ConfigurationConstants::ACTION_CONFIG,
        ];

        $scopes = array_merge(['default'], $container->getParameter('ezpublish.siteaccess.list'));

        foreach ($configArrays as $configArray) {
            $processor->mapConfigArray($configArray, $config);
            foreach ($scopes as $scope) {
                $scopeConfig = $container->getParameter(ConfigurationConstants::SETTINGS_ROOT . '.' . $scope . '.' . $configArray);
                foreach ((array)$scopeConfig as $key => $value) {
                    $container->setParameter(
                        ConfigurationConstants::SETTINGS_ROOT . '.' . $scope . '.' . $configArray . '.' . $key,
                        $value
                    );
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function prepend(ContainerBuilder $container)
    {
        $container->prependExtensionConfig(
            'assetic',
            [
                'bundles' => [
                    'NetgenInformationCollectionBundle'
                ],
            ]
        );

        $this->prependYui($container);
        $this->prependCss($container);
    }

    private function prependYui(ContainerBuilder $container)
    {
        $container->setParameter(
            ConfigurationConstants::SETTINGS_ROOT . '.public_dir',
            'bundles/netgeninformationcollectionbundle'
        );

        $yuiConfigFile = __DIR__ . '/../Resources/config/yui.yml';
        $config = Yaml::parse(file_get_contents($yuiConfigFile));
        $container->prependExtensionConfig('ez_platformui', $config);
        $container->addResource(
            new FileResource($yuiConfigFile)
        );
    }

    private function prependCss(ContainerBuilder $container)
    {
        $container->setParameter(
            ConfigurationConstants::SETTINGS_ROOT . '.css_dir',
            'bundles/netgeninformationcollectionbundle/css'
        );

        $cssConfigFile = __DIR__ . '/../Resources/config/css.yml';
        $config = Yaml::parse(file_get_contents($cssConfigFile));
        $container->prependExtensionConfig('ez_platformui', $config);
        $container->addResource(
            new FileResource($cssConfigFile)
        );
    }
}
