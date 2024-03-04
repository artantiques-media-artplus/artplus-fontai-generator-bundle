<?php
namespace Fontai\Bundle\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Container;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Runtime\Map\TableMap;

/**
 * Generates a form class based on a Propel entity.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class PropelFormGenerator extends Generator
{
  private $config;
  private $filesystem;
  private $projectDir;
  private $routePrefix;
  private $routeNamePrefix;

  /**
   * Constructor.
   *
   * @param Filesystem $filesystem A Filesystem instance
   */
  public function __construct(Filesystem $filesystem, $projectDir, $config)
  {
    $this->filesystem = $filesystem;
    $this->projectDir = $projectDir;
    $this->config     = $config;
  }

  /**
   * Generates the entity form class.
   *
   * @param string            $entity         The entity relative class name
   * @param TableMap          $tableMapClass       The entity TableMap class
   * @param string            $routePrefix      The route name prefix
   * @param bool              $forceOverwrite If true, remove any existing form class before generating it again
   */
  public function generate($entity, TableMap $tableMapClass, $routePrefix, $forceOverwrite = false)
  {
    $this->routePrefix     = $routePrefix;
    $this->routeNamePrefix = self::getRouteNamePrefix($routePrefix);
    $entityParts           = explode('\\', $entity);
    $this->entityClass     = array_pop($entityParts);
    $entityNamespace       = implode('\\', $entityParts);

    $params = [
      'config'             => $this->config,
      'controller_config'  => &$this->config['controllers'][$this->entityClass],
      'entity_namespace'   => $entityNamespace,
      'entity'             => $entity,
      'entity_class'       => $this->entityClass,
      'entity_underscored' => Container::underscore($this->entityClass),
      'table_map'          => $tableMapClass,
      'table_map_i18n'     => NULL,
      'form_class'         => $this->entityClass . 'Type',
      'route_prefix'       => $this->routePrefix,
      'route_name_prefix'  => $this->routeNamePrefix,
      'pluralizer'         => new StandardEnglishPluralizer()
    ];

    $behaviors = $tableMapClass->getBehaviors();
    if (isset($behaviors['i18n']))
    {
      $params['table_map_i18n'] = call_user_func(['\\' . $entityNamespace . '\\Map\\' . str_replace('%PHPNAME%', $tableMapClass->getPhpName(), $behaviors['i18n']['i18n_phpname']) . 'TableMap', 'getTableMap']);
    }

    $tableMapClass->getRelations();

    $this->generateFormBaseClass('Filter', $params, $forceOverwrite);
    $this->generateFormClass('Filter', $params);

    $this->generateFormBaseClass('Edit', $params, $forceOverwrite);
    $this->generateFormClass('Edit', $params);

    $this->generateFormBaseClass('Quickedit', $params, $forceOverwrite);
    $this->generateFormClass('Quickedit', $params);
  }

  /**
   * Generates the form base class only.
   */
  protected function generateFormBaseClass($type, $params, $forceOverwrite)
  {
    $target = sprintf(
      '%s/src/Form/FontaiGenerator/%s/Base/%sType.php',
      $this->projectDir,
      $type,
      $this->entityClass
    );

    if (!$forceOverwrite && file_exists($target))
    {
      throw new \RuntimeException('Unable to generate the %s form class as it already exists under the %s file.', $this->entityClass, $target);
    }

    $this->renderFile(
      sprintf('form/%sFormTypeBase.php.twig', $type),
      $target,
      $params
    );
  }

  /**
   * Generates the form class only.
   */
  protected function generateFormClass($type, $params)
  {
    $target = sprintf(
      '%s/src/Form/FontaiGenerator/%s/%sType.php',
      $this->projectDir,
      $type,
      $this->entityClass
    );

    if (file_exists($target))
    {
      return;
    }

    $this->renderFile(
      sprintf('form/%sFormType.php.twig', $type),
      $target,
      $params
    );
  }

  public static function getRouteNamePrefix($prefix)
  {
    $prefix = preg_replace('/{(.*?)}/', '', $prefix); // {foo}_bar -> _bar
    $prefix = str_replace('/', '_', $prefix);
    $prefix = preg_replace('/_+/', '_', $prefix);     // foo__bar -> foo_bar
    $prefix = trim($prefix, '_');

    return $prefix;
  }
}