<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class ControllerCheckPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container)
  {
    foreach (array_keys($container->findTaggedServiceIds('controller.service_arguments')) as $id)
    {
      if (!preg_match('~\FontaiGenerator\\\(?!Base\\\)~', $id))
      {
        continue;
      }

      try
      {
        $container->getReflectionClass($id);
      }
      catch (\Exception $e)
      {
        $container->removeDefinition($id);
      }
    }
  }
}
