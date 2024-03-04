<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;


class FontaiGeneratorControllersExtension extends AbstractExtension
{
  public function load(array $configs, ContainerBuilder $container)
  {
    $configuration = new ControllersConfiguration();
    $config = $this->processConfiguration($configuration, $configs);

    $mainConfig = $container->getParameter('fontai_generator');

    $this->processControllersSection($config, $mainConfig);

    foreach (array_keys($config) as $name)
    {
      if (!isset($mainConfig['controllers'][$name]))
      {
        $mainConfig['controllers'][$name] = $config[$name];
      }
    }

    $container->setParameter('fontai_generator', $mainConfig);
  }

  public function getConfiguration(array $config, ContainerBuilder $container)
  {
    return new ControllersConfiguration();
  }

  public function getAlias()
  {
    return 'fontai_generator_controllers';
  }
}