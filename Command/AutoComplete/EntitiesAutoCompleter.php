<?php
namespace Fontai\Bundle\GeneratorBundle\Command\AutoComplete;

use Propel\Runtime\Map\DatabaseMap;

/**
 * Provides auto-completion suggestions for entities.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class EntitiesAutoCompleter
{
  private $databaseMap;

  public function __construct(DatabaseMap $databaseMap)
  {
    $this->databaseMap = $databaseMap;
  }

  public function getSuggestions()
  {
    //$configuration = $this->manager->getConfiguration();
    $namespaceReplacements = array();

    /*foreach ($configuration->getEntityNamespaces() as $alias => $namespace)
    {
      $namespaceReplacements[$namespace.'\\'] = $alias.':';
    }*/

    return array_map(function($entity) use ($namespaceReplacements)
    {
      return strtr($entity, $namespaceReplacements);
    }, $this->databaseMap->getTables());
  }
}