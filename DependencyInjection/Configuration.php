<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder('fontai_generator');

    $treeBuilder
    ->getRootNode()
      ->children()
        ->scalarNode('prefix')->end()
        ->booleanNode('fulltextSearch')->defaultFalse()->end()
        ->booleanNode('log')->defaultFalse()->end()
        ->booleanNode('languages')->defaultFalse()->end()
        ->scalarNode('documentRoot')->end()
        ->arrayNode('controllers')
          ->prototype('array')
            ->children()
              ->booleanNode('fulltextSearch')->defaultFalse()->end()
              ->booleanNode('log')->defaultFalse()->end()
              ->arrayNode('included_in')
                ->prototype('scalar')->end()
              ->end()
              ->variableNode('fields')->end()
              ->variableNode('list')->end()
              ->variableNode('export')->end()
              ->variableNode('edit')->end()
            ->end()
          ->end()
        ->end()
        ->arrayNode('list')
          ->children()
            ->scalarNode('click_action')->end()
            ->arrayNode('per_page')
              ->children()
                ->variableNode('choices')->end()
                ->integerNode('default')->defaultValue(20)->end()
              ->end()
            ->end()
            ->arrayNode('actions')
              ->prototype('variable')
              ->end()
              ->defaultValue([
                'create' => NULL
              ])
            ->end()
            ->arrayNode('object_actions')
              ->prototype('variable')
              ->end()
              ->defaultValue([
                'edit'   => NULL,
                'delete' => NULL
              ])
            ->end()
            ->arrayNode('batch_actions')
              ->prototype('variable')
              ->end()
              ->defaultValue([
                'delete' => NULL
              ])
            ->end()
          ->end()
        ->end()
        ->arrayNode('edit')
          ->children()
            ->booleanNode('nav')->defaultTrue()->end()
            ->scalarNode('field_size')->defaultValue('col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2')->end()
          ->end()
        ->end()
      ->end()
    ->end();

    return $treeBuilder;
  }
}