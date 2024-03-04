<?php
namespace Fontai\Bundle\GeneratorBundle\Manipulator;

use Fontai\Bundle\GeneratorBundle\Generator\PropelCrudGenerator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;

class RoutingManipulator extends Manipulator
{
  private $file;

  /**
   * @param string $file The YAML routing file path
   */
  public function __construct($file)
  {
    $this->file = $file;
  }

  /**
   * Adds a routing resource at the top of the existing ones.
   *
   * @param string $format
   * @param string $prefix
   * @param string $path
   *
   * @return bool Whether the operation succeeded
   *
   * @throws \RuntimeException If routing is already imported
   */
  public function addResource($format, $prefix = '/', $path = 'routes')
  {
    $current = '';
    $code = sprintf("%s:\n", $this->getImportedResourceYamlKey($prefix));

    $res = sprintf("resource: \"%s.%s\"\n", $path, $format);

    if (file_exists($this->file))
    {
      $current = file_get_contents($this->file);

      // Don't add same routing twice
      foreach (explode(PHP_EOL, $current) as $line)
      {
        if (FALSE !== strpos($current, $res))
        {
          return TRUE;
        }
      }
    }
    elseif (!is_dir($dir = dirname($this->file)))
    {
      mkdir($dir, 0777, TRUE);
    }
    
    $code .= '    ' . $res . sprintf("    prefix:   %s\n", $prefix);
    $code .= "\n";
    $code .= $current;

    if (FALSE === file_put_contents($this->file, $code))
    {
      return FALSE;
    }

    return TRUE;
  }

  public function getImportedResourceYamlKey($prefix)
  {
    $routePrefix = PropelCrudGenerator::getRouteNamePrefix($prefix);

    return sprintf('fontai_generator_%s', $routePrefix);
  }
}