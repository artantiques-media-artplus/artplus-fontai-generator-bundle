<?php
namespace Fontai\Bundle\GeneratorBundle;

use Fontai\Bundle\GeneratorBundle\DependencyInjection\Compiler\ControllerCheckPass;
use Fontai\Bundle\GeneratorBundle\DependencyInjection\Compiler\ModifyServicesPass;
use Fontai\Bundle\GeneratorBundle\DependencyInjection\FontaiGeneratorControllersExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * FontaiGeneratorBundle.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class FontaiGeneratorBundle extends Bundle
{
  public function build(ContainerBuilder $container)
  {
    parent::build($container);

    $container->addCompilerPass(new ModifyServicesPass());
    $container->addCompilerPass(new ControllerCheckPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 255);
    
    $container->registerExtension(new FontaiGeneratorControllersExtension());
  }
}