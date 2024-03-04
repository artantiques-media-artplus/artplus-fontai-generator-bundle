<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ControllersConfiguration implements ConfigurationInterface
{
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder('fontai_generator_controllers');

    $treeBuilder
    ->getRootNode()
      ->useAttributeAsKey('name')
      ->prototype('array')
        ->children()
          ->booleanNode('fulltextSearch')->end()
          ->booleanNode('log')->end()
          ->arrayNode('included_in')
            ->prototype('scalar')->end()
          ->end()
          ->variableNode('fields')->end()
          ->variableNode('list')->end()
          ->variableNode('export')->end()
          ->variableNode('edit')->end()
        ->end()
      ->end()
    ->end();

    return $treeBuilder;
  }
}