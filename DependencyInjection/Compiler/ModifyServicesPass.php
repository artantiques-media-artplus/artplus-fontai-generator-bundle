<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection\Compiler;

use Fontai\Bundle\GeneratorBundle\Http\Firewall\AccessListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class ModifyServicesPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container)
  {
    $definition = $container->findDefinition('security.access_listener');
    $definition->setClass(AccessListener::class);
  }
}
