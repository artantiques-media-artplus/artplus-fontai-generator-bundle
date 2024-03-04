<?php
namespace Fontai\Bundle\GeneratorBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


abstract class AbstractExtension extends Extension
{
  protected function processControllersSection(
    array &$controllersSection,
    array $mainSection
  )
  {
    foreach (array_keys($controllersSection) as $name)
    {
      $controllerConfig = &$controllersSection[$name];

      if (isset($controllerConfig['included_in']))
      {
        $includedIn = [];

        foreach ($controllerConfig['included_in'] as $entity)
        {
          $includedIn[Container::underscore($entity)] = $entity;
        }

        $controllerConfig['included_in'] = $includedIn;
      }

      $controllerConfig['fields'] = $controllerConfig['fields'] ?? [];

      foreach (['created_at' => 'Vytvořeno', 'updated_at' => 'Aktualizováno'] as $fieldName => $name)
      {
        if (!isset($controllerConfig['fields'][$fieldName]))
        {
          $controllerConfig['fields'][$fieldName] = [
            'name' => $name,
            'plain' => TRUE,
            'only_saved' => TRUE
          ];
        }
      }

      if (isset($controllerConfig['list']))
      {
        $controllerConfig['list']['fields'] = array_replace_recursive(
          $controllerConfig['fields'],
          $controllerConfig['list']['fields'] ?? []
        );

        if (isset($controllerConfig['list']['quickedit']))
        {
          $controllerConfig['list']['quickedit']['fields'] = array_replace_recursive(
            $controllerConfig['fields'],
            $controllerConfig['list']['quickedit']['fields'] ?? []
          );

          $controllerConfig['list']['quickedit']['validate'] = array_replace_recursive(
            $controllerConfig['edit']['validate'] ?? [],
            $controllerConfig['list']['quickedit']['validate'] ?? []
          );
        }

        if (!isset($controllerConfig['list']['actions']))
        {
          $controllerConfig['list']['actions'] = $mainSection['list']['actions'];

          if (isset($controllerConfig['export']))
          {
            $controllerConfig['list']['actions']['export'] = NULL;
          }
        }

        if (!isset($controllerConfig['list']['object_actions']))
        {
          $controllerConfig['list']['object_actions'] = $mainSection['list']['object_actions'];
        }

        if (!isset($controllerConfig['list']['batch_actions']))
        {
          $controllerConfig['list']['batch_actions'] = $mainSection['list']['batch_actions'];
        }

        if (is_array($controllerConfig['list']['actions']))
        {
          foreach (array_keys($controllerConfig['list']['actions']) as $id)
          {
            if (!isset($controllerConfig['list']['actions'][$id]['name']))
            {
              $controllerConfig['list']['actions'][$id]['name'] = [
                strtr($id, [
                  'create' => 'Přidat',
                  'export' => 'Exportovat'
                ]),
                $id == 'export' ? 'do XLS' : 'záznam'
              ];
            }
          }
        }

        if (is_array($controllerConfig['list']['object_actions']))
        {
          foreach (array_keys($controllerConfig['list']['object_actions']) as $id)
          {
            if (!isset($controllerConfig['list']['object_actions'][$id]['name']))
            {
              $controllerConfig['list']['object_actions'][$id]['name'] = strtr($id, [
                'edit' => 'Upravit',
                'delete' => 'Smazat'
              ]);
            }
          }
        }

        if (is_array($controllerConfig['list']['batch_actions']))
        {
          foreach (array_keys($controllerConfig['list']['batch_actions']) as $id)
          {
            if (!isset($controllerConfig['list']['batch_actions'][$id]['name']))
            {
              $controllerConfig['list']['batch_actions'][$id]['name'] = strtr($id, [
                'delete' => 'Smazat'
              ]);
            }
          }
        }
      }

      if (isset($controllerConfig['export']))
      {
        $controllerConfig['export']['fields'] = array_replace_recursive(
          $controllerConfig['fields'],
          $controllerConfig['export']['fields'] ?? []
        );
      }
      
      if (isset($controllerConfig['edit']))
      {
        $controllerConfig['edit']['fields'] = array_replace_recursive(
          $controllerConfig['fields'],
          $controllerConfig['edit']['fields'] ?? []
        );
        
        if (!isset($controllerConfig['edit']['actions']))
        {
          $controllerConfig['edit']['actions'] = [
            'back'   => NULL,
            'delete' => NULL,
            'save'   => NULL
          ];
        }
      }
    }
  }
}