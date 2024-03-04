<?php
namespace Fontai\Bundle\GeneratorBundle\Generator;

use Doctrine\Common\Inflector\Inflector;
use Propel\Common\Pluralizer\StandardEnglishPluralizer;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Generates a CRUD controller.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class PropelCrudGenerator extends Generator
{
  protected $config;
  protected $filesystem;
  protected $projectDir;
  protected $routePrefix;
  protected $routeNamePrefix;
  protected $entity;
  protected $entitySingularized;
  protected $tableMapClass;
  protected $format;
  protected $actions;

  /**
   * @param Filesystem $filesystem
   * @param string     $projectDir
   */
  public function __construct(Filesystem $filesystem, $projectDir, $config)
  {
    $this->filesystem = $filesystem;
    $this->projectDir = $projectDir;
    $this->config     = $config;
  }

  /**
   * Generate the CRUD controller.
   *
   * @param string            $entity           The entity relative class name
   * @param TableMap          $tableMapClass    The entity TableMap class
   * @param string            $format           The configuration format (xml, yaml)
   * @param string            $routePrefix      The route name prefix
   * @param bool              $needWriteActions Whether or not to generate write actions
   * @param bool              $forceOverwrite   Whether or not to overwrite the controller
   *
   * @throws \RuntimeException
   */
  public function generate($entity, TableMap $tableMapClass, $format, $routePrefix, $needWriteActions, $forceOverwrite)
  {
    $this->routePrefix        = $routePrefix;
    $this->routeNamePrefix    = self::getRouteNamePrefix($routePrefix);
    $this->actions            = $needWriteActions ? ['index', 'export', 'new', 'edit', 'delete'] : ['index', 'export', 'edit'];
    $this->entity             = $entity;
    $entityParts              = explode('\\', $entity);
    $this->entityClass        = array_pop($entityParts);
    $this->entityNamespace    = implode('\\', $entityParts);
    $this->entitySingularized = lcfirst(Inflector::singularize($this->entityClass));
    $this->entityUnderscored  = Container::underscore($this->entityClass);
    $this->tableMapClass      = $tableMapClass;
    $this->setFormat($format);

    $params = [
      'config'              => $this->config,
      'controller_config'   => &$this->config['controllers'][$this->entityClass],
      'actions'             => $this->actions,
      'entity_namespace'    => $this->entityNamespace,
      'entity'              => $this->entity,
      'entity_singularized' => $this->entitySingularized,
      'entity_underscored'  => $this->entityUnderscored,
      'entity_class'        => $this->entityClass,
      'table_map'           => $this->tableMapClass,
      'table_map_i18n'      => NULL,
      'record_actions'      => $this->getRecordActions(),
      'route_prefix'        => $this->routePrefix,
      'route_name_prefix'   => $this->routeNamePrefix,
      'format'              => $this->format,
      'pluralizer'          => new StandardEnglishPluralizer()
    ];

    $behaviors = $this->tableMapClass->getBehaviors();
    if (isset($behaviors['i18n']))
    {
      $params['table_map_i18n'] = call_user_func(['\\' . $this->entityNamespace . '\\Map\\' . str_replace('%PHPNAME%', $this->tableMapClass->getPhpName(), $behaviors['i18n']['i18n_phpname']) . 'TableMap', 'getTableMap']);
    }

    $this->tableMapClass->getRelations();

    $this->generateControllerBaseClass($params);
    $this->generateControllerClass($params);

    $this->generateIndexBaseView($params);
    $this->generateIndexView($params);

    if (in_array('edit', $this->actions))
    {
      $this->generateEditBaseView($params);
      $this->generateEditView($params);
    }

    $this->generateTestClass($params);
    $this->generateConfiguration($params);
  }

  /**
   * Sets the configuration format.
   *
   * @param string $format The configuration format
   */
  protected function setFormat($format)
  {
    switch ($format)
    {
      case 'yaml':
      case 'xml':
      case 'php':
        $this->format = $format;
        break;
      default:
        $this->format = 'yaml';
        break;
    }
  }

  /**
   * Generates the routing configuration.
   */
  protected function generateConfiguration($params)
  {
    if (!in_array($this->format, ['yaml', 'xml', 'php']))
    {
      return;
    }

    $target = sprintf(
      '%s/config/routes/fontai_generator/%s.%s',
      $this->projectDir,
      $this->entityUnderscored,
      $this->format
    );

    $this->renderFile(
      'crud/config/routes.' . $this->format . '.twig',
      $target,
      $params
    );
  }

  /**
   * Generates the controller base class only.
   */
  protected function generateControllerBaseClass($params)
  {
    $target = sprintf(
      '%s/src/Controller/FontaiGenerator/Base/%sController.php',
      $this->projectDir,
      $this->entityClass
    );

    $this->renderFile(
      'crud/controllerBase.php.twig',
      $target,
      $params
    );
  }

  /**
   * Generates the controller class only.
   */
  protected function generateControllerClass($params)
  {
    $target = sprintf(
      '%s/src/Controller/FontaiGenerator/%sController.php',
      $this->projectDir,
      $this->entityClass
    );

    if (file_exists($target))
    {
      return;
    }

    $this->renderFile(
      'crud/controller.php.twig',
      $target,
      $params
    );
  }

  /**
   * Generates the functional test class only.
   */
  protected function generateTestClass($params)
  {
    $target = sprintf(
      '%s/src/Tests/Controller/FontaiGenerator/%sControllerTest.php',
      $this->projectDir,
      $this->entityClass
    );

    $this->renderFile(
      'crud/tests/test.php.twig',
      $target,
      $params
    );
  }

  /**
   * Generates the index.html.twig base template in the final bundle.
   */
  protected function generateIndexBaseView($params)
  {
    $dir = sprintf(
      '%s/templates/fontai_generator/base/%s',
      $this->projectDir,
      $this->entityUnderscored
    );

    $this->filesystem->mkdir($dir . '/index', 0777);

    foreach ([
      'index',
      'index/header',
      'index/footer',
      'index/messages',
      'index/filters',
      'index/actions',
      'index/batch_actions',
      'index/list',
      'index/pager',
      'index/thead',
      'index/td_batch_actions',
      'index/td',
      'index/td_actions',
      'index/quickedit_row',
      'quickedit',
      'quickedit/form',
      'filters_standalone'
    ] as $file)
    {
      $this->renderFile(
        'crud/views/base/' . $file . '.html.twig.twig',
        $dir . '/' . $file . '.html.twig',
        $params
      );
    }
  }

  /**
   * Generates the index.html.twig template in the final bundle.
   */
  protected function generateIndexView($params)
  {
    $dir = sprintf(
      '%s/templates/fontai_generator/%s',
      $this->projectDir,
      $this->entityUnderscored
    );

    $this->filesystem->mkdir($dir . '/index', 0777);

    foreach ([
      'index',
      'index/header',
      'index/footer',
      'index/messages',
      'index/filters',
      'index/actions',
      'index/batch_actions',
      'index/list',
      'index/pager',
      'index/thead',
      'index/td_batch_actions',
      'index/td',
      'index/td_actions',
      'index/quickedit_row',
      'quickedit',
      'quickedit/form',
      'filters_standalone'
    ] as $file)
    {
      $target = $dir . '/' . $file . '.html.twig';

      if (file_exists($target))
      {
        continue;
      }

      $this->renderFile(
        'crud/views/' . $file . '.html.twig.twig',
        $target,
        $params
      );
    }
  }

  /**
   * Generates the edit.html.twig base template in the final bundle.
   */
  protected function generateEditBaseView($params)
  {
    $dir = sprintf(
      '%s/templates/fontai_generator/base/%s',
      $this->projectDir,
      $this->entityUnderscored
    );

    $this->filesystem->mkdir($dir . '/edit', 0777);

    foreach ([
      'edit',
      'edit_standalone',
      'edit/header',
      'edit/footer',
      'edit/messages',
      'edit/form',
      'edit/actions'
    ] as $file)
    {
      $this->renderFile(
        'crud/views/base/' . $file . '.html.twig.twig',
        $dir . '/' . $file . '.html.twig',
        $params
      );
    }
  }

  /**
   * Generates the edit.html.twig template in the final bundle.
   */
  protected function generateEditView($params)
  {
    $dir = sprintf(
      '%s/templates/fontai_generator/%s',
      $this->projectDir,
      $this->entityUnderscored
    );

    $this->filesystem->mkdir($dir . '/edit', 0777);

    foreach ([
      'edit',
      'edit_standalone',
      'edit/header',
      'edit/footer',
      'edit/messages',
      'edit/form',
      'edit/actions'
    ] as $file)
    {
      $target = $dir . '/' . $file . '.html.twig';

      if (file_exists($target))
      {
        continue;
      }

      $this->renderFile(
        'crud/views/' . $file . '.html.twig.twig',
        $target,
        $params
      );
    }
  }

  /**
   * Returns an array of record actions to generate (edit).
   *
   * @return array
   */
  protected function getRecordActions()
  {
    return array_filter($this->actions, function($item)
    {
      return in_array($item, ['edit']);
    });
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
