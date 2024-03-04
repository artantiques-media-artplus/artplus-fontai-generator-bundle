<?php
namespace Fontai\Bundle\GeneratorBundle\Command;


abstract class GeneratePropelCommand extends GeneratorCommand
{
  public function isEnabled()
  {
    return class_exists('Propel\\Bundle\\PropelBundle\\PropelBundle');
  }
}
