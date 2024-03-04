<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class FontaiGeneratorExtension extends AbstractExtension
{
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new Configuration();
    $config = $this->processConfiguration($configuration, $configs);

    $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
    $loader->load('fontai_generator.yaml');

    $this->processControllersSection($config['controllers'], $config);

    $container->setParameter('fontai_generator', $config);
  }

  public function getAlias()
  {
    return 'fontai_generator';
  }
}